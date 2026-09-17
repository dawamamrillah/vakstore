<?php

namespace App\Services\Voucher;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Exception;

class VoucherService
{
    /**
     * Validate voucher and calculate discount
     */
    public function validateAndCalculate(string $code, float $subtotal, ?User $user = null, ?Product $product = null): array
    {
        $voucher = Voucher::where('code', strtoupper(trim($code)))
            ->where('status', 'active')
            ->first();

        if (! $voucher) {
            throw new Exception('Kode voucher tidak valid atau sudah tidak aktif.');
        }

        if ($voucher->start_at && now()->lt($voucher->start_at)) {
            throw new Exception('Promo voucher belum dimulai.');
        }

        if ($voucher->end_at && now()->gt($voucher->end_at)) {
            throw new Exception('Masa berlaku voucher telah berakhir.');
        }

        if ($voucher->usage_limit > 0 && $voucher->used_count >= $voucher->usage_limit) {
            throw new Exception('Kuota penggunaan voucher telah habis.');
        }

        if ($subtotal < (float) $voucher->minimum_transaction) {
            throw new Exception('Minimum transaksi untuk voucher ini adalah Rp '.number_format($voucher->minimum_transaction, 0, ',', '.').'.');
        }

        // Check user usage limit
        if ($user && $voucher->usage_per_user > 0) {
            $userUsage = VoucherUsage::where('voucher_id', $voucher->id)
                ->where('user_id', $user->id)
                ->count();
            if ($userUsage >= $voucher->usage_per_user) {
                throw new Exception('Anda telah mencapai batas penggunaan voucher ini.');
            }
        }

        // Check category or game constraint
        if ($product) {
            if ($voucher->category_id && $voucher->category_id !== $product->category_id) {
                throw new Exception('Voucher ini tidak berlaku untuk kategori produk yang dipilih.');
            }
            if ($voucher->game_id && $voucher->game_id !== $product->game_id) {
                throw new Exception('Voucher ini tidak berlaku untuk game yang dipilih.');
            }
        }

        $discount = $voucher->calculateDiscount($subtotal);

        return [
            'voucher' => $voucher,
            'discount' => $discount,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'final_total' => max(0, $subtotal - $discount),
        ];
    }

    /**
     * Record usage after order completion
     */
    public function recordUsage(Voucher $voucher, Transaction $transaction, ?User $user, float $discount): VoucherUsage
    {
        $voucher->increment('used_count');

        return VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'user_id' => $user?->id,
            'transaction_id' => $transaction->invoice_number,
            'discount' => $discount,
        ]);
    }
}
