<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Facades\Cache;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class SitemapController extends Controller
{
    /**
     * On-the-fly sitemap covering static pages (both locales) + published news.
     */
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', 600, function () {
            $urls = [];

            // Static localized pages
            foreach (['home', 'rankings.index', 'news.index'] as $name) {
                foreach (LaravelLocalization::getSupportedLocales() as $code => $props) {
                    $urls[] = [
                        'loc' => LaravelLocalization::getLocalizedURL($code, route($name)),
                        'changefreq' => 'daily',
                        'priority' => $name === 'home' ? '1.0' : '0.7',
                    ];
                }
            }

            // Published news
            foreach (News::query()->published()->latestFirst()->get() as $post) {
                foreach (LaravelLocalization::getSupportedLocales() as $code => $props) {
                    $urls[] = [
                        'loc' => LaravelLocalization::getLocalizedURL($code, route('news.show', $post)),
                        'lastmod' => optional($post->updated_at)->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ];
                }
            }

            return view('sitemap', compact('urls'))->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
