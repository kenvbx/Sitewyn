<?php

namespace Sitewyn\Core\Base\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Support\SitemapRegistry;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapRegistry $sitemaps) {}

    public function __invoke(): Response
    {
        return response()->view('core/base::sitemap', [
            'entries' => $this->sitemaps->entries(),
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
