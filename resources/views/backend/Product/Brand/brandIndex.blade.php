@extends('dashboard.dashboard')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <div class="row">

                {{--  here we start password Update fill  --}}
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-center text-success">Brand List.</h3>
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
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td class="gap-2 d-flex">
                                                    <a href="#" class="btn btn-primary btn-small">Edit</a>
                                                    <a href="#" class="btn btn-danger btn-icon">Delete
                                                    </a>
                                                </td>
                                            </tr>
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
