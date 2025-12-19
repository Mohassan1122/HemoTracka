<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\BloodRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * List transaction history (Card Transactions) for the logged-in user's organization.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $payments = Payment::where('user_id', $user->id)
            ->with(['bloodRequest.organization'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Process a simulated payment.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'blood_request_id' => 'required|exists:blood_requests,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:Card,Bank Transfer,POD',
            'card_details' => 'required_if:payment_method,Card|array',
        ]);

        $bloodRequest = BloodRequest::find($request->blood_request_id);

        // Security check: ensure user belongs to the organization that made the request
        if ($user->organization_id !== $bloodRequest->organization_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Simulate external payment processing
        $status = ($request->payment_method === 'POD') ? 'Pending' : 'Completed';
        $reference = ($request->payment_method === 'POD') ? null : 'TXN-' . strtoupper(Str::random(12));

        $payment = Payment::create([
            'user_id' => $user->id,
            'blood_request_id' => $bloodRequest->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => $status,
            'transaction_reference' => $reference,
            'payment_details' => $request->card_details ?? [],
        ]);

        if ($status === 'Completed') {
            $bloodRequest->update(['status' => 'Paid']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => $payment
        ], 201);
    }
}
