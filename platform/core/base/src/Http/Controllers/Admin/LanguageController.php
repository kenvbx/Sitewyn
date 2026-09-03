<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Core\Base\Support\SettingStore;

/**
 * Language management lives inside the Settings area and reuses the
 * settings.edit permission on purpose (P5-01): no new permission means no
 * ripple through roles, the permission registry, or permission:sync.
 */
class LanguageController extends Controller
{
    public function __construct(private readonly SettingStore $settings) {}

    public function index(Request $request): View
    {
        $editingLanguage = $request->filled('language')
            ? Language::query()->find($request->integer('language'))
            : null;

        return view('core/base::admin.languages.index', [
            'languages' => Language::query()
                ->orderByDesc('is_default')
                ->orderBy('order')
                ->orderBy('name')
                ->get(),
            'editingLanguage' => $editingLanguage,
            'languageOptions' => $this->languageOptions(),
            'localeOptions' => $this->localeOptions(),
            'flagOptions' => $this->flagOptions(),
            'settings' => $this->languageSettings(),
            'activeTab' => $request->query('tab') === 'settings' ? 'settings' : 'detail',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['nullable', 'string', Rule::in(array_keys($this->localeOptions()))],
            'code' => ['required', 'string', Rule::in(array_keys($this->languageOptions())), Rule::unique('languages', 'code')],
            'text_direction' => ['nullable', 'string', Rule::in(['ltr', 'rtl'])],
            'flag' => ['nullable', 'string', Rule::in(array_keys($this->flagOptions()))],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $defaults = $this->defaultsForCode($validated['code']);

        Language::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'locale' => $validated['locale'] ?? $defaults['locale'],
            'flag' => $validated['flag'] ?? $defaults['flag'],
            'text_direction' => $validated['text_direction'] ?? $defaults['text_direction'],
            'order' => $validated['order'] ?? $this->nextOrder(),
            'is_default' => false,
            'is_active' => true,
        ]);

        admin_flash()->success(__('Language added successfully.'));

        return redirect()->route('admin.settings.languages.index');
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['nullable', 'string', Rule::in(array_keys($this->localeOptions()))],
            'code' => ['required', 'string', Rule::in(array_keys($this->languageOptions())), Rule::unique('languages', 'code')->ignore($language->id)],
            'text_direction' => ['nullable', 'string', Rule::in(['ltr', 'rtl'])],
            'flag' => ['nullable', 'string', Rule::in(array_keys($this->flagOptions()))],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $defaults = $this->defaultsForCode($validated['code']);

        $language->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'locale' => $validated['locale'] ?? $language->locale ?? $defaults['locale'],
            'flag' => $validated['flag'] ?? $language->flag ?? $defaults['flag'],
            'text_direction' => $validated['text_direction'] ?? $language->text_direction ?? $defaults['text_direction'],
            'order' => $validated['order'] ?? $language->order,
        ]);

        admin_flash()->success(__('Language updated successfully.'));

        return redirect()->route('admin.settings.languages.index');
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            admin_flash()->error(__('The default language cannot be deleted.'));

            return redirect()->route('admin.settings.languages.index');
        }

        // Every translations row of this locale (page/post/category) is
        // removed with it through the FK cascade on translations.locale.
        $language->delete();

        admin_flash()->success(__('Language deleted successfully.'));

        return redirect()->route('admin.settings.languages.index');
    }

    public function makeDefault(Language $language): RedirectResponse
    {
        // One default at all times: demote the current default and promote
        // the new one in the same transaction. The default is always active.
        DB::transaction(function () use ($language): void {
            Language::query()->where('is_default', true)->update(['is_default' => false]);
            $language->update(['is_default' => true, 'is_active' => true]);
        });

        admin_flash()->success(__(':name is now the default language.', ['name' => $language->name]));

        return redirect()->route('admin.settings.languages.index');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language_hide_default_from_url' => ['nullable', 'boolean'],
            'language_display' => ['required', 'string', Rule::in(['all', 'flag', 'name'])],
            'language_switcher_display' => ['required', 'string', Rule::in(['dropdown', 'list'])],
            'language_hidden_codes' => ['nullable', 'array'],
            'language_hidden_codes.*' => ['string', Rule::exists('languages', 'code')],
            'language_auto_detect' => ['nullable', 'boolean'],
        ]);

        $defaultCode = Language::query()->where('is_default', true)->value('code');
        $hiddenCodes = collect($validated['language_hidden_codes'] ?? [])
            ->reject(fn (string $code): bool => $code === $defaultCode)
            ->values()
            ->all();

        $this->settings->setMany([
            'language_hide_default_from_url' => $request->boolean('language_hide_default_from_url') ? '1' : '0',
            'language_display' => $validated['language_display'],
            'language_switcher_display' => $validated['language_switcher_display'],
            'language_hidden_codes' => json_encode($hiddenCodes),
            'language_auto_detect' => $request->boolean('language_auto_detect') ? '1' : '0',
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()->route('admin.settings.languages.index', ['tab' => 'settings']);
    }

    /**
     * @return array<string, string>
     */
    private function languageOptions(): array
    {
        return [
            'en' => 'English',
            'ar' => 'Arabic',
            'vi' => 'Tiếng Việt',
            'fr' => 'Français',
            'id' => 'Bahasa Indonesia',
            'tr' => 'Türkçe',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return [
            'en_US' => 'en_US',
            'ar' => 'ar',
            'vi' => 'vi',
            'fr' => 'fr',
            'id' => 'id',
            'tr' => 'tr',
        ];
    }

    /**
     * @return array<string, array{name: string, emoji: string}>
     */
    private function flagOptions(): array
    {
        return [
            'us' => ['name' => 'United States', 'emoji' => '🇺🇸'],
            'sa' => ['name' => 'Saudi Arabia', 'emoji' => '🇸🇦'],
            'vn' => ['name' => 'Vietnam', 'emoji' => '🇻🇳'],
            'fr' => ['name' => 'France', 'emoji' => '🇫🇷'],
            'id' => ['name' => 'Indonesia', 'emoji' => '🇮🇩'],
            'tr' => ['name' => 'Turkey', 'emoji' => '🇹🇷'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function languageSettings(): array
    {
        $hiddenCodes = $this->settings->get('language_hidden_codes', '[]');
        $decodedHiddenCodes = is_string($hiddenCodes) ? json_decode($hiddenCodes, true) : [];

        return [
            'language_hide_default_from_url' => $this->settings->get('language_hide_default_from_url', '1') === '1',
            'language_display' => $this->settings->get('language_display', 'all'),
            'language_switcher_display' => $this->settings->get('language_switcher_display', 'dropdown'),
            'language_hidden_codes' => is_array($decodedHiddenCodes) ? $decodedHiddenCodes : [],
            'language_auto_detect' => $this->settings->get('language_auto_detect', '0') === '1',
        ];
    }

    /**
     * @return array{locale: string, flag: string, text_direction: string}
     */
    private function defaultsForCode(string $code): array
    {
        return match ($code) {
            'en' => ['locale' => 'en_US', 'flag' => 'us', 'text_direction' => 'ltr'],
            'ar' => ['locale' => 'ar', 'flag' => 'sa', 'text_direction' => 'rtl'],
            'vi' => ['locale' => 'vi', 'flag' => 'vn', 'text_direction' => 'ltr'],
            'fr' => ['locale' => 'fr', 'flag' => 'fr', 'text_direction' => 'ltr'],
            'id' => ['locale' => 'id', 'flag' => 'id', 'text_direction' => 'ltr'],
            'tr' => ['locale' => 'tr', 'flag' => 'tr', 'text_direction' => 'ltr'],
            default => ['locale' => $code, 'flag' => 'us', 'text_direction' => 'ltr'],
        };
    }

    private function nextOrder(): int
    {
        return ((int) Language::query()->max('order')) + 1;
    }
}
