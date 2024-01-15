<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'quantity' => 'required',
            'name' => 'required',
            'phone' => 'required',
            'address' => 'required'
        ]);

        $data['total_price'] = $request->quantity * 100000;
        $data['status'] = 'Unpaid';

        return response()->json([
            'data' => $data
        ]);
    }
}
