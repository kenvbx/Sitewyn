<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_tables_are_created(): void
    {
        $this->assertTrue(Schema::hasTable('media_folders'));
        $this->assertTrue(Schema::hasTable('media_files'));
    }

    public function test_media_folders_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('media_folders', [
            'id',
            'parent_id',
            'name',
            'slug',
            'path',
            'sort_order',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_media_files_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('media_files', [
            'id',
            'folder_id',
            'name',
            'file_name',
            'path',
            'disk',
            'mime_type',
            'size',
            'width',
            'height',
            'conversions',
            'alt_text',
            'created_at',
            'updated_at',
        ]));
    }
}
