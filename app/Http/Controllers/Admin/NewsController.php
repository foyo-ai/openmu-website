<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::query()->orderByDesc('created_at')->paginate(20);

        return view('admin.news.index', compact('news'));
    }

    public function create()
    {
        return view('admin.news.form', ['news' => new News()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = News::uniqueSlug($data['title_vi']);
        $data['author_account_id'] = $request->user()->Id;
        $data['published_at'] = $data['is_published'] ? now() : null;

        News::create($data);

        return redirect()->route('admin.news.index')->with('alert-success', __('news.saved'));
    }

    public function edit(News $news)
    {
        return view('admin.news.form', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $data = $this->validateData($request);
        // Stamp published_at the first time it goes live; keep it once set.
        if ($data['is_published'] && ! $news->published_at) {
            $data['published_at'] = now();
        }

        $news->update($data);

        return redirect()->route('admin.news.index')->with('alert-success', __('news.saved'));
    }

    public function destroy(News $news)
    {
        $news->delete();

        return redirect()->route('admin.news.index')->with('alert-success', __('news.deleted'));
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title_vi'   => ['required', 'string', 'max:200'],
            'title_en'   => ['nullable', 'string', 'max:200'],
            'body_vi'    => ['required', 'string'],
            'body_en'    => ['nullable', 'string'],
            'excerpt_vi' => ['nullable', 'string', 'max:500'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
        ]);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
