<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Services\OpenMuStatusService;

class PageController extends Controller
{
    /**
     * Public landing / server-info page.
     */
    public function home(OpenMuStatusService $status)
    {
        $serverStatus = $status->status();

        $latestNews = News::query()
            ->published()
            ->latestFirst()
            ->limit(3)
            ->get();

        return view('welcome', [
            'serverStatus' => $serverStatus,
            'latestNews'   => $latestNews,
        ]);
    }
}
