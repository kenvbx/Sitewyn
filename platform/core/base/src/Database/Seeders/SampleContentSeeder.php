<?php

namespace Sitewyn\Core\Base\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Sitewyn\Core\Base\Models\Menu;
use Sitewyn\Core\Base\Models\MenuItem;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Models\Tag;
use Sitewyn\Packages\Page\Models\Page;

/**
 * Sample content for previewing the frontend with realistic data. Never
 * wired into DatabaseSeeder — seeding demo content is a deliberate choice
 * made by running `artisan db:seed --class=...SampleContentSeeder`.
 *
 * Idempotent by design: every record is firstOrCreate'd on its slug, so
 * re-running never duplicates rows and never overwrites content that has
 * been edited by hand after the first run.
 */
class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        $pages = $this->seedPages();
        $categories = $this->seedCategories();
        $tags = $this->seedTags();

        $this->seedPosts($categories, $tags);

        $menu = $this->seedPrimaryMenu($pages);

        $this->command?->info(sprintf(
            'Sample content ready: %d pages, %d categories, %d posts, %d tags, %d menu items.',
            $pages->count(),
            $categories->count(),
            Post::query()->count(),
            $tags->count(),
            $menu->items()->count(),
        ));
    }

    /**
     * @return Collection<string, Page>
     */
    private function seedPages(): Collection
    {
        return collect($this->pages())->mapWithKeys(function (array $attributes): array {
            $page = Page::query()->firstOrCreate(
                ['slug' => $attributes['slug']],
                Arr::except($attributes, ['slug']),
            );

            return [$attributes['slug'] => $page];
        });
    }

    /**
     * @return Collection<string, Category>
     */
    private function seedCategories(): Collection
    {
        return collect($this->categories())->mapWithKeys(function (array $attributes): array {
            $category = Category::query()->firstOrCreate(
                ['slug' => $attributes['slug']],
                Arr::except($attributes, ['slug']),
            );

            return [$attributes['slug'] => $category];
        });
    }

    /**
     * @return Collection<string, Tag>
     */
    private function seedTags(): Collection
    {
        return collect($this->tags())->mapWithKeys(function (array $attributes): array {
            $tag = Tag::query()->firstOrCreate(
                ['slug' => $attributes['slug']],
                Arr::except($attributes, ['slug']),
            );

            return [$attributes['slug'] => $tag];
        });
    }

    /**
     * @param  Collection<string, Category>  $categories
     * @param  Collection<string, Tag>  $tags
     */
    private function seedPosts(Collection $categories, Collection $tags): void
    {
        foreach ($this->posts() as $attributes) {
            $attributes['category_id'] = $categories[$attributes['category']]->id;

            $post = Post::query()->firstOrCreate(
                ['slug' => $attributes['slug']],
                Arr::except($attributes, ['slug', 'tags', 'category']),
            );

            if (! $post->wasRecentlyCreated) {
                continue;
            }

            $post->tags()->sync(
                collect($attributes['tags'])
                    ->map(fn (string $slug): int => $tags[$slug]->id)
                    ->all(),
            );
        }
    }

    /**
     * The "Main" nav is only filled the first time it is created — a menu
     * that already exists belongs to whoever edited it last.
     *
     * @param  Collection<string, Page>  $pages
     */
    private function seedPrimaryMenu(Collection $pages): Menu
    {
        $menu = Menu::query()->firstOrCreate(
            ['slug' => 'main'],
            ['name' => 'Main', 'location' => Menu::LOCATION_PRIMARY],
        );

        if (! $menu->wasRecentlyCreated) {
            return $menu;
        }

        foreach (['about-us', 'services', 'contact'] as $order => $slug) {
            MenuItem::query()->create([
                'menu_id' => $menu->id,
                'label' => $pages[$slug]->title,
                'type' => MenuItem::TYPE_PAGE,
                'target_id' => $pages[$slug]->id,
                'order' => $order,
            ]);
        }

        return $menu;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'about-us',
                'title' => 'About us',
                'status' => Page::STATUS_PUBLISHED,
                'seo_description' => 'Meet Sitewyn Studio — a small web studio building fast, maintainable websites and content platforms for teams that ship often.',
                'content' => <<<'HTML'
                    <h2>Who we are</h2>
                    <p>Sitewyn Studio is a small, independent web studio that builds fast, maintainable websites and content platforms. We believe publishing tools should stay out of your way: easy to edit, quick to load, and boring in all the right places. Our work spans marketing sites, documentation hubs, and custom publishing platforms for teams that ship often.</p>
                    <p>The studio started in 2021 with three developers who kept running into the same problem — heavy content management systems that needed more care than the content they hosted. We built Sitewyn to prove that a modern CMS can be modular, well-tested, and pleasant to administer without a wall of plugins.</p>
                    <h2>What we value</h2>
                    <ul>
                    <li><strong>Own your content.</strong> Your pages, posts, and media live in a database you control, with clean export paths out.</li>
                    <li><strong>Performance is a feature.</strong> Every theme decision starts with a fast first render and honest markup.</li>
                    <li><strong>Simple by default.</strong> The tools cover the common cases; the edge cases live in code, not in settings screens.</li>
                    <li><strong>Tested, not promised.</strong> The platform ships with a broad automated test suite, and so does everything we build on top of it.</li>
                    </ul>
                    <h2>How we work</h2>
                    <p>Projects with us start with a short discovery week, then move in two-week increments with a demo at the end of each one. You review real pages on a real URL, not slide decks. After launch we stay close — most of our clients keep a small monthly retainer for improvements, updates, and the occasional urgent fix.</p>
                    <p>If that sounds like the kind of partner you are looking for, read about our <a href="/services">services</a> or say hello through the <a href="/contact">contact page</a>. We are happy to talk through a project even when the honest answer turns out to be that you do not need us.</p>
                    HTML,
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'status' => Page::STATUS_PUBLISHED,
                'seo_description' => 'Get in touch with Sitewyn Studio — email, phone, and office details for project inquiries, support, and feedback.',
                'content' => <<<'HTML'
                    <!-- Sample contact details seeded by SampleContentSeeder — replace them with real values before launch. -->
                    <p>Questions, feedback, or a project you would like to talk about? We would love to hear from you. Email is the fastest way to reach the team, and we usually reply within one business day.</p>
                    <h2>Reach us directly</h2>
                    <ul>
                    <li><strong>Email:</strong> hello@sitewyn-studio.example</li>
                    <li><strong>Phone:</strong> +1 (555) 014-2038</li>
                    <li><strong>Office:</strong> 42 Harbour Lane, Suite 300, Portland, OR 97204</li>
                    <li><strong>Hours:</strong> Monday to Friday, 9:00–17:30 Pacific Time</li>
                    </ul>
                    <h2>What to include in your message</h2>
                    <p>For project inquiries, a few lines about your goals, timeline, and budget range help us reply with something useful instead of a generic brochure. Links to the site you have today, if any, and to sites you admire tell us a lot in seconds.</p>
                    <p>For support requests, include the version of Sitewyn you run, the browser and operating system you tested with, and the exact steps that reproduce the problem. A short screen recording saves a round of back-and-forth.</p>
                    <p>Prefer to talk first? Send an email with two or three time slots that work for you and we will set up a call. There is no charge for a first conversation about a potential project.</p>
                    HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'status' => Page::STATUS_PUBLISHED,
                'seo_description' => 'How Sitewyn Studio collects, uses, and protects personal information, including cookies, analytics, and your rights.',
                'content' => <<<'HTML'
                    <p>This policy explains what information we collect when you use our website, why we collect it, and the choices you have. It applies to this site and any subdomains we operate. By using the site you agree to the practices described here.</p>
                    <h2>Information we collect</h2>
                    <p>We collect the information you give us directly — such as your name, email address, and message content when you contact us — and a small amount of technical data recorded automatically, including pages visited, referrer, browser type, and approximate region derived from your IP address.</p>
                    <h2>How we use your information</h2>
                    <p>We use contact details to reply to your questions and to deliver services you have asked for. Aggregated visit data helps us understand which pages are useful so we can improve the site. We do not sell personal information, and we do not use it to build advertising profiles.</p>
                    <h2>Cookies</h2>
                    <p>The site uses a minimal set of cookies: one to keep your session preferences and one to remember your cookie choice. We do not run third-party advertising trackers. You can clear or block cookies in your browser at any time; the site will keep working, though stored preferences will reset.</p>
                    <h2>Data retention and your rights</h2>
                    <p>We keep contact messages for as long as needed to handle your request and for the period required by law, then delete them. You can ask for a copy of the information we hold about you, ask us to correct it, or ask us to delete it — email us and we will respond within thirty days.</p>
                    <h2>Contact</h2>
                    <p>Questions about this policy can go through the <a href="/contact">contact page</a>. If we make material changes, we will update the revision date below and, where appropriate, announce the change on the site.</p>
                    <p><em>Last updated: August 2026.</em></p>
                    HTML,
            ],
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'status' => Page::STATUS_PUBLISHED,
                'seo_description' => 'The terms that govern your use of the Sitewyn Studio website, including acceptable use, intellectual property, and liability.',
                'content' => <<<'HTML'
                    <p>These terms govern your use of our website and any services we provide through it. By using the site you accept these terms. If you do not agree with them, please stop using the site.</p>
                    <h2>Acceptance of terms</h2>
                    <p>When you open the site, create an account, or contact us through it, you confirm that you have read these terms and agree to them. If you use the site on behalf of a company, you also confirm that you are allowed to bind that company to these terms.</p>
                    <h2>Use of the service</h2>
                    <p>You may browse, read, and link to the site freely. You may not attempt to break its security, scrape it in ways that degrade the service, resell access to it, or use it to publish content that is unlawful or infringes the rights of others. We may suspend access for behaviour that harms the site or its visitors.</p>
                    <h2>Intellectual property</h2>
                    <p>The site's text, design, and code belong to Sitewyn Studio or its licensors. You are welcome to quote short passages with a link back; anything beyond that needs written permission. Content you send us — such as messages and project briefs — stays yours; you only grant us the right to use it to answer you and deliver the work.</p>
                    <h2>Limitation of liability</h2>
                    <p>The site is provided as is, without warranties of any kind. To the extent the law allows, we are not liable for indirect or consequential damages, lost profits, or lost data that result from using the site. Nothing in these terms limits liability that cannot be limited by law.</p>
                    <h2>Changes to these terms</h2>
                    <p>We may update these terms as the service evolves. The date below shows the latest revision, and continued use of the site after a change counts as acceptance of the updated terms.</p>
                    <p><em>Last updated: August 2026.</em></p>
                    HTML,
            ],
            [
                'slug' => 'services',
                'title' => 'Services',
                'status' => Page::STATUS_PUBLISHED,
                'seo_description' => 'Web development, CMS solutions, and long-term maintenance — what Sitewyn Studio offers and how each engagement works.',
                'content' => <<<'HTML'
                    <p>Sitewyn Studio focuses on three things: building websites that are quick to load and easy to edit, tailoring the Sitewyn CMS to unusual publishing needs, and keeping client sites healthy for the long run. Every engagement starts with a short scoping conversation so the work matches the problem.</p>
                    <h2>Web development</h2>
                    <p>We design and build marketing sites, documentation hubs, and small web applications with modern PHP and a lean front end. Projects are delivered in two-week increments with a working URL at the end of each sprint, so you always see real progress. Everything we ship goes through automated tests and a performance budget agreed up front.</p>
                    <h2>CMS solutions</h2>
                    <p>Out of the box, Sitewyn covers pages, posts, media, menus, and translations. When your publishing model is more specific — structured product data, multi-language editorial workflows, scheduled releases — we extend the CMS with custom modules that follow the same patterns and the same test discipline as the core.</p>
                    <h2>Maintenance</h2>
                    <p>Launch day is the start, not the end. Our maintenance plans cover platform updates, security patches, backups, uptime monitoring, and a monthly improvement allowance. You get a short report each month describing what changed and what we recommend next, with no jargon and no surprise invoices.</p>
                    <p>Not sure which of the three fits your situation? Write to us through the <a href="/contact">contact page</a> and we will point you in the right direction — even if the honest answer is that you need less than you think.</p>
                    HTML,
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function categories(): array
    {
        return [
            [
                'slug' => 'news',
                'name' => 'News',
                'description' => 'Product updates, milestones, and announcements from the Sitewyn team.',
            ],
            [
                'slug' => 'tutorials',
                'name' => 'Tutorials',
                'description' => 'Step-by-step guides for building, extending, and testing with Sitewyn.',
            ],
            [
                'slug' => 'releases',
                'name' => 'Releases',
                'description' => 'Release notes for every Sitewyn version, with upgrade notes.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function tags(): array
    {
        return [
            ['slug' => 'laravel', 'name' => 'Laravel'],
            ['slug' => 'cms', 'name' => 'CMS'],
            ['slug' => 'php', 'name' => 'PHP'],
            ['slug' => 'testing', 'name' => 'Testing'],
            ['slug' => 'architecture', 'name' => 'Architecture'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function posts(): array
    {
        return [
            [
                'slug' => 'building-a-modular-cms-lessons-from-the-first-1000-commits',
                'title' => 'Building a Modular CMS: Lessons from the First 1,000 Commits',
                'status' => Post::STATUS_PUBLISHED,
                'category' => 'news',
                'tags' => ['cms', 'architecture'],
                'seo_description' => 'What the first 1,000 commits of a modular CMS taught us about package boundaries, migrations, and deleting early.',
                'content' => <<<'HTML'
                    <p>Last week the main branch of Sitewyn passed its one thousandth commit. It felt like a good moment to look back at what building a modular CMS actually taught us — not the marketing version, the version we would tell a friend starting a similar project.</p>
                    <p>The first lesson is that module boundaries are drawn with migrations, not diagrams. Each package owns its database migrations, routes, and views, and the core never reaches directly into a package's tables. Cross-module references go through well-marked seams — menu items, for example, store page identifiers without a foreign key, precisely because a cross-package foreign key would weld two migrations together forever. That single decision has saved us more pain than any architecture meeting ever did.</p>
                    <p>The second lesson is that a test suite is what makes modules affordable. With several hundred feature tests running against an in-memory database, we can move a controller between packages in an afternoon instead of a sprint. The tests do not just catch regressions; they define what "the module still works" means when nobody remembers anymore.</p>
                    <p>The last lesson is the least glamorous: delete early. The ideas that did not survive — a plugin marketplace, a theme composer layer — were cheap to remove because they were never allowed to grow hooks into everything. If your roadmap cannot survive deletion, the roadmap is the problem, not the code.</p>
                    HTML,
            ],
            [
                'slug' => 'understanding-laravel-middleware-execution-order',
                'title' => 'Understanding Laravel Middleware Execution Order',
                'status' => Post::STATUS_PUBLISHED,
                'category' => 'tutorials',
                'tags' => ['laravel', 'php'],
                'seo_description' => 'How Laravel orders global middleware, middleware groups, and route middleware — and how $middlewarePriority changes it.',
                'content' => <<<'HTML'
                    <p>Middleware looks simple until the first time a redirect fires before an authentication check, and you spend an hour wondering why. The order in which Laravel runs middleware is deterministic — it just is not obvious until you have traced it once.</p>
                    <p>Laravel sorts middleware into three layers. Global middleware runs on every request, in the order it is listed in the HTTP kernel or the application bootstrap. Middleware groups like <strong>web</strong> and <strong>api</strong> run next, again in list order. Route middleware assigned directly on a route run last, in the order you attach them — with one important exception: the middleware priority list re-sorts a known set of middleware (sessions, CSRF protection, authentication, and friends) so they always run in a safe relative order no matter how you attach them.</p>
                    <p>The practical takeaway is that "before" and "after" depend on the direction of the request. Code that runs before <strong>$next</strong> acts on the way in — checks, rewrites, early redirects. Code after <strong>$next</strong> acts on the way out, wrapping the response, which is why response-modifying middleware usually looks like it "runs first" in the file even though it effectively acts last.</p>
                    <p>When debugging, resist the urge to guess. Drop a log line at the top and bottom of each suspect middleware with the class name in the message, hit the route once, and read the log top to bottom — it is a faithful, timestamped picture of the real execution order. It takes two minutes and it ends the debate.</p>
                    HTML,
            ],
            [
                'slug' => 'testing-feature-routes-against-in-memory-sqlite',
                'title' => 'Testing Feature Routes Against In-Memory SQLite',
                'status' => Post::STATUS_PUBLISHED,
                'category' => 'tutorials',
                'tags' => ['testing', 'laravel'],
                'seo_description' => 'Why in-memory SQLite keeps Laravel feature tests fast, where it differs from MySQL, and when to add a MySQL CI job anyway.',
                'content' => <<<'HTML'
                    <p>A fast test suite gets run; a slow one gets skipped. For Sitewyn's feature tests we run against an in-memory SQLite database, and the whole suite of around five hundred tests finishes in under a minute. That speed is the difference between testing every save and testing when we get around to it.</p>
                    <p>The setup is two lines in phpunit.xml: the database connection set to <strong>sqlite</strong> and the database name set to <strong>:memory:</strong>. Combine it with the RefreshDatabase trait so every test starts from a freshly migrated schema. Because the database lives in RAM, migrations run in hundreds of milliseconds instead of seconds, and tests cannot leak rows into each other through a shared file.</p>
                    <p>SQLite is not a drop-in clone of MySQL, so know the edges. Foreign key enforcement, default values for timestamps, and index handling all differ in small ways, and queries that lean on MySQL-specific syntax will not work. Our rule of thumb: write feature tests against SQLite for speed and application behaviour, and keep a small, focused MySQL job in continuous integration for the schema tests and any raw-query code.</p>
                    <p>If a test fails on MySQL but passes on SQLite, do not shrug it off — it usually points at a query that relies on engine-specific behaviour. Fixing it to work on both engines almost always makes the code more portable and more predictable in production.</p>
                    HTML,
            ],
            [
                'slug' => 'sitewyn-0-5-translations-storage-and-locale-aware-routing',
                'title' => 'Sitewyn 0.5: Translations Storage and Locale-Aware Routing',
                'status' => Post::STATUS_PUBLISHED,
                'category' => 'releases',
                'tags' => ['cms', 'php'],
                'seo_description' => 'Sitewyn 0.5 adds translation tables for pages, posts, and categories, plus locale-prefixed routing with clean fallbacks.',
                'content' => <<<'HTML'
                    <p>Sitewyn 0.5 is out, and it is the release that makes the CMS genuinely multi-language. Pages, posts, and categories now support per-language translations with clean fallback behaviour, and the frontend understands locale-prefixed URLs out of the box.</p>
                    <p>Under the hood, each translatable model gained a translations table keyed by locale. The default language's row lives where it always did, and a translation never owns a slug of its own — translated content is served at <strong>/{locale}/{original-slug}</strong>, so the slug namespace stays unique and shared links keep working. Fallback is straightforward: when a translation is missing, the default language's content is shown rather than a 404.</p>
                    <p>Admin-side, the language manager lets you add or disable locales, and the editor shows a translation tab for each active language. No configuration files, no environment variables — adding French to a site is two clicks and filling in the content.</p>
                    <p>Upgrading from 0.4 is a standard <strong>artisan migrate</strong>; no data changes are required, and sites that only use one language see no behaviour change. The full changelog ships with the repository, and as always, the release is covered by the platform test suite before it is tagged.</p>
                    HTML,
            ],
            [
                'slug' => 'why-every-cms-needs-a-central-slug-service',
                'title' => 'Why Every CMS Needs a Central Slug Service',
                'status' => Post::STATUS_PUBLISHED,
                'category' => 'tutorials',
                'tags' => ['architecture', 'cms'],
                'seo_description' => 'Slug collisions across content types cause subtle routing bugs — how a central slug service keeps every URL unique.',
                'content' => <<<'HTML'
                    <p>Every CMS that supports more than one content type eventually meets the same bug: a page and a blog post both claim <strong>/hello-world</strong>, and whichever one the router asks for first wins. The visitor gets the wrong page half the time, and the admin sees nothing wrong. The fix is not smarter routing — it is making slugs unique at the moment they are saved.</p>
                    <p>Our approach is a single slug service that all modules share. It does two jobs: turning any title into a URL-safe string, and guaranteeing uniqueness by checking the candidate slug across every table that owns a URL. When a collision is found, it appends -2, -3, and so on until the slug is free. Because every module goes through the same service, adding a new content type cannot quietly re-introduce the bug.</p>
                    <p>The subtle part is the ignore list. When a page is being updated, its own row must not count as a collision, or saving without changes would rename every page to page-title-2. Multi-table setups also need to know which table owns the record being edited, otherwise rows in other tables that happen to share the same id are skipped as well. These details sound trivial, and they are exactly the kind that produce one bug report per quarter.</p>
                    <p>The payoff shows up in routing. With uniqueness guaranteed at write time, the frontend router can be a dumb, fast lookup: find the published row with this slug, serve it, or return a 404. No ambiguity, no priority rules between modules, and no cache invalidation gymnastics when two pieces of content fight over one URL.</p>
                    HTML,
            ],
        ];
    }
}
