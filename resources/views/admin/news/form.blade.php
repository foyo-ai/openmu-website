@extends('layouts.app')

@section('title', $news->exists ? __('news.edit_post') : __('news.new_post'))

@section('content')
    <div class="container py-5" style="max-width: 900px">
        <h1 class="mu-section-title mb-4">{{ $news->exists ? __('news.edit_post') : __('news.new_post') }}</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST"
              action="{{ $news->exists ? route('admin.news.update', $news) : route('admin.news.store') }}">
            @csrf
            @if($news->exists) @method('PUT') @endif

            <div class="card p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('news.title_vi') }}</label>
                        <input type="text" name="title_vi" class="form-control" required
                               value="{{ old('title_vi', $news->title_vi) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('news.title_en') }}</label>
                        <input type="text" name="title_en" class="form-control"
                               value="{{ old('title_en', $news->title_en) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('news.excerpt_vi') }}</label>
                        <input type="text" name="excerpt_vi" class="form-control" maxlength="500"
                               value="{{ old('excerpt_vi', $news->excerpt_vi) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('news.excerpt_en') }}</label>
                        <input type="text" name="excerpt_en" class="form-control" maxlength="500"
                               value="{{ old('excerpt_en', $news->excerpt_en) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('news.cover_image') }}</label>
                        <input type="url" name="cover_image" class="form-control"
                               value="{{ old('cover_image', $news->cover_image) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('news.body_vi') }}</label>
                        <textarea name="body_vi" rows="10" class="form-control" required>{{ old('body_vi', $news->body_vi) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('news.body_en') }}</label>
                        <textarea name="body_en" rows="10" class="form-control">{{ old('body_en', $news->body_en) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" class="form-check-input" id="pub"
                                   {{ old('is_published', $news->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pub">{{ __('news.published') }}</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">{{ __('news.save') }}</button>
                <a href="{{ route('admin.news.index') }}" class="btn btn-outline-light">{{ __('news.back') }}</a>
            </div>
        </form>
    </div>
@endsection
