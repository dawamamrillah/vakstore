<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get or create wallet for user
     */
    public function getWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );
    }

    /**
     * Credit balance to user wallet (Deposit, Refund, Bonus, Adjustment)
     */
    public function credit(User $user, float $amount, string $type, string $reference = '', string $description = ''): WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Jumlah credit harus lebih dari 0.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (! $wallet) {
                $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0]);
                // Re-lock
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->update(['balance' => $balanceAfter]);

            return WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => $reference,
                'description' => $description,
            ]);
        });
    }

    /**
     * Debit balance from user wallet (Purchase, Adjustment)
     */
    public function debit(User $user, float $amount, string $type = 'purchase', string $reference = '', string $description = ''): WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Jumlah debit harus lebih dari 0.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $reference, $description) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (! $wallet) {
                throw new Exception('Wallet user tidak ditemukan.');
            }

            $balanceBefore = (float) $wallet->balance;
            if ($balanceBefore < $amount) {
                throw new Exception('Saldo Vault tidak mencukupi (Saldo: Rp '.number_format($balanceBefore, 0, ',', '.').').');
            }

            $balanceAfter = $balanceBefore - $amount;
            $wallet->update(['balance' => $balanceAfter]);

            return WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => $reference,
                'description' => $description,
            ]);
        });
    }

    /**
     * Process refund for a transaction
     */
    public function refund(User $user, float $amount, string $invoiceNumber, string $reason = ''): WalletTransaction
    {
        return $this->credit(
            $user,
            $amount,
            'refund',
            $invoiceNumber,
            'Pengembalian dana untuk transaksi '.$invoiceNumber.($reason ? ' ('.$reason.')' : '')
        );
    }
}
