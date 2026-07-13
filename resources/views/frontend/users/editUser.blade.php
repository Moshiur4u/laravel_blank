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
                            <h3 class="text-center text-primary">Update-User-Info</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('user.update', $Users->id) }}" method="Post"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label for="name"> User Name</label>
                                    <input type="text" name="name" class="form-control" value="{{ $Users->name }}">
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="col-sm-3 col-form-label">Select Roles</label>
                                    <select class="form-select" name="roles" id="category_id" required>
                                        <option value="" selected disabled>Select
                                            Role
                                        </option>
                                        @foreach ($Roles as $role)
                                            <option value="{{ $role->name }}"
                                                {{ in_array($role->name, $userRole) ? 'selected' : '' }}>
                                                {{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="name"> User Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ $Users->email }}">
                                </div>
                                <div class="mb-3">
                                    <label for="name"> Password</label>
                                    <input type="number" name="password" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="name">Conform Password</label>
                                    <input type="number" name="confarmPassword" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="name">Change Photo </label>
                                    <input type="file" name="image" class="form-control" value="">
                                </div>
                                <div class="gap-2 mb-3 text-center ">
                                    <button class="btn btn-primary" type="submit"> Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
