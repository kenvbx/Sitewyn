<?php

namespace Sitewyn\Packages\Media\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
            'file' => array_merge(['required_without_all:files,upload_url,upload_urls', 'nullable'], $this->fileRules()),
            'files' => ['required_without_all:file,upload_url,upload_urls', 'array', 'min:1'],
            'files.*' => array_merge(['required'], $this->fileRules()),
            'upload_url' => ['required_without_all:file,files,upload_urls', 'nullable', 'url', 'max:2048'],
            'upload_urls' => ['required_without_all:file,files,upload_url', 'nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function uploadedFiles(): array
    {
        if ($this->hasFile('file')) {
            return [$this->file('file')];
        }

        return array_values($this->file('files', []));
    }

    /**
     * @return array<int, string>
     */
    public function uploadUrls(): array
    {
        $urls = [];

        if ($this->filled('upload_url')) {
            $urls[] = (string) $this->input('upload_url');
        }

        if ($this->filled('upload_urls')) {
            $urls = array_merge($urls, preg_split('/\R+/', (string) $this->input('upload_urls')) ?: []);
        }

        return collect($urls)
            ->map(fn (string $url): string => trim($url))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function fileRules(): array
    {
        return [
            'file',
            'max:'.(int) config('media.max_upload_size', 10240),
            'mimetypes:'.implode(',', config('media.allowed_mime_types', [])),
        ];
    }
}
