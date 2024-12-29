<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class StaticDataController extends Controller
{
    public function getProductTypes(): JsonResponse {
        $productTypes = [
            [
                'type' => 'coaching',
                'title' => 'Coaching Call',
                'description' => 'Book Discovery Calls, Paid Coaching',
                'image_url' => asset('product/coaching.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'group-call',
                'title' => 'Live Event',
                'description' => 'Host exclusive coaching sessions or events with multiple customers',
                'image_url' => asset('product/group-call.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'digital',
                'title' => 'Digital Download',
                'description' => 'PDFs, Guides, Templates, Exclusive Content, eBooks etc.',
                'image_url' => asset('product/digital.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'community',
                'title' => 'Community Hub',
                'description' => 'Host a free or paid community/social group link',
                'image_url' => asset('product/community.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'service',
                'title' => 'Service',
                'description' => 'Sell your service and get paid easily',
                'image_url' => asset('product/service.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'affiliate',
                'title' => 'E-commerce Affiliate',
                'description' => 'Add e-commerce affiliate links or share any links',
                'image_url' => asset('product/affiliate.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'flexpoint-affiliate',
                'title' => 'Flexpoint Affiliate Link',
                'description' => 'Refer friend and receive 20% of their Subscription fee each month!',
                'image_url' => asset('product/flexpoint-affiliate.svg'),
                'is_hot_deal' => true
            ]
        ];

        return response()->json([
            'message' => 'All Product Types',
            'data' => $productTypes
        ]);
    }

    public function authRedirect($provider)
    {
        $url = Socialite::driver($provider)
                    ->stateless()
                    ->redirect()
                    ->getTargetUrl();

        return redirect($url);
    }

    /**
     * Handle social auth
     *
     * @param   string  $provider
     *
     * @return  json
     */
    public function authCallback($provider, Request $request)
    {
        $payload = $request->payload;
        if (!$payload) {
            return response()->json(['error' => 'Payload not provided'], 400);
        }
        parse_str($payload, $parsedData);
        $code = $parsedData['code'] ?? null;

        $authUser = Socialite::driver($provider)
                ->with(['code' => $code])
                ->stateless()
                ->user();
        Log::info($authUser);
        $slug = Str::slug($authUser->name);
        $exists = User::where('username', $slug)->first();

        $user = User::where('email', $authUser->email)->first();
        if ($user) {
            $user->onboard()->updateOrCreate([], [
                'email_verification' => $provider == 'google' ? true : false,
            ]);
        }
        else{
            $user = User::create(['email' => $authUser->email],
            [
                'email'      => $authUser->email,
                'name'       => $authUser->name,
                'image'      => $authUser->avatar,
                'username'   => $exists ? $exists->username : $slug . Str::random(3)
            ]);
            $user->onboard()->updateOrCreate([], [
                'email_verification' => $provider == 'google' ? true : false,
            ]);
        }

        Auth::login($user);

        $user->load('onboard');

        $userData = $user->toArray(request());

        $userData['access_token'] = $user->createAuthToken();
        $userData['token_type'] = 'Bearer';

        return $userData;
    }
}
