@extends('dashboard.dashboard')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <div class="row">
                {{--  Here start User Info Update  --}}
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-primary">Add Product Category Information.</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('category.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="name"> Category Name</label>
                                    <input type="text" name="category_name" class="form-control"
                                        value="{{ }}">
                                </div>
                                <div class="gap-2 mb-3 d-flex ">
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
