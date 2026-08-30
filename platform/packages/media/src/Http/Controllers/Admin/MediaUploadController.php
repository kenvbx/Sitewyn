<?php

namespace Sitewyn\Packages\Media\Http\Controllers\Admin;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Sitewyn\Packages\Media\Http\Requests\Admin\UploadMediaRequest;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Repositories\MediaFileRepository;
use Sitewyn\Packages\Media\Support\ImageConversionGenerator;
use Sitewyn\Packages\Media\Support\MediaStorage;
use Sitewyn\Packages\Media\Support\RemoteUrlGuard;
use Sitewyn\Packages\Media\Support\UnsafeUrlException;
use Symfony\Component\Mime\MimeTypes;

class MediaUploadController extends Controller
{
    private const MAX_REDIRECTS = 5;

    private const STREAM_CHUNK_SIZE = 65536;

    public function __construct(
        private readonly MediaFileRepository $files,
        private readonly ImageConversionGenerator $conversions,
        private readonly MediaStorage $storage,
        private readonly RemoteUrlGuard $urlGuard,
    ) {}

    public function __invoke(UploadMediaRequest $request): JsonResponse
    {
        $disk = $this->storage->diskName();
        $directory = $this->storage->uploadDirectory();
        $temporaryPaths = [];
        $uploadedFiles = $request->uploadedFiles();

        try {
            foreach ($request->uploadUrls() as $index => $url) {
                $uploadedFiles[] = $this->downloadUrl($url, $request, $temporaryPaths, $index + 1);
            }

            $records = collect($uploadedFiles)
                ->map(fn (UploadedFile $file): MediaFile => $this->storeFile(
                    file: $file,
                    disk: $disk,
                    directory: $directory,
                    folderId: $request->integer('folder_id') ?: null,
                ));
        } finally {
            foreach ($temporaryPaths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        return response()->json([
            'files' => $records->map(fn (MediaFile $file): array => $this->payload($file))->values(),
        ], 201);
    }

    /**
     * @param  array<int, string>  $temporaryPaths
     *
     * @throws ValidationException
     */
    private function downloadUrl(string $url, UploadMediaRequest $request, array &$temporaryPaths, int $position): UploadedFile
    {
        if (Validator::make(['url' => $url], ['url' => ['required', 'url', 'max:2048']])->fails()) {
            throw ValidationException::withMessages([
                'upload_urls' => __('Line :line must be a valid URL.', ['line' => $position]),
            ]);
        }

        $maxBytes = (int) config('media.max_upload_size', 10240) * 1024;
        $temporaryPath = tempnam(storage_path('app'), 'media-url-');

        if ($temporaryPath === false) {
            throw ValidationException::withMessages([
                'upload_urls' => __('Line :line could not be prepared for upload.', ['line' => $position]),
            ]);
        }

        $temporaryPaths[] = $temporaryPath;

        try {
            [$mimeType, $finalUrl] = $this->downloadToTempFile($url, $temporaryPath, $maxBytes, $position);
        } catch (UnsafeUrlException) {
            throw ValidationException::withMessages([
                'upload_urls' => __('The URL points to a forbidden host.'),
            ]);
        }

        if ($mimeType === null) {
            throw ValidationException::withMessages([
                'upload_urls' => __('Line :line could not be downloaded.', ['line' => $position]),
            ]);
        }

        $uploadedFile = new UploadedFile($temporaryPath, $this->filenameFromUrl($finalUrl, $mimeType), $mimeType, null, true);

        $validator = Validator::make(['file' => $uploadedFile], [
            'file' => $request->fileRules(),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'upload_urls' => __('Line :line is not an allowed file.', ['line' => $position]),
            ]);
        }

        return $uploadedFile;
    }

    /**
     * Download the URL into the given temp file, streaming the body instead of
     * buffering it, and re-validating every redirect hop against the URL guard.
     *
     * @return array{0: string|null, 1: string} The mime type (null when the
     *                                          download failed) and the final URL after redirects.
     *
     * @throws UnsafeUrlException
     * @throws ValidationException
     */
    private function downloadToTempFile(string $url, string $temporaryPath, int $maxBytes, int $position): array
    {
        $currentUrl = $url;
        $redirects = 0;

        while (true) {
            $this->urlGuard->assertSafe($currentUrl);

            $response = Http::timeout(15)
                ->accept('*/*')
                ->withOptions(['allow_redirects' => false, 'stream' => true])
                ->get($currentUrl);

            if ($response->redirect()) {
                if ($redirects >= self::MAX_REDIRECTS) {
                    throw ValidationException::withMessages([
                        'upload_urls' => __('Line :line has too many redirects.', ['line' => $position]),
                    ]);
                }

                $location = $response->header('Location');

                if ($location === '') {
                    return [null, $currentUrl];
                }

                $currentUrl = $this->absoluteUrl($currentUrl, $location);
                $redirects++;

                continue;
            }

            if (! $response->successful()) {
                return [null, $currentUrl];
            }

            return [$this->streamBody($response, $temporaryPath, $maxBytes, $position), $currentUrl];
        }
    }

    /**
     * Stream the response body to the temp file in chunks, aborting as soon as
     * the size limit is exceeded so oversized bodies never fully hit the RAM.
     *
     * @return string|null The response mime type, null when the body was empty.
     *
     * @throws ValidationException
     */
    private function streamBody(Response $response, string $temporaryPath, int $maxBytes, int $position): ?string
    {
        $mimeType = (string) Str::of((string) $response->header('Content-Type'))->before(';')->trim();
        $handle = fopen($temporaryPath, 'wb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'upload_urls' => __('Line :line could not be prepared for upload.', ['line' => $position]),
            ]);
        }

        $bytes = 0;

        try {
            $body = $response->toPsrResponse()->getBody();

            while (! $body->eof()) {
                $chunk = $body->read(self::STREAM_CHUNK_SIZE);

                if ($chunk === '') {
                    break;
                }

                $bytes += strlen($chunk);

                if ($bytes > $maxBytes) {
                    throw ValidationException::withMessages([
                        'upload_urls' => __('Line :line may not be greater than :max kilobytes.', [
                            'line' => $position,
                            'max' => (int) config('media.max_upload_size', 10240),
                        ]),
                    ]);
                }

                if (fwrite($handle, $chunk) === false) {
                    throw ValidationException::withMessages([
                        'upload_urls' => __('Line :line could not be prepared for upload.', ['line' => $position]),
                    ]);
                }
            }
        } finally {
            fclose($handle);
        }

        if ($bytes === 0) {
            throw ValidationException::withMessages([
                'upload_urls' => __('Line :line could not be downloaded.', ['line' => $position]),
            ]);
        }

        return $mimeType !== '' ? $mimeType : null;
    }

    /**
     * Resolve a Location header against the URL it was received from.
     *
     * @throws UnsafeUrlException
     */
    private function absoluteUrl(string $base, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        try {
            return (string) UriResolver::resolve(new Uri($base), new Uri($location));
        } catch (InvalidArgumentException) {
            throw new UnsafeUrlException('The redirect target is not a valid URL.');
        }
    }

    private function filenameFromUrl(string $url, ?string $mimeType): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $filename = is_string($path) ? basename(urldecode($path)) : '';
        $filename = trim($filename);

        if ($filename !== '' && str_contains($filename, '.')) {
            return $filename;
        }

        $extension = $mimeType ? (MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? null) : null;

        return 'remote-file'.($extension ? '.'.$extension : '');
    }

    private function storeFile(UploadedFile $file, string $disk, string $directory, ?int $folderId): MediaFile
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $storedName = Str::slug($baseName) ?: 'file';
        $storedName .= '-'.Str::uuid()->toString();

        if ($extension !== '') {
            $storedName .= '.'.$extension;
        }

        $path = $this->storage->putFileAs($file, $storedName, $disk, $directory);
        [$width, $height] = $this->imageDimensions($file);
        $conversions = $this->conversions->generate($file, $disk, $path);

        return $this->files->create([
            'folder_id' => $folderId,
            'name' => $baseName,
            'file_name' => $originalName,
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
            'width' => $width,
            'height' => $height,
            'conversions' => $conversions,
        ]);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(UploadedFile $file): array
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return [null, null];
        }

        $dimensions = @getimagesize($file->getRealPath());

        return $dimensions === false ? [null, null] : [$dimensions[0], $dimensions[1]];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(MediaFile $file): array
    {
        return [
            'id' => $file->id,
            'folder_id' => $file->folder_id,
            'name' => $file->name,
            'file_name' => $file->file_name,
            'path' => $file->path,
            'disk' => $file->disk,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'width' => $file->width,
            'height' => $file->height,
            'conversions' => $file->conversions ?? [],
            'url' => $this->storage->url($file->disk, $file->path),
        ];
    }
}
