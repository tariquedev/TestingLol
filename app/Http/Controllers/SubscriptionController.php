<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Models\Plan;
use App\Transformers\PlanTransformer;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Stripe\PaymentMethod;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class SubscriptionController extends Controller
{
    /**
     * Get all subscription plans
     *
     * @return  array
     */
    public function getPlans()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $products = Product::all();
        $productData = [];

        foreach ($products->data as $key => $product) {
            $prices = Price::all(['product' =>$product->id]);
            $productData = [
                'product' => $product,
                'prices' => $prices->data,
            ];
        }
        return response()->json([$productData]);
        // $plans = Plan::all();

        // return  fractal($plans, new PlanTransformer)->toArray();
    }

    /**
     * Get payment intent
     *
     * @param   Request  $request
     *
     * @return  [type]             [return description]
     */
    public function getPaymentIntent(Request $request)
    {
        $secret = auth()->user()->createSetupIntent()->client_secret;
        return response()->json([
            'message'   => 'Client Secret',
            'data'      => [
                'client_secret'  => $secret
            ]
        ]);
    }
     /**
     * Get payment methods
     *
     * @param   Request  $request
     *
     * @return  [type]             [return description]
     */
    public function getSubscriptionDetails()
    {
        $user = auth()->user();

        // Check if the user has an active subscription
        if (!$user->subscribed('default')) {
            return response()->json([
                'message' => 'No active subscription found.'
            ], 404);
        }
        Stripe::setApiKey(env('STRIPE_SECRET'));
        // Get the subscription details
        $subscription = $user->subscription('default');
        $stripeSubscription = $subscription->asStripeSubscription();

        $paymentMethod = $user->defaultPaymentMethod();
        $planDetails = Price::retrieve($subscription->stripe_price);
        $data = [
            'subscription' => [
                'status' => $subscription->stripe_status,
                'plan' => [
                    'price' => $planDetails->unit_amount,
                    'currency' => $planDetails->currency,
                    'recurring' => $planDetails->recurring->interval
                ],
                'start_date' => $subscription->created_at->toDateString(),
                'next_billing_date' => date('Y-m-d', $stripeSubscription->current_period_end),
                'ends_at' => $subscription->ends_at ? $subscription->ends_at->toDateString() : null,
                'trial_ends_at' => $subscription->trial_ends_at ? $subscription->trial_ends_at->toDateString() : null,
            ],
            'payment_method' => $paymentMethod ? [
                'type' => $paymentMethod->card->brand,
                'last_four' => $paymentMethod->card->last4,
                'exp_month' => $paymentMethod->card->exp_month,
                'exp_year' => $paymentMethod->card->exp_year,
                'billing_details' => [
                    'name' => $paymentMethod->billing_details->name,
                    'email' => $paymentMethod->billing_details->email,
                ],
            ] : null,
        ];

        return response()->json($data);

    }

    /**
     * Subscribe user
     *
     * @param   SubscribeRequest  $request  [$request description]
     *
     * @return  json
     */
    // public function subscribe(SubscribeRequest $request)
    // {
    //     $plan = Plan::where('slug', $request->plan)
    //         ->orWhere('slug', 'monthly')
    //         ->first();

    //     auth()->user()->newSubscription('default', $plan->stripe_price_id)
    //         ->create($request->token);

    //     return response()->json([
    //         'message' => 'Successfully subscribed'
    //     ]);
    // }
    public function subscribe(SubscribeRequest $request)
    {
        auth()->user()->newSubscription('default', $request->stripe_price_id)
            ->create($request->payment_method_id);

        return response()->json([
            'message' => 'Successfully subscribed'
        ]);
    }

    /**
     * Unscribe user subscriptions
     *
     * @param   Request  $request  [$request description]
     *
     * @return  json
     */
    public function unsubscribe(Request $request)
    {
        $subscription = auth()->user()->subscription('default');

        $subscription->cancel();

        return response()->json([
            'message' => 'Successfully unsubscribed'
        ]);
    }

    /**
     * Resume user subscription
     *
     * @param   Request  $request  [$request description]
     *
     * @return  json
     */
    public function resume(Request $request)
    {
        $subscription = auth()->user()->subscription('default');

        $subscription->resume();

        return response()->json([
            'message' => 'Successfully subscription resume'
        ]);
    }

    public function deletePaymentMethod(){
        $user = auth()->user();

        if ($user->hasPaymentMethod()) {
            $user->deletePaymentMethods();
            return response()->json([
                'message' => "Payment Method removed successfully"
            ],200);
        }

        return response()->json([
            'message' => "Payment Method not found"
        ], 404);
    }

    public function addOrUpdateCard(Request $request)
    {
        $user = auth()->user();
        try {

            $user->addPaymentMethod($request->payment_method_id);
            $user->updateDefaultPaymentMethod($request->payment_method_id);
            $this->removeOtherPaymentMethods($user, $request->payment_method_id);

            return response()->json([
                'message' => 'Card Updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    private function removeOtherPaymentMethods($user, $keepPaymentMethodId)
    {
        $paymentMethods = $user->paymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->id !== $keepPaymentMethodId) {
                $paymentMethod->delete();
            }
        }
    }
}
