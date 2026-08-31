{{-- Minimal test fixture theme (P5-02): a standalone home document like
     page.blade.php, so the home route renders through the active theme. --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Home</title>
</head>
<body>
  TEST-MARKER-HOME
  <h1>Home</h1>
  <ul>
    @foreach ($posts as $post)
      <li><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></li>
    @endforeach
    @foreach ($pages as $page)
      <li><a href="/{{ $page->slug }}">{{ $page->title }}</a></li>
    @endforeach
  </ul>
</body>
</html>
