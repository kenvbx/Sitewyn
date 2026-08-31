<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sitewyn\Core\Base\Models\AnalyticsVisit;
use Sitewyn\Core\Base\Models\AuditLog;
use Sitewyn\Core\Base\Support\PluginManager;
use Sitewyn\Core\Base\Support\ThemeManager;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;

class DashboardController extends Controller
{
    private const PERIODS = ['today', '7d', '30d'];

    private const PERIOD_LABELS = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
    ];

    /**
     * Humanized audit actions; unknown actions fall back to "{action} {subject}".
     */
    private const ACTION_LABELS = [
        'created' => 'created :subject',
        'updated' => 'updated :subject',
        'deleted' => 'deleted :subject',
        'login' => 'signed in',
        'logout' => 'signed out',
        'login-failed' => 'had a failed sign-in attempt',
    ];

    /**
     * Browser detection, checked in this order: a Chrome user agent contains
     * "Safari" and a Chromium-Edge one contains "Chrome", so the most
     * specific families must win first.
     */
    private const BROWSER_PATTERNS = [
        'Edge' => '/edg(?:e|a|ios)?\//i',
        'Opera' => '/opr\/|opera/i',
        'Chrome' => '/chrome|crios/i',
        'Safari' => '/safari/i',
        'Firefox' => '/firefox|fxios/i',
    ];

    public function __invoke(Request $request, ThemeManager $themes, PluginManager $plugins): View
    {
        $period = (string) $request->query('period', 'today');
        $period = in_array($period, self::PERIODS, true) ? $period : 'today';

        [$start, $end] = $this->window($period);

        return view('core/base::admin.dashboard', [
            'stats' => [
                'themes' => $themes->all()->count(),
                'users' => User::query()->count(),
                'plugins' => count($plugins->availableSlugs()),
                'pages' => Page::query()->count(),
            ],
            'period' => $period,
            'periodLabel' => self::PERIOD_LABELS[$period],
            'chart' => $this->chart($start, $end, $period),
            'mini' => $this->miniStats($start, $end),
            'topPages' => $this->topPages($start, $end),
            'topBrowsers' => $this->topBrowsers($start, $end),
            'topReferrers' => $this->topReferrers($start, $end),
            'recentPosts' => $this->recentPosts(),
            'activities' => $this->activities(),
            'activityTotal' => AuditLog::query()->count(),
        ]);
    }

    /**
     * Inclusive analytics window for the selected period.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function window(string $period): array
    {
        $end = CarbonImmutable::now();

        $start = match ($period) {
            '7d' => $end->subDays(6)->startOfDay(),
            '30d' => $end->subDays(29)->startOfDay(),
            default => $end->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * Area-chart payload: pageviews (rows) and sessions (distinct session
     * ids) per bucket. "today" buckets by hour, the day ranges bucket by
     * day. The bucket expression is driver-specific but both dialects emit
     * "Y-m-d H:00:00" strings, which the PHP buckets are keyed on.
     *
     * @return array{labels: array<int, string>, pageviews: array<int, int>, sessions: array<int, int>}
     */
    private function chart(CarbonImmutable $start, CarbonImmutable $end, string $period): array
    {
        $hourly = $period === 'today';
        $expression = $this->bucketExpression($hourly);

        // The chart always shows the full period — 24 hourly buckets for
        // "today", every calendar day of the range otherwise — while the
        // queries stay bounded by "now".
        $bucketEnd = $end->endOfDay();

        $buckets = [];
        $labels = [];

        for ($cursor = $start; $cursor->lessThanOrEqualTo($bucketEnd); $cursor = $hourly ? $cursor->addHour() : $cursor->addDay()) {
            $key = $cursor->format($hourly ? 'Y-m-d H:00:00' : 'Y-m-d 00:00:00');
            $buckets[$key] = ['views' => 0, 'sessions' => 0];
            $labels[] = $hourly ? $cursor->format('H:00') : $cursor->format('j M');
        }

        $rows = AnalyticsVisit::query()
            ->selectRaw($expression.' as bucket, count(*) as views, count(distinct session_id) as sessions')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw($expression))
            ->get();

        foreach ($rows as $row) {
            if (isset($buckets[$row->bucket])) {
                $buckets[$row->bucket] = ['views' => (int) $row->views, 'sessions' => (int) $row->sessions];
            }
        }

        return [
            'labels' => $labels,
            'pageviews' => array_column($buckets, 'views'),
            'sessions' => array_column($buckets, 'sessions'),
        ];
    }

    private function bucketExpression(bool $hourly): string
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($hourly) {
            return $isSqlite
                ? "strftime('%Y-%m-%d %H:00:00', created_at)"
                : "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')";
        }

        return $isSqlite
            ? "strftime('%Y-%m-%d 00:00:00', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m-%d 00:00:00')";
    }

    /**
     * @return array{sessions: int, visitors: int, pageviews: int, bounce: string}
     */
    private function miniStats(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $totals = AnalyticsVisit::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('count(*) as pageviews, count(distinct session_id) as sessions, count(distinct ip) as visitors')
            ->first();

        $sessionPaths = AnalyticsVisit::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('session_id')
            ->select('session_id', DB::raw('count(distinct path) as paths'))
            ->groupBy('session_id')
            ->get();

        $sessionCount = $sessionPaths->count();
        $bounced = $sessionPaths->where('paths', 1)->count();
        $bounce = $sessionCount > 0 ? round($bounced / $sessionCount * 100, 1) : 0.0;

        return [
            'sessions' => (int) $totals->sessions,
            'visitors' => (int) $totals->visitors,
            'pageviews' => (int) $totals->pageviews,
            'bounce' => number_format($bounce, 1),
        ];
    }

    /**
     * Top ten visited paths with friendly names: a published page slug
     * resolves to the page title, /blog/{slug} to the post title, anything
     * else stays as the raw path.
     *
     * @return array<int, array{label: string, href: string, views: int}>
     */
    private function topPages(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = AnalyticsVisit::query()
            ->whereBetween('created_at', [$start, $end])
            ->select('path', DB::raw('count(*) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->orderBy('path')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $blogSlugs = [];
        $pageSlugs = [];

        foreach ($rows as $row) {
            $path = (string) $row->path;

            if (str_starts_with($path, 'blog/')) {
                $blogSlugs[] = substr($path, 5);
            } elseif ($path !== '/') {
                $pageSlugs[] = $path;
            }
        }

        $pageTitles = $pageSlugs === [] ? collect() : Page::query()
            ->whereIn('slug', $pageSlugs)
            ->where('status', Page::STATUS_PUBLISHED)
            ->pluck('title', 'slug');
        $postTitles = $blogSlugs === [] ? collect() : Post::query()
            ->whereIn('slug', $blogSlugs)
            ->where('status', Post::STATUS_PUBLISHED)
            ->pluck('title', 'slug');

        return $rows->map(function (object $row) use ($pageTitles, $postTitles): array {
            $path = (string) $row->path;

            if (str_starts_with($path, 'blog/')) {
                $slug = substr($path, 5);

                return [
                    'label' => $postTitles->get($slug, $path),
                    'href' => '/blog/'.$slug,
                    'views' => (int) $row->views,
                ];
            }

            return [
                'label' => $path === '/' ? $path : $pageTitles->get($path, $path),
                'href' => $path === '/' ? '/' : '/'.$path,
                'views' => (int) $row->views,
            ];
        })->all();
    }

    /**
     * Sessions per browser family. Classification needs PHP regex (no
     * portable SQL), so only the distinct session/user-agent pairs are
     * fetched and grouped here.
     *
     * @return array<int, array{browser: string, sessions: int}>
     */
    private function topBrowsers(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $pairs = AnalyticsVisit::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('session_id')
            ->select('session_id', 'user_agent')
            ->distinct()
            ->get();

        $sessionsPerBrowser = [];

        foreach ($pairs as $pair) {
            $sessionsPerBrowser[$this->detectBrowser($pair->user_agent)][] = $pair->session_id;
        }

        return collect($sessionsPerBrowser)
            ->map(fn (array $sessionIds, string $browser): array => [
                'browser' => $browser,
                'sessions' => count(array_unique($sessionIds)),
            ])
            ->sortByDesc('sessions')
            ->values()
            ->all();
    }

    private function detectBrowser(?string $userAgent): string
    {
        foreach (self::BROWSER_PATTERNS as $browser => $pattern) {
            if ($userAgent !== null && preg_match($pattern, $userAgent) === 1) {
                return $browser;
            }
        }

        return 'Other';
    }

    /**
     * @return array<int, array{label: string, views: int}>
     */
    private function topReferrers(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = AnalyticsVisit::query()
            ->whereBetween('created_at', [$start, $end])
            ->select('referer', DB::raw('count(*) as views'))
            ->groupBy('referer')
            ->orderByDesc('views')
            ->orderBy('referer')
            ->limit(10)
            ->get();

        return $rows->map(fn (object $row): array => [
            'label' => $this->referrerLabel($row->referer),
            'views' => (int) $row->views,
        ])->all();
    }

    private function referrerLabel(mixed $referer): string
    {
        $referer = is_string($referer) ? trim($referer) : '';

        if ($referer === '') {
            return '(direct)';
        }

        // Full URLs collapse to their host; anything unparseable stays as-is.
        return parse_url($referer, PHP_URL_HOST) ?: $referer;
    }

    /**
     * @return Collection<int, Post>
     */
    private function recentPosts(): Collection
    {
        return Post::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'created_at']);
    }

    /**
     * @return array<int, array{name: string, initials: string, badge: array{text: string, class: string}|null, text: string, time: string|null, ip: string|null}>
     */
    private function activities(): array
    {
        $logs = AuditLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Eager load the users (with roles for the badge) in one query — the
        // audit rows themselves are a bounded list of ten.
        $users = User::query()
            ->with('roles')
            ->whereIn('id', $logs->pluck('user_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return $logs
            ->map(fn (AuditLog $log): array => $this->activityItem($log, $users->get($log->user_id)))
            ->all();
    }

    private function activityItem(AuditLog $log, ?User $user): array
    {
        $name = $user?->name ?: 'System';
        $initials = collect(explode(' ', $name))
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('') ?: '?';

        $subject = class_basename($log->subject_type).($log->subject_id !== null ? ' #'.$log->subject_id : '');
        $template = self::ACTION_LABELS[$log->action] ?? ':action :subject';
        $text = str_replace([':action', ':subject'], [$log->action, $subject], $template);

        $badge = null;

        if ($user?->is_super_admin) {
            $badge = ['text' => 'admin', 'class' => 'bg-green-lt'];
        } elseif ($user !== null && ($roleName = $user->roles->first()?->name) !== null && $roleName !== '') {
            $badge = ['text' => $roleName, 'class' => 'bg-secondary-lt'];
        }

        return [
            'name' => $name,
            'initials' => $initials,
            'badge' => $badge,
            'text' => $text,
            'time' => $log->created_at?->diffForHumans(),
            'ip' => $log->ip_address,
        ];
    }
}
