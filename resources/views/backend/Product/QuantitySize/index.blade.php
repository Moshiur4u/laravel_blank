@extends('dashboard.dashboard')

@section('title', 'Qty/Size List')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex align-items-center">
                <div class="breadcrumb-title pe-3">Product Manager</div>
                <div class="ms-auto">
                    @can('quantity-size.create')
                        <a href="{{ route('quantity-size.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus-circle me-1"></i>Add Qty/Size
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card radius-10 border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="bx bx-ruler text-primary me-2"></i>Qty/Size Options</h5>
                    <p class="text-muted small mb-0">Manage reusable labels available on product forms.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Qty/Size</th>
                                    <th>Created</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($quantitySizes as $index => $quantitySize)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="fw-semibold">{{ $quantitySize->name }}</td>
                                        <td>{{ $quantitySize->created_at?->format('d M Y') }}</td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                @can('quantity-size.edit')
                                                    <a href="{{ route('quantity-size.edit', $quantitySize) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Edit Qty/Size">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </a>
                                                @endcan
                                                @can('quantity-size.delete')
                                                    <form action="{{ route('quantity-size.destroy', $quantitySize) }}"
                                                        method="POST" data-swal-delete
                                                        data-delete-message="Qty/Size: {{ $quantitySize->name }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            title="Delete Qty/Size">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No Qty/Size options found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0">
                        Deleting an option does not change Qty/Size text already saved on products.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
