<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use PharIo\Manifest\Author;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['item.product'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data Transaksi Berhasil di Ambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data Transaksi Tidak Ada',
                    484
                );
            }
        }

        $transaction = Transaction::with(['item.product'])->where('user_id', Auth::user()->id);

        if ($status) {
            $transaction->where('status', $status);
        }
        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data List Transaksi Berhasil di Ambil'
        );
    }

    public function checkout(Request $request) {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' =>'exits:products,id',
            'total_price' => 'required',
            'shipping_price' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANSELED,FAILED,SHIPPING,SHIPPED'
        ]);
        $transaction = Transaction::create([
            'users_id' =>Auth::user()->id,
            'adress' =>$request->adress,
            'total_price' => $request->total_price,
            'Shipping_price' => $request->shipping_price,
            'status' => $request->status,
        ]);

        foreach ($request->items as $product) {
            Transaction::create([
                'user_id' => Auth::user()->id,
                'products_id' => $product['id'],
                'transactions_id' => $transaction->id,
                'quantity' => $product['quantity']
            ]);

            return ResponseFormatter::success($transaction->load('items.pruduct'), 'Transaksi Berhasil')
        }
    }
}
