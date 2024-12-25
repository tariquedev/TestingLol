<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use App\Transformers\UserTransformer;
use App\Http\Resources\UserResource;
use App\Models\Onboard;

class SocialAuthController extends Controller
{
    /**
     * Auth redirect with social redirect
     *
     * @param   string  $provider  [$provider description]
     *
     * @return  JSON
     */
    public function authRedirect($provider)
    {
        $url = Socialite::driver($provider)
                    ->stateless()
                    ->redirect()
                    ->getTargetUrl();

        return response()->json([
            'message'       => 'success',
            'data'          => [
                'redirect_url' => $url
            ]
        ]);
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
        }

        Auth::login($user);

        $user->load('onboard');

        $userData = $user->toArray(request());

        $userData['access_token'] = $user->createAuthToken();
        $userData['token_type'] = 'Bearer';

        return response()->json([
            'message'       => 'Login success',
            'data'          => $userData
        ]);
    }
}
