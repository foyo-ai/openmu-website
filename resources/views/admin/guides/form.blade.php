@extends('layouts.app')

@section('title', $guide->exists ? __('guide.edit_post') : __('guide.new_post'))

@section('content')
    <div class="container py-5" style="max-width: 960px">
        <h1 class="mu-section-title mb-4">{{ $guide->exists ? __('guide.edit_post') : __('guide.new_post') }}</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST"
              action="{{ $guide->exists ? route('admin.guides.update', $guide) : route('admin.guides.store') }}">
            @csrf
            @if($guide->exists) @method('PUT') @endif

            <div class="card p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('guide.category') }}</label>
                        <select name="category" class="form-select" required>
                            @foreach(\App\Models\Guide::CATEGORIES as $cat)
                                <option value="{{ $cat }}" @selected(old('category', $guide->category) === $cat)>{{ __('guide.cat_' . $cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('guide.class_key') }}</label>
                        <input type="text" name="class_key" class="form-control"
                               value="{{ old('class_key', $guide->class_key) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('guide.icon') }}</label>
                        <input type="text" name="icon" class="form-control"
                               value="{{ old('icon', $guide->icon) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('guide.sort_order') }}</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="{{ old('sort_order', $guide->sort_order ?? 0) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('guide.title_vi') }}</label>
                        <input type="text" name="title_vi" class="form-control" required
                               value="{{ old('title_vi', $guide->title_vi) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('guide.title_en') }}</label>
                        <input type="text" name="title_en" class="form-control"
                               value="{{ old('title_en', $guide->title_en) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('guide.excerpt_vi') }}</label>
                        <input type="text" name="excerpt_vi" class="form-control" maxlength="500"
                               value="{{ old('excerpt_vi', $guide->excerpt_vi) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('guide.excerpt_en') }}</label>
                        <input type="text" name="excerpt_en" class="form-control" maxlength="500"
                               value="{{ old('excerpt_en', $guide->excerpt_en) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('guide.cover_image') }}</label>
                        <input type="url" name="cover_image" class="form-control"
                               value="{{ old('cover_image', $guide->cover_image) }}">
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info py-2 small mb-1">{{ __('guide.html_hint') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('guide.body_vi') }}</label>
                        <textarea name="body_vi" rows="20" class="form-control font-monospace" style="font-size:.85rem" required>{{ old('body_vi', $guide->body_vi) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('guide.body_en') }}</label>
                        <textarea name="body_en" rows="20" class="form-control font-monospace" style="font-size:.85rem">{{ old('body_en', $guide->body_en) }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" class="form-check-input" id="pub"
                                   {{ old('is_published', $guide->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pub">{{ __('guide.published') }}</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">{{ __('guide.save') }}</button>
                <a href="{{ route('admin.guides.index') }}" class="btn btn-outline-light">{{ __('guide.back') }}</a>
            </div>
        </form>
    </div>
@endsection
