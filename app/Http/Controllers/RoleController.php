<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
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
        $permissions = Permission::latest()->get();
        return view('backend.roles.role-create',compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permission'=>'required'
        ]);
        $PermissionId = array_map('intval',($request->input('permission')));
        $role = Role::create(['name'=> $request->input('name')]);
        $role->syncPermissions($PermissionId);

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
        $request->validate([
            'name'=>'required|unique:roles,name,'.$id,
            'permission'=>'required'
        ]);
        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->save();

        $PermissionId = array_map('intval',($request->input('permission')));
        $role->syncPermissions($PermissionId);
        // $flash()->successfull('Role Updated Seccessfully');
        return redirect()->route('roles.index');


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {
       Role::find($id)->delete();
       return back();
    }
}