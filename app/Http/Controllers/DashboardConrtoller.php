<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardConrtoller extends Controller
{
    public function dashboard(){
        return view ('dashboard.dashboard');
    }
    public function dashboardbody(){
        return view ('dashboard.dashboard-body');
    }
}
