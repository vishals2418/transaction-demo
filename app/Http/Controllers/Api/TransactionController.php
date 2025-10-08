<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    /**
     * Display a listing of the user's transactions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $transactions = $user->sentTransactions()
            ->with(['sender', 'receiver'])
            ->union($user->receivedTransactions()->with(['sender', 'receiver']))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'transactions' => $transactions,
            'balance'      => $user->balance,
        ]);
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer|exists:users,id',
            'amount'      => 'required|numeric|min:0.01|max:999999999',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $transaction = $this->transactionService->transfer(
                $request->user(),
                $request->receiver_id,
                $request->amount
            );

            return response()->json([
                'message'     => 'Transaction completed successfully',
                'transaction' => $transaction->load(['sender', 'receiver']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate the receiver before making a transaction.
     */
    public function validateReceiver($id)
    {
        $user = auth()->user();

        // Check if receiver exists
        $receiver = User::find($id);

        if (! $receiver) {
            return response()->json([
                'valid'   => false,
                'message' => 'User not found',
            ], 404);
        }

        // Check if trying to send to self
        if ($receiver->id === $user->id) {
            return response()->json([
                'valid'   => false,
                'message' => 'Cannot send money to yourself',
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'user'  => [
                'id'   => $receiver->id,
                'name' => $receiver->name,
            ],
        ]);
    }

    /**
     * Add money to the authenticated user's balance.
     */
    public function addMoney(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:999999999',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $user   = $request->user();
            $amount = $request->amount;

            \DB::transaction(function () use ($user, $amount) {
                $user->increment('balance', $amount);
            });

            return response()->json([
                'message' => 'Money added successfully',
                'balance' => $user->fresh()->balance,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add money',
            ], 400);
        }
    }
}
