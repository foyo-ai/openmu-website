@extends('layouts.app')

@section('title', __('news.manage'))

@section('content')
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mu-section-title mb-0">{{ __('news.manage') }}</h1>
            <a href="{{ route('admin.news.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('news.new_post') }}
            </a>
        </div>

        <div class="card p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th class="ps-3">{{ __('news.title_vi') }}</th>
                        <th>{{ __('news.status') }}</th>
                        <th>{{ __('news.created_at') }}</th>
                        <th class="text-end pe-3">{{ __('news.actions') }}</th>
                    </tr></thead>
                    <tbody>
                    @forelse($news as $post)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $post->title_vi }}</td>
                            <td>
                                @if($post->is_published)
                                    <span class="badge bg-success">{{ __('news.published') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('news.draft') }}</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $post->created_at?->format('d/m/Y') }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.news.edit', $post) }}" class="btn btn-sm btn-outline-light">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.news.destroy', $post) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('news.delete_confirm') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('news.empty') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $news->links() }}</div>
    </div>
@endsection
