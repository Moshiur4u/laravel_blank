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
                                        <!--ফর ইচ লুপ এর মাধ্যমে ব্লেডে রোলেগুলো দেখান হলো --> 
                                        @foreach ($Roles as $role)
                                        <!-- in_array ফাংশন ব্যবহার করে আমরা চেক করব  যে ইউজারকে কোন রোল টি সিলেক্ট করা আছে কিনা -->
                                            <option value="{{ $role->name }}"
                                                {{ in_array($role->name, $userRole) ? 'selected' : '' }}>
                                                {{-- রোল-নাম দেখাব তাই নাম দিলাম --}}
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
                                    <input type="password" name="password" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="name">Conform Password</label>
                                    <input type="password" name="confarmPassword" class="form-control" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="name">Change Photo </label>
                                    <input type="file" name="image" class="form-control" value="">
                                    @if ($Users->image)
                                        <small class="text-muted">Current: {{ $Users->image }}</small>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <label for="name"> Status</label>
                                    <select class="form-select" name="status" id="status" required>
                                        <option value="" selected disabled>Select
                                            Status
                                        </option>
                                        {{-- অপশন ভ্যালু ডাটাবেজ এ  সেভ হবে এ জন্য আলাদা করে value দিতে হয়েছে 
                                            এবং {{ $Users->status == 1 ? 'selected' : '' }} 
                                            দ্বারা চেক করা হয়েছে যে কোন অপশন টি সিলেক্ট করা আছে --}}
                                        <option value="1" {{ $Users->status == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $Users->status == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="name"> Remark</label>
                                    <textarea name="remark" class="form-control" value="">{{ $Users->remark }}</textarea>   
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
