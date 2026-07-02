<?php

namespace App\Http\Controllers;

use App\Models\Guide;

class GuideController extends Controller
{
    public function index()
    {
        // One query, grouped by category for the sectioned index page.
        $guides = Guide::query()->published()->ordered()->get()->groupBy('category');

        return view('guides.index', compact('guides'));
    }

    public function show(Guide $guide)
    {
        abort_unless($guide->is_published, 404);

        // Sibling guides in the same category, for the "explore more" side nav.
        $siblings = Guide::query()->published()->category($guide->category)->ordered()
            ->where('id', '!=', $guide->id)->get();

        return view('guides.show', compact('guide', 'siblings'));
    }
}
