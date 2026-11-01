<?php

namespace App\Http\Controllers;

class MoneyReceiptController extends Controller
{
    public function create()
    {
        return view('backend.money-receipts.create');
    }
}
