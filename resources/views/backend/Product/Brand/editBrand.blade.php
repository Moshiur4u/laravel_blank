@extends('dashboard.dashboard')
@section('title')
    editBrand
@endsection
@section('content')
    <div class="page-content">
        <div class="page-wrapper">

            <div class="row">

                {{--  Here start Category Info Update  --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-primary">Update Product Brand Information.</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('brand.update', $brand->id) }}" method="Post">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label for="name"> Category Name</label>
                                    <input type="text" name="name" class="form-control" value="{{ $brand->name }}">
                                </div>
                                <div class="gap-2 mb-3 d-flex ">
                                    <button class="btn btn-primary" type="submit"> Update Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
