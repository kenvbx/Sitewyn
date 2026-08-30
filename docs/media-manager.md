# Media Manager

P2 starts the reusable media package at:

```text
platform/packages/media
```

The package is registered through its own `composer.json` provider metadata and
is discovered by the core base module provider scanner.

## Schema

P2-01 adds two tables.

`media_folders` stores the folder tree:

- `id`
- `parent_id`: nullable self-reference for nested folders.
- `name`
- `slug`: unique within the same parent folder.
- `path`: nullable normalized folder path for later lookup.
- `sort_order`
- `created_at`, `updated_at`

`media_files` stores uploaded file records:

- `id`
- `folder_id`: nullable reference to `media_folders` for root-level files.
- `name`: display name.
- `file_name`: original/current file name.
- `path`: unique storage path.
- `disk`: Laravel filesystem disk, default `public`.
- `mime_type`
- `size`: file size in bytes.
- `width`, `height`: nullable image dimensions.
- `alt_text`
- `created_at`, `updated_at`

The schema intentionally keeps `folder_id` nullable and stores `disk` on each
file so later upload work can use Laravel's filesystem abstraction and prepare
for non-local storage drivers.

## Storage Abstraction

P2-09 centralizes filesystem behavior in
`Sitewyn\Packages\Media\Support\MediaStorage`.

Config values:

```php
'disk' => env('MEDIA_DISK', 'public'),
'upload_directory_format' => env('MEDIA_UPLOAD_DIRECTORY_FORMAT', 'Y/m'),
```

`.env.example` exposes:

```text
MEDIA_DISK=public
MEDIA_UPLOAD_DIRECTORY_FORMAT=Y/m
```

All media writes, deletes, and URL generation now go through Laravel's
filesystem disk configured by `MEDIA_DISK`. The upload controller asks
`MediaStorage` for the active disk and upload directory, image conversions use
the same disk abstraction, payload generation resolves URLs through
`MediaStorage`, and delete actions remove originals/conversions through the
stored disk name on each record.

The local public disk still renders relative `/storage/...` URLs for reliable
local admin previews across dev-server ports. Custom local disks with a `url`
setting, and remote disks such as S3, use `Storage::disk($disk)->url($path)`.
This keeps file records portable because each `media_files` row stores both the
disk and path instead of an absolute local filesystem path.

## Next Steps

- P2-11: add broader media workflow tests.

## Models And Repositories

P2-02 adds Eloquent models:

- `Sitewyn\Packages\Media\Models\MediaFolder`
- `Sitewyn\Packages\Media\Models\MediaFile`

Folder relationships:

- `parent()`
- `children()`
- `files()`

File relationships:

- `folder()`

Repository classes:

- `Sitewyn\Packages\Media\Repositories\MediaFolderRepository`
- `Sitewyn\Packages\Media\Repositories\MediaFileRepository`

Storage support:

- `Sitewyn\Packages\Media\Support\MediaStorage`

`MediaFolderRepository` supports:

- `allForSelect()`
- `childrenOf(?int $parentId = null)`
- `searchByName(string $term, ?int $parentId = null)`
- `findByPath(?string $path)`
- `create(array $attributes)`
- `rename(MediaFolder $folder, string $name)`
- `move(MediaFolder $folder, ?int $parentId)`
- `deleteTree(MediaFolder $folder, MediaFileRepository $files)`
- `isDescendantOf(MediaFolder $folder, MediaFolder $ancestor)`

`MediaFileRepository` supports:

- `inFolder(?int $folderId = null)`
- `search(string $term, ?int $folderId = null)`
- `findByPath(string $path)`
- `create(array $attributes)`
- `rename(MediaFile $file, string $name)`
- `move(MediaFile $file, ?int $folderId)`
- `deleteWithFiles(MediaFile $file)`

Repositories keep query behavior in one place for upload endpoints and the
future `/admin/media` grid.

## Upload API

P2-03 adds the first admin upload endpoint:

```text
POST /admin/media/upload
```

The route uses the admin guard. P2-10 registers the media permissions through
`PermissionRegistry` and gates the routes with the permission middleware, so
uploading requires `media.upload`.

Supported multipart payloads:

```text
file=<single uploaded file>
folder_id=<optional media_folders.id>
```

or:

```text
files[]=<uploaded file>
files[]=<uploaded file>
folder_id=<optional media_folders.id>
```

Uploads are validated against `media.allowed_mime_types` and
`media.max_upload_size`, saved to `Storage::disk(config('media.disk'))` under a
`Y/m` directory, and persisted through `MediaFileRepository`.

Successful uploads return `201 Created`:

```json
{
    "files": [
        {
            "id": 1,
            "folder_id": null,
            "name": "Hero Banner",
            "file_name": "Hero Banner.jpg",
            "path": "2026/08/hero-banner-uuid.jpg",
            "disk": "public",
            "mime_type": "image/jpeg",
            "size": 524288,
            "width": 1200,
            "height": 630,
            "url": "/storage/2026/08/hero-banner-uuid.jpg"
        }
    ]
}
```

The upload code uses Laravel's filesystem abstraction and does not depend on a
hardcoded local storage path.

## Image Conversions

P2-04 adds automatic image conversions using `intervention/image`.

Raster image uploads create:

- `thumb`: `150x150` cover crop.
- `medium`: scale down to `768px` wide while preserving aspect ratio.

Conversion metadata is stored on `media_files.conversions`:

```json
{
    "thumb": {
        "path": "2026/08/conversions/hero-banner-uuid-thumb.jpg",
        "disk": "public",
        "width": 150,
        "height": 150,
        "url": "/storage/2026/08/conversions/hero-banner-uuid-thumb.jpg"
    },
    "medium": {
        "path": "2026/08/conversions/hero-banner-uuid-medium.jpg",
        "disk": "public",
        "width": 768,
        "height": 403,
        "url": "/storage/2026/08/conversions/hero-banner-uuid-medium.jpg"
    }
}
```

Non-image uploads keep `conversions` empty. Conversion sizes are configured in
`media.image_conversions`.

## Admin Grid View

P2-05 adds the first admin browsing interface:

```text
GET /admin/media
POST /admin/media/folders
```

The page extends the shared Tabler admin master layout, so it uses the same
combined sidebar/topbar as Users, Roles, Permissions, and Settings. The media
package registers a top-level `Media` sidebar item through `AdminMenuRegistry`.

The grid view supports:

- root and folder-specific browsing through the `folder` query parameter.
- breadcrumb navigation for nested folders.
- folder cards before file cards.
- image thumbnails from `media_files.conversions.thumb.url`.
- generic file cards for non-image files.
- search within the current folder.
- a Tabler modal for creating folders under the current folder.

P2-10 adds media-specific permissions. The page is available to users with
`media.index`; action buttons and forms are only rendered when the current admin
has the matching action permission.

## Dropzone Uploads

P2-06 adds upload actions through the Media Manager page action bar. The page
shows an `Upload` button next to `New folder`; clicking it opens a Tabler modal
with two tabs:

- `Upload from local`: drag-and-drop upload with Dropzone.
- `Upload from URL`: download one or more remote file URLs, validate them, and
  save them into the current folder. The textarea accepts one URL per line.

The Dropzone markup and assets are copied from the local Tabler source:

```text
/Volumes/WORKSPACE/PROJECT/HTML/tabler-dev/core/dist/libs/dropzone
```

Published assets live under:

```text
public/vendor/tabler/dist/libs/dropzone
```

The upload form posts to:

```text
POST /admin/media/upload
```

Dropzone is configured with `paramName: file` and `uploadMultiple: false`, so
each file is sent as a normal single-file upload while the UI can still queue
multiple selected files. When the user is browsing a folder, the current
`folder_id` is appended to each upload request. After successful queued uploads
finish, the page reloads so the grid shows the new media cards in the current
folder.

For the local `public` disk, media URLs are rendered as relative `/storage/...`
paths through `MediaStorage`. This avoids broken images when the app runs on a
different local port than `APP_URL`, while non-local disks still use the
configured filesystem URL.

## Media Picker

P2-07 adds a reusable Media Picker field for future Page and Blog forms:

```blade
<x-media-picker name="featured_image_id" label="Featured image" />
```

The component renders:

- a hidden media ID input.
- a hidden media URL input named `{name}_url` by default.
- a selected-media preview with choose and clear actions.
- a Tabler modal with search, breadcrumb folder navigation, folder cards, file
  cards, and a `Use selected media` action.

The picker loads media through:

```text
GET /admin/media/picker
```

The JSON payload includes the current folder, breadcrumbs, child folders, and
files with normalized `url`, `thumbnail`, `dimensions`, and `is_image` values.
This keeps the picker decoupled from the full Media Manager page so other
admin forms can reuse it without duplicating media query logic.

## Media Actions

P2-08 adds item actions to the Media Manager grid:

```text
PATCH /admin/media/files/{file}
DELETE /admin/media/files/{file}
PATCH /admin/media/folders/{folder}
DELETE /admin/media/folders/{folder}
```

Each file and folder card now has a Tabler dropdown menu with:

- `Rename`
- `Move`
- `Delete`

Rename and move actions open Tabler modals and submit normal Laravel forms. Move
supports sending files or folders back to the root library. Folder moves refresh
the folder path and every descendant path, and the backend blocks moving a
folder into itself or one of its descendants.

Deleting a file removes the database record, the original storage file, and any
stored conversion paths. Deleting a folder recursively removes child folders and
their files from storage before removing the folder rows.

## Media Permissions

P2-10 registers Media Manager permissions through `PermissionRegistry` from the
media package service provider:

- `media.index`: view the media library and picker endpoint.
- `media.upload`: upload files and create folders.
- `media.edit`: rename or move files and folders.
- `media.delete`: delete files and folders.

The media sidebar item requires `media.index`, so admins without media access do
not see it. Routes are protected with the same permission map:

```text
GET /admin/media                     media.index
GET /admin/media/picker              media.index
POST /admin/media/upload             media.upload
POST /admin/media/folders            media.upload
PATCH /admin/media/files/{file}      media.edit
PATCH /admin/media/folders/{folder}  media.edit
DELETE /admin/media/files/{file}     media.delete
DELETE /admin/media/folders/{folder} media.delete
```

Run `php artisan permission:sync` after deploying module permission changes so
the database roles can assign the new Media permissions.

## Security

### Upload from URL (SSRF)

`POST /admin/media/upload` with `upload_url`/`upload_urls` is protected against
SSRF by `Sitewyn\Packages\Media\Support\RemoteUrlGuard`. Before any request is
sent (and again for every redirect hop), the guard rejects:

- URLs whose scheme is not `http`/`https` (e.g. `file://`, `ftp://`).
- Hosts that cannot be resolved via DNS (fail closed).
- Hosts resolving into forbidden ranges: `127.0.0.0/8`, `10.0.0.0/8`,
  `172.16.0.0/12`, `192.168.0.0/16`, `169.254.0.0/16` (cloud metadata service),
  `0.0.0.0/8`, `100.64.0.0/10`, `::1`, `fc00::/7`, `fe80::/10`. IPv4-mapped
  IPv6 literals (`::ffff:10.0.0.5`) are normalized and checked against the
  IPv4 list too.

Redirects are followed manually (Guzzle `allow_redirects => false`), at most 5
hops, so each hop can be validated before it is fetched. A redirect to a
forbidden host fails the upload with 422 `The URL points to a forbidden host.`
instead of leaking the request.

Bodies are streamed to a temp file in 64 KB chunks and the download aborts as
soon as `media.max_upload_size` is exceeded, so an oversized remote file is
never fully buffered in memory.

### File type hardening (stored XSS)

- `image/svg+xml` is intentionally NOT in `media.allowed_mime_types`: SVG can
  embed scripts that execute same-origin when served back to admins. If SVG
  support is needed later, integrate a sanitizer (e.g. `enshrined/svg-sanitize`)
  before re-enabling it.
- An extension denylist in `UploadMediaRequest::fileRules()` rejects
  client-supplied extensions such as `html`, `htm`, `xhtml`, `shtml`, `svg`,
  `svgz`, `php`, `phtml`, `php3`–`php8`, `asp`, `aspx`, `jsp`, `js`, `mjs`,
  `exe`, `dll`, `sh`, `bat`, `cmd`, `ps1`, `vbs`, `cgi`, `pl`, `py` — even when
  the detected mime type is allowed (e.g. `text/plain` disguised as `.html`).
  The rule applies to multipart uploads and URL downloads alike, because both
  paths validate through `fileRules()`.

### Serving stored files

Stored files are served from `/storage` (or the configured disk). The
application does not set serving headers; the web server in front of
`/storage` should enforce:

```nginx
location /storage {
    add_header X-Content-Type-Options nosniff always;
    # Force download for types browsers may execute:
    add_header Content-Disposition "attachment" always;
    try_files $uri =404;
}
```

At minimum set `X-Content-Type-Options: nosniff` for `/storage` and keep the
denylist above in place so executable formats are never stored.

## Editor Bridge

The admin media picker also listens for the `admin:editor-file-picker` event
documented by the core `x-admin-editor` component. When the editor opens its
image or file picker, the first `[data-media-picker]` instance on the page is
opened in a modal: the listener marks `detail.handled = true` synchronously,
and choosing a file calls `detail.callback(url, { alt: name })` before closing
the modal. The standalone picker inputs, preview, and permissions are
unchanged — the bridge never writes to the hidden inputs of the form picker.
