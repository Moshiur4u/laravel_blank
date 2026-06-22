@extends('dashboard.dashboard')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <div class="row">
                {{--  Here start User Info Update  --}}
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-primary">Add Product Category Information.</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('category.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="name"> Category Name</label>
                                    <input type="text" name="category_name" class="form-control" value="">
                                </div>
                                <div class="gap-2 mb-3 d-flex ">
                                    <button class="btn btn-primary" type="submit"> Add Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                {{--  here we start Image Update Options  --}}
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-info">Update Categories.</h3>
                        </div>
                        <div class="card-body">
                            <form action="#" method="#">
                                <div class="mb-3">
                                    <label for="#">Update Categories Name</label>
                                    <input type="text" name="name" class="form-control">
                                    @error('categories')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-info" type="submit"> Update Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {{--  here we start password Update fill  --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="text-center card-header">
                        <h3 class="text-danger">Category List.</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="example_wrapper" class="dataTables_wrapper dt-bootstrap5">
                                <table id="myTable" class="table display table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ProductCategories as $key => $ProductCategory)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $ProductCategory->category_name }} </td>
                                                <td class="gap-2 d-flex">
                                                    <a href="#" class="btn btn-primary btn-small">edit</a>
                                                    <a href="{{ route('category.destroy', $ProductCategory->id) }}"
                                                        class="btn btn-danger btn-icon">Delete
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
@endsection
