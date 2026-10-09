@extends('dashboard.dashboard')
@section('title')
    Category-add&list
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <div class="row">
                {{--  here we start password Update fill  --}}
                <div class="col-lg-8">
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
                                            @foreach ($ProductCategories as $key => $ProCategory)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $ProCategory->category_name }} </td>
                                                    <td class="gap-2 d-flex">
                                                        <a href="{{ route('category.edit', $ProCategory->id) }}"
                                                            class="btn btn-primary btn-small">Edit</a>
                                                        <a href="{{ route('category.destroy', $ProCategory->id) }}"
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
    </div>
@endsection
