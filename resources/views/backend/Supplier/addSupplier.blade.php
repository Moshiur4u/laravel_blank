@extends('dashboard.dashboard')
@section('title')
    addBrand
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-primary">Add Supplier Info.</h3>
                            <div class="gap-2 mb-3">
                                <a href="{{ route('supplier.index') }}" class="btn btn-primary float-end">
                                    BackToList</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('supplier.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="name"> Supplier Name</label>
                                    <input type="text" name="name" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="email"> Supplier Email</label>
                                    <input type="email" name="email" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="phone"> Supplier Phone</label>
                                    <input type="text" name="phone" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="address"> Supplier Address</label>
                                    <input type="text" name="address" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="logo"> Supplier Logo</label>
                                    <input type="file" name="logo" class="form-control" value="">
                                </div>
                                <div class="gap-2 mb-3 d-flex">
                                    <button class="btn btn-primary" type="submit"> Add Supplier</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
