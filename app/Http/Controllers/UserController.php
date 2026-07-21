<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Users = User::with('roles')->get();

        return view('frontend.users.userIndex', compact('Users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $Roles = Role::latest()->get();

        return view('frontend.users.addUser', compact('Roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // dd($request->all());

        $request->validate([
            'name' => 'required',
            'roles' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required|same:confarmPassword',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:1048',
            'remark' => 'nullable',
        ]);
        $imagePath = null;
        // ইমেজ হ্যান্ডলিং
        if ($request->hasFile('image')) {
            $image = $request->image;
            $extension = $image->extension(); // Photo Rename As par user name
            // $photo_name = Auth::User()->name.".".$extension;
            $photo_name = $request->name . '.' . $extension;
            $request->image->move(public_path('Users'), $photo_name);
            $imagePath = $photo_name;

            // $imagePath = $request->file('image')->storeAs('Users', $imageName, 'public');
        }
        $Users = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imagePath,
            'remark' => $request->remark,
        ]);
        $Users->assignRole($request->roles);
        flash()->success('User Added successfully!');

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

        return view('frontend.users.editUser', compact('Users', 'Roles', 'userRole'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // ১. ভ্যালিডেশন ঠিক করা হলো (email ignore এবং nullable)
        $request->validate([
            'name' => 'required|string|max:255',
            'roles' => 'required',
            'email' => 'required|email|unique:users,email,' . $id, // বর্তমান ইউজারকে ইগনোর করবে
            'password' => 'nullable|same:confirmPassword|min:6', // nullable করা হলো
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // nullable করা হলো
            'remark' => 'nullable',
        ]);

        $user = User::findOrFail($id);

        $updateData = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'remark' => $request->input('remark'),
        ];

        // ২. ইমেজ আপডেট লজিক
        if ($request->hasFile('image')) {
            // ১ম পদ্ধতি (File::) পদ্ধতি বাদ দেওয়া হলো
            // use Illuminate\Support\Facades\File;
            // পুরনো ইমেজ ডিলিট করা (Folder name 'Users' consistent রাখা হলো)
            // if ($user->image && File::exists(public_path('Users/' . $user->image))) {
            //     File::delete(public_path('Users/' . $user->image));
            // }
            if ($user->image !== null) {
                // যদি ইউজার ইমেজ থাকে, তবে পুরানো ইমেজ ডিলিট করবে
                $delete_from = public_path('Users/' . $user->image);
                // পুরানো ইমেজ ডিলিট ইউজার ফোল্ডার থেকে  ইউজার -> ডাটাবেজ ইমেজ
                unlink($delete_from);
                // উনলিঙ্ক মানে ডিলিট
            }

            // নতুন ইমেজ আপলোড
            $image = $request->file('image');
            $extension = $image->extension();
            // ফাইলের নামে স্পেস থাকলে সমস্যা হয়, তাই str_replace এবং time() ব্যবহার করা নিরাপদ
            // $photo_name = str_replace(' ', '_', $request->name).'_'.time().'.'.$extension;
            // শুধু রিক্যেস্ট থেকে নাম নিব
            $photo_name = ($request->name) . '.' . $extension;
            // ইউজার ফোল্ডারে ইমেজ সেভ হবে ও  ফোল্ডার নাম ইউজার
            $image->move(public_path('Users'), $photo_name);

            // আপডেট ডেটাতে নতুন ইমেজের নাম যোগ করা
            $updateData['image'] = $photo_name;
        }

        // ৩. পাসওয়ার্ড আপডেট লজিক (filled ব্যবহার করা হয়েছে)
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // ৪. একবারেই ডেটা আপডেট করা (Efficient)
        $user->update($updateData);

        // ৫. রোল সিঙ্ক করা
        $user->syncRoles($request->roles);

        return redirect()->route('user.index')->with('success', 'User updated successfully!');
    }

    public function userstatusupdate(string $status_id)
    {
        $user = User::find($status_id);
        if ($user->status == 0) {
            User::find($status_id)->update([
                'status' => 1,
            ]);
        } else {
            User::find($status_id)->update([
                'status' => 0,
            ]);
        }

        return redirect()->route('user.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $Users = User::find($id);
        if ($Users->image !== null) {
            $delete_from = public_path('users/' . $Users->image);
            unlink($delete_from);
        }
        User::find($id)->delete();

        return redirect()->route('user.index');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
