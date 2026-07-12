@extends('dashboard.dashboard')
@section('title')
    editCreate
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">

                {{--  Here start User's Info Update  --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-primary">Update-User-Info</h3>
                        </div>
                        <div class="card-body">
                            <form action=" " method="Post">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label for="name"> User Name</label>
                                    <input type="text" name="name" class="form-control" value="{{ $Users->name }}">
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="col-sm-3 col-form-label">Select Category</label>
                                    <select class="form-select" name="product_categorie_id" id="category_id" required>
                                        <option value="" selected disabled>Select
                                            Category
                                        </option>
                                        @foreach ($Users->roles as $role)
                                            <option
                                                value="{{ $role->name }}",{{ in_array($role->name, $userRole) ? 'selected' : '' }}>
                                                {{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="gap-2 mb-3 d-flex ">
                                    <button class="btn btn-primary" type="submit"> Update User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
