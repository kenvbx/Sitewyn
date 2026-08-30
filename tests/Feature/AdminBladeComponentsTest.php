<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Sitewyn\Core\Base\View\Components\Admin\Editor;
use Tests\TestCase;

class AdminBladeComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_card_and_data_table_components_render_tabler_markup(): void
    {
        $html = Blade::render(
            <<<'BLADE'
            <x-admin-card title="Reusable card">
              Card body
            </x-admin-card>

            <x-admin-data-table title="Reusable table" :empty-colspan="2">
              <x-slot:head>
                <tr><th>Name</th><th>Status</th></tr>
              </x-slot:head>
              <tr><td>Editor</td><td>Active</td></tr>
            </x-admin-data-table>
            BLADE,
            [],
            deleteCachedView: true,
        );

        $this->assertStringContainsString('class="card"', $html);
        $this->assertStringContainsString('Reusable card', $html);
        $this->assertStringContainsString('table-responsive', $html);
        $this->assertStringContainsString('table table-vcenter card-table table-striped', $html);
        $this->assertStringContainsString('Editor', $html);
    }

    public function test_admin_data_table_component_can_enable_list_controls(): void
    {
        $html = Blade::render(
            <<<'BLADE'
            <x-admin-data-table
              id="test-table"
              title="Searchable table"
              :value-names="['sort-name', ['name' => 'sort-date', 'attr' => 'data-date']]"
              searchable
              paginated
              :page="10"
              search-placeholder="Search records..."
            >
              <x-slot:head>
                <tr>
                  <th><button class="table-sort" data-sort="sort-name">Name</button></th>
                  <th><button class="table-sort" data-sort="sort-date">Date</button></th>
                </tr>
              </x-slot:head>
              <tr><td class="sort-name">Editor</td><td class="sort-date" data-date="1">Today</td></tr>
            </x-admin-data-table>

            @stack('scripts')
            BLADE,
            [],
            deleteCachedView: true,
        );

        $this->assertStringContainsString('id="test-table"', $html);
        $this->assertStringContainsString('class="form-control search"', $html);
        $this->assertStringContainsString('class="table-tbody"', $html);
        $this->assertStringContainsString('class="pagination m-0 ms-auto"', $html);
        $this->assertStringContainsString('list.min.js', $html);
        $this->assertStringContainsString("sortClass: 'table-sort'", $html);
    }

    public function test_admin_form_group_alert_modal_toast_and_pagination_components_render(): void
    {
        $html = Blade::render(
            <<<'BLADE'
            <x-admin-form-group name="title" label="Title" value="Hello" required autocomplete="off" :maxlength="120" :minlength="3" pattern="[A-Za-z0-9_-]+" invalid-feedback="Title is required." />
            <x-admin-alert type="success">Saved</x-admin-alert>
            <x-admin-modal id="sample-modal" title="Sample modal">Modal body</x-admin-modal>
            <x-admin-toast id="sample-toast" type="success">Toast body</x-admin-toast>
            BLADE,
            [],
            deleteCachedView: true,
        );

        $this->assertStringContainsString('form-label required', $html);
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('value="Hello"', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('maxlength="120"', $html);
        $this->assertStringContainsString('minlength="3"', $html);
        $this->assertStringContainsString('pattern="[A-Za-z0-9_-]+"', $html);
        $this->assertStringContainsString('invalid-feedback', $html);
        $this->assertStringContainsString('Title is required.', $html);
        $this->assertStringContainsString('alert alert-success', $html);
        $this->assertStringContainsString('modal modal-blur fade', $html);
        $this->assertStringContainsString('toast show', $html);
    }

    public function test_admin_editor_component_renders_tinymce_textarea(): void
    {
        $html = Blade::render(
            <<<'BLADE'
            <x-admin-editor name="content" label="Content" value="Draft body" :height="480" placeholder="Tell the story..." hint="Markdown is not supported." />
            BLADE,
            [],
            deleteCachedView: true,
        );

        $this->assertStringContainsString('id="content"', $html);
        $this->assertStringContainsString('name="content"', $html);
        $this->assertStringContainsString('data-admin-editor', $html);
        $this->assertStringContainsString('data-admin-editor-height="480"', $html);
        $this->assertStringContainsString('data-admin-editor-placeholder="Tell the story..."', $html);
        $this->assertStringContainsString('Draft body', $html);
        $this->assertStringContainsString('form-label', $html);
        $this->assertStringContainsString('form-hint', $html);
    }

    public function test_admin_editor_component_is_registered_through_admin_editor_alias(): void
    {
        $this->assertSame(Editor::class, Blade::getClassComponentAliases()['admin-editor']);

        $html = Blade::render('<x-admin-editor name="excerpt" value="Short summary" />', [], deleteCachedView: true);

        $this->assertStringContainsString('id="excerpt"', $html);
        $this->assertStringContainsString('data-admin-editor', $html);
        $this->assertStringContainsString('Short summary', $html);
    }
}
