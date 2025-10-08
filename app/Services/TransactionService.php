<?php
namespace App\Services;

use App\Events\BalanceUpdated;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    const COMMISSION_RATE = 0.015; // 1.5%

    /**
     * Transfer amount from sender to receiver with commission fee.
     *
     * @throws \Exception
     */
    public function transfer(User $sender, int $receiverId, float $amount): Transaction
    {
        return DB::transaction(function () use ($sender, $receiverId, $amount) {
            // Lock sender's record to prevent race conditions
            $sender = User::where('id', $sender->id)->lockForUpdate()->first();

            // Lock receiver's record
            $receiver = User::where('id', $receiverId)->lockForUpdate()->first();

            if (! $receiver) {
                throw new \Exception('Receiver not found');
            }

            if ($sender->id === $receiver->id) {
                throw new \Exception('Cannot transfer to yourself');
            }

            $commissionFee = round($amount * self::COMMISSION_RATE, 2);
            $totalDebited  = $amount + $commissionFee;

            if ($sender->balance < $totalDebited) {
                throw new \Exception('Insufficient balance');
            }

            // Update balances
            $sender->decrement('balance', $totalDebited);
            $receiver->increment('balance', $amount);

            // Get updated balance
            $sender->refresh();

            // Create transaction record with balance_after
            $transaction = Transaction::create([
                'sender_id'      => $sender->id,
                'receiver_id'    => $receiver->id,
                'amount'         => $amount,
                'commission_fee' => $commissionFee,
                'total_debited'  => $totalDebited,
                'balance_after'  => $sender->balance,
                'status'         => 'completed',
            ]);

            // Dispatch events (will be broadcast after transaction commits)
            event(new BalanceUpdated($sender));
            event(new BalanceUpdated($receiver->fresh()));

            return $transaction;
        });
    }
}
