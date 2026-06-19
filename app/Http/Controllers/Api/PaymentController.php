<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RazorpayConfigService;
use Illuminate\Http\Request;
use Exception;

class PaymentController extends Controller
{
    public function __construct(private RazorpayConfigService $razorpay) {}
    public function index(Request $request)
    {
        return response()->json($request->user()->payments()->latest()->paginate(20));
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'plan_id' => 'required',
        ]);

        if (!$this->razorpay->isConfigured()) {
            return response()->json(['error' => 'Payment gateway is not configured'], 503);
        }

        $api = $this->razorpay->api();

        try {
            $orderData = [
                'receipt'         => 'rcptid_' . time(),
                'amount'          => $request->amount * 100, // Amount in paise
                'currency'        => 'INR',
                'payment_capture' => 1 // auto capture
            ];

            $razorpayOrder = $api->order->create($orderData);

            return response()->json([
                'order_id' => $razorpayOrder['id'],
                'amount' => $orderData['amount'],
                'currency' => $orderData['currency'],
                'key' => $this->razorpay->keyId()
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'plan_id' => 'required'
        ]);

        if (!$this->razorpay->isConfigured()) {
            return response()->json(['success' => false, 'error' => 'Payment gateway is not configured'], 503);
        }

        $api = $this->razorpay->api();

        try {
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $api->utility->verifyPaymentSignature($attributes);

            // Payment is successful.
            $plan = \App\Models\Plan::findOrFail($request->plan_id);
            $user = $request->user();
            
            // Create Subscription
            $periodLow = strtolower($plan->period);
            $num = (int)filter_var($periodLow, FILTER_SANITIZE_NUMBER_INT) ?: 1;
            $periodDays = match (true) {
                str_contains($periodLow, 'year') => 365,
                str_contains($periodLow, 'lifetime') => 36500,
                default => $num * 30,
            };
            \App\Models\Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays($periodDays),
            ]);

            // Add Quotas
            $user->contact_quota += $plan->contact_quota ?? 0;
            $user->message_quota += $plan->message_quota ?? 0;
            $user->credits += $plan->credits ?? 0;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Payment successful']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
