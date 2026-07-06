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
                            <h3 class="text-primary">Add Product Brand.</h3>
                            <div class="gap-2 mb-3">
                                <a href="{{ route('brand.index') }}" class="btn btn-primary float-end">
                                    BackToList</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('brand.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="name"> Category Name</label>
                                    <input type="text" name="name" class="form-control" value="">
                                </div>
                                <div class="gap-2 mb-3 d-flex">
                                    <button class="btn btn-primary" type="submit"> Add Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
