<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::with('permissions')->latest()->get();
        // return $roles;
        return view('backend.roles.role-index',compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only offer permissions the current user may delegate to a new role.
        $permissions = Permission::whereIn('id', auth()->user()->getAllPermissions()->modelKeys())
            ->latest()
            ->get();
        return view('backend.roles.role-create',compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $permissionIds = $request->user()->getAllPermissions()->modelKeys();
        $validated = $request->validate([
            'name' => 'required|unique:roles,name',
            'permission' => ['required', 'array', 'min:1'],
            'permission.*' => ['required', 'integer', 'distinct', Rule::in($permissionIds)],
        ]);
        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions(array_map('intval', $validated['permission']));
        flash()->success('Role Create successfully!');
        return redirect()->route('roles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show( )
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit( $id )
    {
        $permissions = Permission::get()->all();
        $role =Role::with('Permissions')->find($id);
        $rolewithpermission = $role->Permissions->pluck('id')->all();

        return view('backend.roles.role-edit',compact('permissions','role','rolewithpermission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissionIds = array_unique(array_merge(
            $request->user()->getAllPermissions()->modelKeys(),
            $role->permissions->modelKeys()
        ));
        $validated = $request->validate([
            'name' => 'required|unique:roles,name,'.$id,
            'permission' => ['required', 'array', 'min:1'],
            'permission.*' => ['required', 'integer', 'distinct', Rule::in($permissionIds)],
        ]);
        $role->name = $validated['name'];
        $role->save();

        $role->syncPermissions(array_map('intval', $validated['permission']));
        flash()->success('Role Update successfully!');
        return redirect()->route('roles.index');


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {
       Role::find($id)->delete();
       sweetalert()->success('Role Delete Successfully.');
       return back();
    }
}
