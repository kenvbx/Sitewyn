<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Core\Base\Support\LanguageCatalog;

class LocaleController extends Controller
{
    public function __construct(private readonly LanguageCatalog $languages) {}

    public function index(): View
    {
        return view('core/base::admin.translations.locales', [
            'languages' => Language::query()
                ->orderBy('name')
                ->get(),
            'localeOptions' => $this->localeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys($this->languages->languages())), Rule::unique('languages', 'code')],
        ]);

        $language = $this->languages->languages()[$validated['locale']];

        Language::query()->create([
            'code' => $language['code'],
            'name' => $language['native_name'],
            'locale' => $language['locale'],
            'flag' => $language['flag'],
            'text_direction' => $language['text_direction'],
            'order' => $this->nextOrder(),
            'is_default' => false,
            'is_active' => true,
        ]);

        admin_flash()->success(__('Locale added successfully.'));

        return redirect()->route('admin.translations.locales.index');
    }

    public function download(Language $language)
    {
        $payload = [
            'name' => $language->name,
            'locale' => $language->code,
            'language' => $language->locale,
            'is_default' => $language->is_default,
            'translations' => [],
        ];

        return Response::streamDownload(
            static function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            $language->code.'.json',
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            admin_flash()->error(__('The default locale cannot be deleted.'));

            return redirect()->route('admin.translations.locales.index');
        }

        $language->delete();

        admin_flash()->success(__('Locale deleted successfully.'));

        return redirect()->route('admin.translations.locales.index');
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return collect($this->languages->languages())
            ->mapWithKeys(fn (array $language, string $code): array => [
                $code => $language['native_name'].' - '.$code,
            ])
            ->all();
    }

    private function nextOrder(): int
    {
        return ((int) Language::query()->max('order')) + 1;
    }
}
