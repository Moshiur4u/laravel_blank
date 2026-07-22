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
        // ইউজারকে খুঁজে বের করা হলো  রোলসহ
        $Users = User::with('roles')->get();

        // ইউজারকে ভিউ করা হলো
        return view('frontend.users.userIndex', compact('Users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // রোলে ভিউ করা হলো
        $Roles = Role::latest()->get();

        // রোলে ভিউ করা হলো
        return view('frontend.users.addUser', compact('Roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // dd($request->all());
        // রেকুয়েস্ট ডেটা ভ্যালিডেশন
        $request->validate([
            'name' => 'required',
            'roles' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required|same:confarmPassword',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:1048',
            'remark' => 'nullable',
        ]);
        // ইমেজ ভ্যারিয়েবল তৈরি করা হলো
        $imagePath = null;
        // ইমেজ আপলোড করার শর্ত তৈরি করা হলো
        if ($request->hasFile('image')) {
            // ইমেজ ভ্যারিয়েবলে রেকুয়েস্ট থেকে ইমেজ নেওয়া হলো
            $image = $request->image;
            // ইমেজ এক্সটেনশন নেওয়া হলো
            $extension = $image->extension(); // Photo Rename As par user name
            // $photo_name = Auth::User()->name.".".$extension;
            // ফাইলের নাম দেওয়া হলো
            $photo_name = $request->name.'.'.$extension;
            // ইমেজ ফোল্ডারে মুভ করা হলো
            $request->image->move(public_path('Users'), $photo_name);
            // ইমেজ পাথ ভ্যারিয়েবলে রাখা হলো যার কারনে ফাইল টা ডাটাবেজে সেভ হবে
            $imagePath = $photo_name;

            // $imagePath = $request->file('image')->storeAs('Users', $imageName, 'public');
        }
        // ইউজার ডেটা ক্রিয়েট করা হলো
        $Users = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imagePath,
            'remark' => $request->remark,
        ]);
        // ইউজারকে রোল এসাইন করা হলো
        $Users->assignRole($request->roles);
        // ফ্ল্যাশ মেসেজ দেখানো হলো
        flash()->success('User Added successfully!');

        // রিডাইরেক্ট করা হলো
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
        // ইউজারকে খুঁজে বের করা হলো
        $Users = User::find($id);
        // রোলে ভিউ করা হলো
        $Roles = Role::latest()->get();
        // ইউজারকে রোল এসাইন করা হলো
        $userRole = $Users->Roles->pluck('name')->all();

        // ইডিটে ভিউ করা হলো
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
            'email' => 'required|email|unique:users,email,'.$id, // বর্তমান ইউজারকে ইগনোর করবে
            'password' => 'nullable|same:confirmPassword|min:6', // nullable করা হলো
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // nullable করা হলো
            'status' => 'nullable',
            'remark' => 'nullable',
        ]);
        // ডেটা আপডেট করার জন্য ইউজারকে খুজে বের করা হলো
        $user = User::findOrFail($id);
        // ডেটা আপডেট করার জন্য ভেরিয়েবল তৈরি হলো
        $updateData = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'remark' => $request->input('remark'),
        ];
        // স্ট্যাটাস আপডেট লজিক
        if ($request->has('status')) {
            $updateData['status'] = $request->input('status');
        } else {
            $updateData['status'] = 0;
        }

        // ২. ইমেজ আপডেট লজিক
        if ($request->hasFile('image')) {
            // ১ম পদ্ধতি (File::) পদ্ধতি বাদ দেওয়া হলো
            // use Illuminate\Support\Facades\File;
            // পুরনো ইমেজ ডিলিট করা (Folder name 'Users' consistent রাখা হলো)
            // if ($user->image && File::exists(public_path('Users/' . $user->image))) {
            //     File::delete(public_path('Users/' . $user->image));
            // }
            // যদি ডাটাবেজে ইউজার ইমেজ থাকে তবে পুরানো ইমেজ ডিলিট করবে
            if ($user->image !== null) {
                // পুরানো ইমেজ ডিলিট ইউজার ফোল্ডার থেকে  ইউজার -> ডাটাবেজ ইমেজ
                $delete_from = public_path('Users/'.$user->image);
                // উনলিঙ্ক মানে ডিলিট
                unlink($delete_from);

            }

            // নতুন ইমেজ আপলোড
            $image = $request->file('image');
            $extension = $image->extension();
            // ফাইলের নামে স্পেস থাকলে সমস্যা হয়, তাই str_replace এবং time() ব্যবহার করা নিরাপদ
            // $photo_name = str_replace(' ', '_', $request->name).'_'.time().'.'.$extension;
            // শুধু রিক্যেস্ট থেকে নাম নিব
            $photo_name = ($request->name).'.'.$extension;
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

    // এই ফংশনটা ইউজার স্ট্যাটাস একটি বাটন থাকে যা ক্লিক করলে ইউজার স্ট্যাটাস একটিভ এবং ইনএক্টিভ হয়
    // এবং আলাদা একটা রাউট লিংক userstatusupdate থাকে তা ব্যবহার করা হয়েছে ও এ রাউট লিংক ব্লেড এ ব্যবহার করা হয়েছে।
    public function userstatusupdate(string $status_id)
    {
        $user = User::find($status_id);
        // স্ট্যাটাস আপডেট লজিক চেক করবে ইন ডাটাবেজ কত আছে
        if ($user->status == 0) {
            // user মডেল থেকে স্ট্যাটাস ফাইন্ড করবে ০ থাকলে এবং উপডেট করবে এখানে  ১ হলে active হবে এবং সবুজ বাটন হবে
            User::find($status_id)->update([
                'status' => 1,
            ]);
        } else {
            // স্ট্যাটাস ০ হলে inactive হবে এবং লাল বাটন হবে
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
        // ইফ কন্ডিশন ব্যবহার করে চেক করা হলো যে ইউজার ইমেজ আছে কিনা
        if ($Users->image !== null) {
            // ইমেজের পাথ তৈরি করা হলো
            $delete_from = public_path('users/'.$Users->image);
            // ইমেজ ডিলিট করা হলো
            unlink($delete_from);
        }
        // ডাটাবেজ থেকে ইউজার ডিলিট করা হলো
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
