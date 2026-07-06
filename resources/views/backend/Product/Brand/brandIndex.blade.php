@extends('dashboard.dashboard')
@section('title')
    brandList
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">

                {{--  here we start password Update fill  --}}
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-center text-primary">Brand List</h3>
                            <div class="gap-2 mb-3">
                                <a href="{{ route('brand.create') }}" class="btn btn-primary float-end">
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
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($Brans as $key => $brand)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $brand->name }}</td>
                                                    <td class="gap-2 d-flex">
                                                        <a href="{{ route('brand.edit', $brand->id) }}"
                                                            class="btn btn-primary btn-small">Edit</a>
                                                        <a href="{{ route('brand.destroy', $brand->id) }}"
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
