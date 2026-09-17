<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Voucher\VoucherService;
use Exception;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherService $voucherService
    ) {}

    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
        ]);

        try {
            $product = $request->product_id ? Product::find($request->product_id) : null;
            $user = auth()->user();

            $result = $this->voucherService->validateAndCalculate(
                $request->code,
                (float) $request->subtotal,
                $user,
                $product
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Voucher berhasil diterapkan!',
                'code' => $result['code'],
                'name' => $result['name'],
                'discount' => $result['discount'],
                'final_total' => $result['final_total'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
