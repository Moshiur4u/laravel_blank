@extends('dashboard.dashboard')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-center text-primary">Product<sub class="text-info">Add</sub></h3>
                            <div class="gap-2 mb-3">
                                <a href="{{ route('product.index') }}" class="btn btn-primary float-end">
                                    BackToList</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('product.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="name">Product Name</label>
                                    <input type="text" name="productName" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="category_id" class="col-sm-3 col-form-label">Select Category</label>
                                    <select class="form-select" name="categories_id" id="category_id" required>
                                        <option value="" selected disabled>Select Category</option>
                                        @foreach ($ProductCategories as $ProductCategory)
                                            <option value="{{ $ProductCategory->id }}">{{ $ProductCategory->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="brand_id" class="col-sm-3 col-form-label">Select Brand</label>
                                    <select class="form-select" name="brands_id" id="brand_id" required>
                                        <option value="" selected disabled>Select Brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="name">Price</label>
                                    <input type="number" name="price" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="name">Quantity</label>
                                    <input type="number" name="quantity" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="name">Image</label>
                                    <input type="file" name="image" class="form-control" value="">
                                </div>
                                <div class="gap-2 mb-3 d-flex">
                                    <button class="btn btn-primary" type="submit"> Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
