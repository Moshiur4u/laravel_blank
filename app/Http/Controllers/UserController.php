<?php

namespace App\Http\Controllers;

use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Users = User::with('roles')->get();
        return view('frontend.users.userIndex',compact('Users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $Roles = Role::latest()->get();
        return view('frontend.users.addUser',compact('Roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());

        $request->validate([
            'name'=>'required',
            'roles'=>'required',
            'email'=>'required|unique:users,email',
            'password'=>'required|same:confarmPassword',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:1048',

        ]);
        if ($request->hasFile('image')) {
        // ইমেজ স্টোর করা
        $imageName = time() . '.' . $request->image->extension();

        // পাবলিক ডিস্কে স্টোর
        // $path = $request->file('image')->store('images', 'public');

        // অথবা কাস্টম নাম দিয়ে স্টোর
        $path = $request->file('image')->storeAs('users', $imageName, 'public');
       $Users = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make( $request->password),
            'image'=>$imagePath,
        ]);
        $Users->assignRole($request->roles);
        return redirect()->route('user.index');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $Users = User::find($id);
        $Roles = Role::latest()->get();
        $userRole = $Users->Roles->pluck('name')->all();
        return view('frontend.users.editUser',compact('Users','Roles','userRole'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required',
            'roles'=>'required',
            'email'=>'unique:users,email,'.$id,
            'password'=>'nullable|same:confarmPassword'
        ]);
        // dd($request->all());
        $Users = User::find($id);
        $Users->update([
            'name'=>$request->input('name'),
            'email'=>$request->input('email'),
        ]);
        if($request->has('password')){
            $Users->update([
                'password'=>Hash::make($request->password)
            ]);
        };
        $Users->syncRoles($request->roles);
        return redirect()->route('user.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function logout(Request $request){
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}