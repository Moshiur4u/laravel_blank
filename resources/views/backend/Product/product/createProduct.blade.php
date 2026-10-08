@extends('dashboard.dashboard')
@section('title')
    addProduct
@endsection
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
                            <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="mb-3 col-6">
                                        <label for="category_id" class="col-sm-3 col-form-label">Product Name</label>
                                        <input type="text" name="productName" class="form-control" value="">
                                        @error('productName')
                                            <strong class="text-danger">{{ $message }}</strong>
                                        @enderror
                                    </div>
                                    <div class="mb-3 col-6">
                                        <label for="Supplier" class="col-sm-3 col-form-label">Supplier</label>
                                        <select class="form-select" name="brand_id" id="brand_id" required>
                                            <option value="" selected disabled>Select Supplier Name</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-6">
                                        <label for="category_id" class="col-sm-3 col-form-label">Category</label>
                                        <select class="form-select" name="product_categorie_id" id="category_id" required>
                                            <option value="" selected disabled>Select Category</option>
                                            @foreach ($ProductCategories as $ProductCategory)
                                                <option value="{{ $ProductCategory->id }}">
                                                    {{ $ProductCategory->category_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3 col-6">
                                        <label for="brand_id" class="col-sm-3 col-form-label">Brand</label>
                                        <select class="form-select" name="brand_id" id="brand_id" required>
                                            <option value="" selected disabled>Select Brand</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-4">
                                        <label for="name">Price</label>
                                        <input type="number" name="price" class="form-control" value="">
                                    </div>
                                    <div class="mb-3 col-4">
                                        <label for="name">Quantity</label>
                                        <input type="number" name="unit" class="form-control" value="">
                                    </div>
                                    <div class="mb-3 col-4">
                                        <label for="name">Purchase Date</label>
                                        <input type="date" name="purchase_date" class="form-control" value="">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-4">
                                        <label for="menufecher_date">Manufacturing Date</label>
                                        <input type="date" id="menufecher_date" name="menufecher_date"
                                            class="form-control" value="">
                                    </div>

                                    <div class="mb-3 col-4">
                                        <label for="expire_date">Expire Date</label>
                                        <input type="date" id="expire_date" name="expire_date" class="form-control"
                                            value="">
                                    </div>

                                    <!-- Output Field -->
                                    <div class="mb-3 col-4">
                                        <label for="total_duration">Total Duration</label>
                                        <input type="text" id="total_duration" name="total_duration" class="form-control"
                                            readonly placeholder="0 years 0 months 0 days">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="name">Description</label>
                                    <textarea name="description" class="form-control" value=""></textarea>
                                </div>
                                <div class="mb-3">
                                    <img id="preview" style="max-width:80px; margin-bottom: auto;" />
                                    <input type="file" name="imageName" id="imageInput" class="form-control"
                                        value="">
                                    @error('imageName')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
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
    <script>
        document.getElementById('imageInput').onchange = function(evt) {
            const [file] = this.files;
            if (file) {
                document.getElementById('preview').src = URL.createObjectURL(file);
            }
        };
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mfgInput = document.getElementById('menufecher_date');
            const expInput = document.getElementById('expire_date');
            const totalDurationInput = document.getElementById('total_duration');

            function calculateDuration() {
                if (!mfgInput.value || !expInput.value) {
                    totalDurationInput.value = '';
                    return;
                }

                let start = new Date(mfgInput.value);
                let end = new Date(expInput.value);

                if (end < start) {
                    totalDurationInput.value = 'Invalid Date Range';
                    return;
                }

                let years = end.getFullYear() - start.getFullYear();
                let months = end.getMonth() - start.getMonth();
                let days = end.getDate() - start.getDate();

                // Adjust days if negative
                if (days < 0) {
                    months--;
                    // Get total days in the previous month
                    const prevMonth = new Date(end.getFullYear(), end.getMonth(), 0);
                    days += prevMonth.getDate();
                }

                // Adjust months if negative
                if (months < 0) {
                    years--;
                    months += 12;
                }

                // Format plural/singular units
                const formatUnit = (val, singular) => `${val} ${singular}${val === 1 ? '' : 's'}`;

                const parts = [];
                if (years > 0) parts.push(formatUnit(years, 'year'));
                if (months > 0) parts.push(formatUnit(months, 'month'));
                if (days > 0 || parts.length === 0) parts.push(formatUnit(days, 'day'));

                totalDurationInput.value = parts.join(' ');
            }

            mfgInput.addEventListener('change', calculateDuration);
            expInput.addEventListener('change', calculateDuration);
        });
    </script>
@endsection
