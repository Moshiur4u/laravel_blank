<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserGuide extends Controller
{
    public function UserGuide()
    {
        return view('frontend.userguide.guideline');
    }
}
