<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'salary', 'nid_number', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'remark',
        'status',
        'salary',
        'nid_number',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            //এখানে স্ট্যাটাসকে বুলিয়ান করা হয়েছে যাতে এটা 1 এবং 0 কে True এবং False তে কনভার্ট করতে পারে
            // এবং লগইনের সময় স্ট্যাটাস অ্যাক্টিভ আছে কিনা এটা চেক করার জন্য এইটা ব্যবহার করা হয় Request - Auth - LoginRequest এর মধ্যে কাজ করা হয়েছে।
            'status' => 'boolean',
            'salary' => 'decimal:2',
        ];
    }

    /**
     * বর্তমান ব্যবহারকারীর জন্য দৃশ্যমান user-দের query তৈরি করে।
     */
    public function scopeVisibleToCurrentUser(Builder $query): Builder
    {
        if (auth()->user()?->hasRole('admin')) { // বর্তমান ব্যবহারকারীর admin role আছে কি না যাচাই করে।
            return $query; // admin হলে কোনো user-কে query থেকে বাদ দেয় না।
        } // non-admin হলে নিচের role filter প্রয়োগ হয়।

        return $query->whereDoesntHave( // admin role থাকা user-দের বাদ দেওয়ার query তৈরি করে।
            'roles', // User model-এর role relation পরীক্ষা করে।
            fn ($roles) => $roles->where('name', 'admin') // admin নামের role যুক্ত user খুঁজে পায়।
        ); // admin role নেই এমন user-দের query ফেরায়।
    }
}
