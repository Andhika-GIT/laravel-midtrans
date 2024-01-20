<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'qty' => 'required',
            'name' => 'required',
            'phone' => 'required',
            'address' => 'required'
        ]);

        $data['total_price'] = $request->qty * 100000;
        $data['status'] = 'Unpaid';

        $order = Order::create($data);



        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = false;
        // Set sanitization on (default)
        \Midtrans\Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        \Midtrans\Config::$is3ds = true;

        $params = array(
            'transaction_details' => array(
                'order_id' => $order->id,
                'gross_amount' => $order->total_price,
            ),
            'customer_details' => array(
                'first_name' => $request->name,
                'last_name' => 'user',
                'phone' => $request->phone,
            ),
        );

        $snapToken = \Midtrans\Snap::getSnapToken($params);

        return response()->json([
            'token' => $snapToken,
            'orderId' => $order->id,
        ]);
    }

    public function callback(Request $request)
    {
        // take the server key env
        $serverKey = config("midtrans.server_key");

        // hash the order_id,status_code,gross_amount,ServerKey from midtrans, into one string as SHA512
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        // check if hashed is the same as signature key request from midtrans
        if ($hashed == $request->signature_key) {
            // check the transaction status from midtrans request

            // if captured, means the payment is sucessful
            if ($request->transaction_status == "capture" or $request->transaction_status == "settlement") {
                // find the order in our database based on order_id request from midtrans
                $order = Order::find($request->order_id);

                // upddate the status to Paid
                $order->update(['status' => 'paid']);
            }
        }
    }

    public function invoice(Order $order)
    {
        return response()->json([
            'order' => $order
        ]);
    }
}
