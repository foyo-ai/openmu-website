<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuideController extends Controller
{
    public function index()
    {
        $guides = Guide::query()->orderBy('category')->ordered()->paginate(30);

        return view('admin.guides.index', compact('guides'));
    }

    public function create()
    {
        return view('admin.guides.form', ['guide' => new Guide()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = Guide::uniqueSlug($data['title_vi']);
        $data['author_account_id'] = $request->user()->Id;

        Guide::create($data);

        return redirect()->route('admin.guides.index')->with('alert-success', __('guide.saved'));
    }

    public function edit(Guide $guide)
    {
        return view('admin.guides.form', compact('guide'));
    }

    public function update(Request $request, Guide $guide)
    {
        $data = $this->validateData($request);
        $guide->update($data);

        return redirect()->route('admin.guides.index')->with('alert-success', __('guide.saved'));
    }

    public function destroy(Guide $guide)
    {
        $guide->delete();

        return redirect()->route('admin.guides.index')->with('alert-success', __('guide.deleted'));
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'category'    => ['required', Rule::in(Guide::CATEGORIES)],
            'class_key'   => ['nullable', 'string', 'max:40'],
            'icon'        => ['nullable', 'string', 'max:60'],
            'title_vi'    => ['required', 'string', 'max:200'],
            'title_en'    => ['nullable', 'string', 'max:200'],
            'body_vi'     => ['required', 'string'],
            'body_en'     => ['nullable', 'string'],
            'excerpt_vi'  => ['nullable', 'string', 'max:500'],
            'excerpt_en'  => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'sort_order'  => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
