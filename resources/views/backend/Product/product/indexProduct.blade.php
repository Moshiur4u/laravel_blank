@extends('dashboard.dashboard')
@section('title')
    Product
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">

                {{--  here we start password Update fill  --}}
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-center text-primary">Product<sub>List</sub></h3>
                            <div class="gap-2 mb-3">
                                <a href="{{ route('product.create') }}" class="btn btn-primary float-end">
                                    Add New</a>
                            </div>
                        </div>
                        <div class="text-justify card-body">
                            <div class="table-responsive">
                                <div id="example_wrapper" class="dataTables_wrapper dt-bootstrap5">
                                    <table id="myTable" class="table display table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Brand</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Image</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($Products as $key => $product)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $product->productName }}</td>
                                                    {{-- <td>{{ $product->product_categorie_id }}</td> --}}
                                                    <td>{{ $product->productCategory->category_name }}</td>
                                                    {{-- <td>{{ $product->productCategory->category_name ?? 'N/A' }}</td> --}}
                                                    <td>{{ $product->brand->name }}</td>
                                                    <td>{{ $product->price }}</td>
                                                    <td>{{ $product->quantity }}</td>
                                                    <td></td>
                                                    <td class="gap-2 d-flex">
                                                        <a href="{{ route('product.edit') }}"
                                                            class="btn btn-primary btn-small">Edit</a>
                                                        <a href="#" class="btn btn-danger btn-icon">Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
