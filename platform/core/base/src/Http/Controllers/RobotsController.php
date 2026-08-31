<?php

namespace Sitewyn\Core\Base\Http\Controllers;

use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Support\RobotsTxt;
use Sitewyn\Core\Base\Support\SettingStore;
use Symfony\Component\HttpFoundation\Response;

class RobotsController extends Controller
{
    public function __construct(private readonly SettingStore $settings) {}

    public function __invoke(): Response
    {
        return response(RobotsTxt::content($this->settings->get('robots_txt')))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
