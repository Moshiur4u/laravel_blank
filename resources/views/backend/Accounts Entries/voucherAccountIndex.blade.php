@extends('dashboard.dashboard')

@section('title', 'Voucher Accounts')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex align-items-center justify-content-between">
                <div>
                    <div class="breadcrumb-title pe-3">Accounts</div>
                    <h4 class="mt-2 mb-0">Voucher Accounts</h4>
                </div>
                @can('voucher.account.create')
                    <a href="{{ route('voucher-accounts.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus-circle me-1"></i>Add Account
                    </a>
                @endcan
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="border-0 shadow-sm card">
                <div class="card-header">
                    <h5 class="mb-0">Account Name List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-striped table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">#</th>
                                    <th>Account Name</th>
                                    <th>Created</th>
                                    <th style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($voucherAccounts as $key => $voucherAccount)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $voucherAccount->name }}</td>
                                        <td>{{ $voucherAccount->created_at->format('d M Y') }}</td>
                                        <td>
                                            @can('voucher.account.edit')
                                                <a href="{{ route('voucher-accounts.edit', $voucherAccount) }}"
                                                    class="btn btn-sm btn-outline-primary">Edit</a>
                                            @endcan
                                            @can('voucher.account.delete')
                                                <form action="{{ route('voucher-accounts.destroy', $voucherAccount) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Delete this voucher account?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-muted">No voucher accounts added yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
