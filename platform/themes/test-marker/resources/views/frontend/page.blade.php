{{-- Minimal test fixture theme (P5-02): a complete standalone document so it
     proves theme switching without sharing the default theme's layout. --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>{{ $page->title }}</title>
</head>
<body>
  TEST-MARKER-VIEW
  <h1>{{ $page->title }}</h1>
</body>
</html>
