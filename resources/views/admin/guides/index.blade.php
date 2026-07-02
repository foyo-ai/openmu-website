@extends('layouts.app')

@section('title', __('guide.manage'))

@section('content')
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mu-section-title mb-0">{{ __('guide.manage') }}</h1>
            <a href="{{ route('admin.guides.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('guide.new_post') }}
            </a>
        </div>

        <div class="card p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th class="ps-3">{{ __('guide.title_vi') }}</th>
                        <th>{{ __('guide.category') }}</th>
                        <th>{{ __('guide.sort_order') }}</th>
                        <th>{{ __('guide.status') }}</th>
                        <th class="text-end pe-3">{{ __('guide.actions') }}</th>
                    </tr></thead>
                    <tbody>
                    @forelse($guides as $g)
                        <tr>
                            <td class="ps-3 fw-semibold">
                                @if($g->icon)<i class="fa-solid fa-{{ $g->icon }} me-1 text-warning"></i>@endif
                                {{ $g->title_vi }}
                            </td>
                            <td><span class="badge bg-secondary">{{ __('guide.cat_' . $g->category) }}</span></td>
                            <td class="text-muted">{{ $g->sort_order }}</td>
                            <td>
                                @if($g->is_published)
                                    <span class="badge bg-success">{{ __('guide.published') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('guide.draft') }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.guides.edit', $g) }}" class="btn btn-sm btn-outline-light">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.guides.destroy', $g) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('guide.delete_confirm') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('guide.empty') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $guides->links() }}</div>
    </div>
@endsection
