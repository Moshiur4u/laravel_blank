@extends('dashboard.dashboard')
@section('title')
    editProduct
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-center text-primary">Product<sub class="text-info">Update</sub></h3>
                            <div class="gap-2 mb-3">
                                <a href="{{ route('product.index') }}" class="btn btn-primary float-end">
                                    BackToList</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('product.update', $product->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label for="name">Product Name</label>
                                    <input type="text" name="productName" class="form-control"
                                        value="{{ $product->productName }}">
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-6">
                                        <label for="category_id" class="col-sm-3 col-form-label">Select Category</label>
                                        <select class="form-select" name="product_categorie_id" id="category_id" required>
                                            <option value="" selected disabled>Select
                                                Category
                                            </option>
                                            @foreach ($ProductCategories as $ProductCategory)
                                                <option value="{{ $ProductCategory->id }}"
                                                    {{ $product->product_categorie_id == $ProductCategory->id ? 'selected' : '' }}>
                                                    {{ $ProductCategory->category_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3 col-6">
                                        <label for="brand_id" class="col-form-label">Select Brand</label>
                                        <select class="form-select" name="brand_id" id="brand_id" required>
                                            <option value="" disabled>Select Brand</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}"
                                                    {{ $product->brand_id == $brand->id ? 'selected' : '' }}>
                                                    {{ $brand->name }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-4">
                                        <label for="name">Price</label>
                                        <input type="number" name="price" class="form-control"
                                            value="{{ $product->price }}">
                                    </div>
                                    <div class="mb-3 col-4">
                                        <label for="name">Quantity</label>
                                        <input type="number" name="unit" class="form-control"
                                            value="{{ $product->unit }}">
                                    </div>

                                    <div class="mb-3 col-4">
                                        <label for="imageInput" class="form-label">Product Image</label>
                                        <!-- বর্তমান ইমেজ শো করার জন্য -->
                                        @if ($product->img_url)
                                            <div class="mb-2">
                                                <img id="preview"
                                                    src="{{ asset('uploads/products/' . $product->img_url) }}"
                                                    style="max-width:150px;" />
                                            </div>
                                        @endif
                                        <input type="file" id="imageInput" name="img_url" class="form-control">
                                    </div>
                                    @error('img_url')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
                                </div>
                                <div class="gap-2 mb-3 d-flex">
                                    <button class="btn btn-primary" type="submit"> Save Change</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('imageInput').onchange = function(evt) {
            const [file] = this.files;
            if (file) {
                document.getElementById('preview').src = URL.createObjectURL(file);
            }
        };
    </script>
@endsection
