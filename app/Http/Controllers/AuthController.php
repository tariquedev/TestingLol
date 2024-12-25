<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNameCheckRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\Country;
use App\Models\Onboard;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Transformers\UserTransformer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register new user
     *
     * @param   StoreUserRequest  $request  Handle user requested data
     *
     * @return  json
     */
    public function register(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        $emailRules = ['required', 'email'];
        if (!$user || !is_null($user->password)) {
            $emailRules[] = Rule::unique('users', 'email');
        }

        $rules = [
            'name'     => 'required|string|max:255',
            'email'    => $emailRules,
            'password' => ['required', Password::min(8)->numbers(),'regex:/[A-Z]/'],
            'country'  => 'required|in:' . implode(',', Country::getCodes()),
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userData = [
            'name'     => $request->name,
            'email'    => $request->email,
            'country'  => $request->country,
            'password' => bcrypt($request->password),
        ];

        if ($user) {
            $user->update($userData);
        } else {
            $user = User::create($userData);
        }

        $onboard = Onboard::firstOrNew(['user_id' => $user->id]);
        $onboard->user_id = $user->id;
        $onboard->checkout  = false;
        $onboard->email_verification = $onboard->wasRecentlyCreated ? true : false;
        $onboard->save();

        $user->assignRole('subscriber');

        return response()->json([
            'message'       => 'Registration success',
            'data'          => [
                'access_token' => $user->createAuthToken(),
                'token_type'   => 'Bearer',
            ]
        ]);
    }

    /**
     * Handle user login
     *
     * @param   UserLoginRequest  $request
     *
     * @return  json
     */
    public function login(UserLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'User not found',
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'message'       => 'Login success',
            'data'          => [
                'access_token' => $user->createAuthToken(),
                'token_type'   => 'Bearer',
            ]
        ]);
    }

    /**
     * Handle user logout
     *
     * @return  json
     */
    public function logout()
    {
        Auth::user()->tokens()->each(function($token, $key){
            $token->delete();
        });

        return response()->json([
            'message' => 'Logout Successfull'
        ]);
    }

    /**
     * Check store name
     *
     * @param   StoreNameCheckRequest  $request
     *
     * @return  JSON
     */
    public function checkStoreName($store_name, Request $request)
    {
        // Define reserved names
        $reservedNames = [
            'blog', 'user', 'profile', 'flexpoint', 'support',
            'about', 'terms', 'service', 'policy', 'gdpr',
            'article', 'password'
        ];

        $validator = Validator::make(['storename' => $store_name], [
            'storename' => [
                'required',
                'string',
                'min:3',
                'regex:/^[A-Za-z0-9\-]+$/',
            ]
        ]);

        // Check validation result
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (in_array($store_name, $reservedNames)) {
            return response()->json([
                'message'   => 'Check store name',
                'data'      => [
                    'is_taken'  => true
                ]
            ]);
        }
        // Check if the username exists
        $isAvailable = User::where('store_name', $store_name)->exists();

        // Return a JSON response
        return response()->json([
            'message'   => 'Check store name',
            'data'      => [
                'is_taken'  => $isAvailable
            ]
        ]);
    }

    /**
     * Check user
     *
     * @param   [type]  $email  [$email description]
     *
     * @return  [type]          [return description]
     */
    public function checkUser($email) {
        // Check if a user exists with the given email
        $user = User::where('email', $email)->first();

        // If user exists, return a success response
        if ($user) {
            return response()->json([
                'message' => 'User exists',
                'data'    => [
                    'name'  => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->image ?? asset('images/demo.webp'),
                ] // Optionally include user details
            ], 200);
        }

        // If no user exists, return an error response
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }
}
