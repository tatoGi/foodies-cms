@extends('admin.layouts.app')

@section('title', __('Products'))
@section('page_title', __('Products'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Products Management') }}</h2>
            <p class="text-muted mb-0">{{ __('Create and manage your products here.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2 flex-wrap">
            <button type="button"
                class="btn btn-outline-secondary px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-arrow-up"></i>
                <span>{{ __('Import Excel') }}</span>
            </button>
            <a href="{{ route('admin.products.create') }}"
                class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>{{ __('Create New Product') }}</span>
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6">
            <form action="{{ route('admin.products.index') }}" method="GET" class="d-flex gap-2">
                <input type="search" name="q" value="{{ $search }}" class="form-control form-control-lg"
                    placeholder="{{ __('Search by name, ID or SKU...') }}">
                <button type="submit" class="btn btn-primary px-4">{{ __('Search') }}</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('import_imported') || session()->has('import_updated'))
        @php
            $importedCount = session('import_imported', 0);
            $updatedCount = session('import_updated', 0);
            $importErrors = session('import_errors', []);
            $total = $importedCount + $updatedCount;
        @endphp
        <div
            class="alert border-0 shadow-sm rounded-3 mb-4 {{ $importErrors ? 'alert-warning' : 'alert-success' }} p-0 overflow-hidden">
            <div class="px-4 py-3 d-flex align-items-center gap-3">
                <i class="bi {{ $importErrors ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }} fs-5"></i>
                <div>
                    <div class="fw-semibold">
                        {{ __('Import complete') }} —
                        @if($importedCount > 0)
                            <span class="text-success">{{ $importedCount }} {{ __('created') }}</span>@if($updatedCount > 0),@endif
                        @endif
                        @if($updatedCount > 0)
                            <span class="text-primary">{{ $updatedCount }} {{ __('updated') }}</span>
                        @endif
                        @if($total === 0)
                            <span class="text-muted">{{ __('no rows processed') }}</span>
                        @endif
                    </div>
                    @if($importErrors)
                        <div class="small mt-1 text-muted">{{ count($importErrors) }} {{ __('row(s) had errors (see below)') }}
                        </div>
                    @endif
                </div>
            </div>
            @if($importErrors)
                <div class="border-top px-4 py-3" style="background:rgba(0,0,0,.03)">
                    <div class="small fw-semibold mb-2 text-danger">{{ __('Rows with errors:') }}</div>
                    <ul class="mb-0 ps-3 small text-muted">
                        @foreach($importErrors as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <div id="reorder-feedback" class="d-none mb-3"></div>

    <div class="dashboard-panel premium-shadow overflow-hidden">
        <div class="panel-body p-0">
            <div class="table-responsive" data-mobile-columns="true">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Cover') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Product') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">
                                {{ __('SKU') }}
                            </th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">
                                {{ __('Price') }}
                            </th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">
                                {{ __('Sale Price') }}
                            </th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">
                                {{ __('Category') }}
                            </th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">
                                {{ __('Stock') }}
                            </th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 text-center">
                                {{ __('Status') }}
                            </th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody id="products-sortable-list">

                        @forelse($products as $product)

                            @php
                                $preferredTranslation = $product->translations->firstWhere('locale', $currentLocale);
                                $fallbackTranslation = $product->translations->first();
                                $title = $preferredTranslation?->title ?? $fallbackTranslation?->title ?? '#' . $product->id;
                                $slug = $preferredTranslation?->slug ?? $fallbackTranslation?->slug ?? '-';
                            @endphp
                            <tr data-id="{{ $product->id }}">
                                <td class="py-3" style="width:56px;">
                                    @if($product->cover_image)
                                        <img src="{{ asset('storage/' . $product->cover_image) }}" alt="" class="rounded-2"
                                            style="width:48px;height:48px;object-fit:cover;">
                                    @else
                                        <div class="rounded-2 bg-light-soft d-flex align-items-center justify-content-center"
                                            style="width:48px;height:48px;">
                                            <i class="bi bi-box-seam text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-4">
                                    <div>
                                        <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                            {{ $title }}
                                            @if($product->is_featured)
                                                <span class="badge bg-warning text-dark" title="{{ __('Featured') }}"
                                                    style="font-size:0.65rem;letter-spacing:0.04em;">
                                                    <i class="bi bi-star-fill me-1"></i>VIP
                                                </span>
                                            @endif
                                        </h6>
                                        <span class="small text-muted">{{ __('ID') }}: {{ $product->id }}</span>
                                    </div>
                                </td>
                                <td class="py-4 table-col-secondary"><code>{{ $product->sku }}</code></td>
                                <td class="py-4 table-col-secondary fw-bold text-success">
                                    ₾{{ number_format((float) $product->price, 2) }}</td>
                                <td class="py-4 table-col-secondary fw-bold text-danger">
                                    @if($product->sale_price)
                                        ₾{{ number_format((float) $product->sale_price, 2) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-4 table-col-secondary">
                                    @if($product->category)
                                        <span class="badge-soft badge-secondary">{{ $product->category }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-4 table-col-secondary">{{ number_format((int) $product->stock) }}</td>
                                <td class="py-4 text-center">
                                    @if($product->published)
                                        <span class="badge-soft badge-success">{{ __('Published') }}</span>
                                    @else
                                        <span class="badge-soft badge-secondary">{{ __('Draft') }}</span>
                                    @endif
                                </td>
                                <td class="pe-4 py-4 text-end">
                                    <div class="table-actions-inline d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                            class="btn btn-icon btn-light-soft" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                            onsubmit="return confirm('{{ __('Are you sure you want to delete this product?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-light-soft text-danger"
                                                title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-5 text-center text-muted">
                                    <i class="bi bi-box-seam h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No products found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-light-soft border-top">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="importModalLabel">
                        <i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i>{{ __('Import Products') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    @if($errors->has('file'))
                        <div class="alert alert-danger border-0 rounded-3 small py-2">{{ $errors->first('file') }}</div>
                    @endif
                    <p class="text-muted small mb-3">
                        {{ __('Upload an Excel or CSV file to bulk-import products. Existing SKUs will be updated.') }}
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('File') }} <span
                                class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">{{ __('Accepted formats: xlsx, xls, csv — max 5 MB') }}</div>
                    </div>
                    <div class="bg-light-soft rounded-3 p-3 small">
                        <div class="fw-semibold mb-2">{{ __('Required columns:') }}</div>
                        <code class="d-block text-muted">sku, price</code>
                        <div class="fw-semibold mt-2 mb-2">{{ __('Optional columns:') }}</div>
                        <code class="d-block text-muted"
                            style="word-break:break-all;">brand, sale_price, on_sale, category, stock, colors, is_active, is_featured, title_ka, title_en, excerpt_ka, excerpt_en</code>
                        <a href="{{ route('admin.products.sample-download') }}"
                            class="btn btn-link btn-sm p-0 mt-2 d-inline-flex align-items-center gap-1">
                            <i class="bi bi-download"></i> {{ __('Download sample file') }}
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-upload me-1"></i>{{ __('Import') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .table thead th {
            font-size: 0.7rem;
            background: var(--admin-surface-2);
            border-bottom: 2px solid var(--admin-border);
        }

        .letter-spacing-1 {
            letter-spacing: 0.05em;
        }

        .badge-soft.badge-success {
            background: var(--success-soft);
            color: var(--success);
        }

        .badge-soft.badge-secondary {
            background: var(--admin-surface-2);
            color: var(--admin-muted);
        }
    </style>
@endpush