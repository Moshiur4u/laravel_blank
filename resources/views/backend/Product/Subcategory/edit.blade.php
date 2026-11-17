@extends('dashboard.dashboard')

@section('title', 'Edit Subcategory')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Edit Subcategory</h4>
                @can('product.subcategory.index')
                    <a href="{{ route('subcategories.index') }}" class="btn btn-outline-secondary">Back to List</a>
                @endcan
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('subcategories.update', $subcategory) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label for="subcategory_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category_id" id="subcategory_category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                @selected((string) old('category_id', $subcategory->category_id) === (string) $category->id)>
                                                {{ $category->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="mt-1 text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="subcategory_name" class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="subcategory_name" class="form-control"
                                        maxlength="255" value="{{ old('name', $subcategory->name) }}" required>
                                    @error('name')
                                        <div class="mt-1 text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('subcategories.index') }}" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Update Subcategory</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
