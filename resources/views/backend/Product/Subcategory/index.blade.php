@extends('dashboard.dashboard')

@section('title', 'Subcategories')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Product Subcategories</h4>
                @can('product.subcategory.create')
                    <a href="{{ route('subcategories.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus-circle me-1"></i>Add Subcategory
                    </a>
                @endcan
            </div>
            <div class="card">
                <div class="table-responsive card-body">
                    <table class="table align-middle table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subcategory</th>
                                <th>Category</th>
                                <th>Products</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subcategories as $index => $subcategory)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $subcategory->name }}</td>
                                    <td>{{ $subcategory->category->category_name }}</td>
                                    <td>{{ $subcategory->products_count }}</td>
                                    <td class="text-end">
                                        @can('product.subcategory.edit')
                                            <a href="{{ route('subcategories.edit', $subcategory) }}"
                                                class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bx bx-edit-alt"></i>
                                            </a>
                                        @endcan
                                        @can('product.subcategory.delete')
                                            <form action="{{ route('subcategories.destroy', $subcategory) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Delete this subcategory?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-muted">No subcategories have been created.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
