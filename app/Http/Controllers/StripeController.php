<?php

namespace App\Http\Controllers;

use App\Models\ConnectStripe;
use App\Models\User;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Stripe\Exception\ApiErrorException;
use Stripe\OAuth;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeController extends Controller
{
    public function generateOnboardingURL(Request $request){

        $validated = $request->validate([
                "returnURL" => ['required'],
                "refreshURL" => ['required']
            ]);

        $user = auth()->user();

        return response()->json([
            "Url" => $user->accountOnboardingUrl($validated['returnURL'], $validated['refreshURL'])
        ]);
    }
    public function userCheck(){
        $user = auth()->user();
        // Get store. This store has added the Billable trait and implements StripeAccount.
        // $store = Store::query()->find(1);

        // Transfer 10 USD to the store.
        $user->transferToStripeAccount(500);
        // return 'OK';
        // if (!$user->hasStripeAccount()) {
        //     $data = [
        //         'country' => 'US'
        //     ];
        //     $connectedAccount = $user->createAsStripeAccount('standard', $data);
        // }
        // else{
        //     return $this->handleBoardingRedirect($user);
        // }

        return response()->json($user);
    }


    /**
     * Handles returning from completing the onboarding process.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function returning(Request $request)
    {
        $user = auth()->user();
        $accountId = $request->stripe_account_id ?? $user->stripeConnect->stripe_account_id;

        $stripe = new StripeClient(env('STRIPE_SECRET'));
        $account = $stripe->accounts->retrieve($accountId);

        if ($account->details_submitted) {
            return response()->json([
                "message" => "Successfully connected your Stripe account!",
            ]);
        }

        return response()->json([
            "message" => "Account onboarding is incomplete.",
        ], 400);
    }

    /**
     * Handles refreshing of onboarding process.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function refresh(Request $request): RedirectResponse
    {
        return auth()->user()->refresh();
    }

    /**
     * Handles the redirection logic of Stripe onboarding for the given store. Will
     * create account and redirect store to onboarding process or redirect to account
     * dashboard if they have already completed the process.
     *
     * @param Store $Store
     * @return RedirectResponse
     */

    function stripeConnect(Request $request){
        $stripe = new StripeClient(env('STRIPE_SECRET'));

        $account = $stripe->accounts->create([
            'country' => $request->country_code ?? "US",
        ]);

        $account_link = $stripe->accountLinks->create([
            'account' => $account->id,
            'refresh_url' => url('api/stripe/refresh?stripe_account_id='.$account->id),
            'return_url'  => url('api/stripe/return?stripe_account_id='.$account->id),
            'type' => 'account_onboarding',
          ]);
        $user = auth()->user();

        if (!$user->stripeConnect) {
            $stripe = ConnectStripe::firstOrNew(['user_id'=> $user]);
            $stripe->stripe_account_id = $account->id;
            $stripe->user_id = $user->id;
            $stripe->country_code = $account->country;
            $stripe->save();
        }

        return response()->json([
            "message" => "Successfully Created Stripe Connected Account",
            "data" => [
                'payload' => $account_link->url
            ]
        ]);

    }

    function stripeDelete(){
        $user = auth()->user();
        $stripe = new StripeClient(env('STRIPE_SECRET'));
        if ($user->stripeConnect->stripe_account_id) {
            $stripe->accounts->delete($user->stripeConnect->stripe_account_id, []);
            $user->stripeConnect->delete();

            return response()->json([
                "message" => "Successfully Removed Stripe Account",
            ],200);
        }
        return response()->json([
            "message" => "Something went wrong",
        ],400);
    }

    public function isStripeConnected()
    {
        $user = auth()->user();
        if (!$user->stripeConnect) {
            return response()->json([
                'connected' => false,
                'message' => 'No Stripe account connected.',
            ], 400);
        }

        try {
            $stripe = new StripeClient(env('STRIPE_SECRET'));
            $account = $stripe->accounts->retrieve($user->stripeConnect->stripe_account_id);
            $isConnected = $account->charges_enabled && $account->payouts_enabled;
            if ($isConnected) {
                return response()->json([
                    'connected' => true,
                    'message' => 'Stripe account is fully connected.',
                    'data' => [
                        'stripe_id' => $user->stripeConnect->id,
                        'stripeData' => $account
                    ]
                ]);
            } else {
                return response()->json([
                    'connected' => true,
                    'message' => 'Stripe account connected but is not fully charges & payouts enabled.',
                    'data' => [
                        'stripe_id' => $user->stripeConnect->id,
                        'stripeData' => $account
                    ]
                ]);
            }
        } catch (ApiErrorException $e) {
            return response()->json([
                'connected' => false,
                'message' => 'Error connecting to Stripe: ' . $e->getMessage(),
            ], 500);
        }
    }

}
