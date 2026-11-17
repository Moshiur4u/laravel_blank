@extends('dashboard.dashboard')

@section('title', 'Create Subcategory')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Create Subcategory</h4>
                @can('product.subcategory.index')
                    <a href="{{ route('subcategories.index') }}" class="btn btn-outline-secondary">Back to List</a>
                @endcan
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            @include('backend.Product.Subcategory._form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
