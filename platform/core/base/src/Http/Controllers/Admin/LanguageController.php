<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Models\Language;

/**
 * Language management lives inside the Settings area and reuses the
 * settings.edit permission on purpose (P5-01): no new permission means no
 * ripple through roles, the permission registry, or permission:sync.
 */
class LanguageController extends Controller
{
    public function index(): View
    {
        return view('core/base::admin.languages.index', [
            // Default language first, then alphabetical.
            'languages' => Language::query()
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:2', 'regex:/^[a-z]{2}$/', Rule::unique('languages', 'code')],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Language::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_default' => false,
            'is_active' => true,
        ]);

        admin_flash()->success(__('Language added successfully.'));

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
}
