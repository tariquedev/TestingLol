<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ImageResource;
use App\Http\Resources\UserResource;
use App\Models\Onboard;
use App\Models\User;
use App\Models\UserDetails;
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function index()
    {
        $users = User::all();

        return UserResource::collection($users);
    }

    public function getUser()
    {
        $user = Auth::user();

        $userData = (new UserResource($user))->toArray(request());

        return response()->json([
            'message' => 'Get user profile',
            'data'    => $userData,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        if($user::isReservedKeyword($request->store_name)){
            return response()->json([
                'message'   => 'Check store name',
                'data'      => [
                    'is_taken'  => true
                ]
            ]);
        }

        $user->username    = $request->get('username', $user->username);
        $user->store_name  = $request->get('store_name', $user->store_name);
        $user->name        = $request->get('name', $user->name);
        $user->email       = $request->get('email', $user->email);
        $user->phone       = $request->get('phone', $user->phone);
        $user->country     = $request->get('country', $user->country);
        $user->currency    = $request->get('currency', $user->currency);
        $user->currency_symbol    = $request->get('currency_symbol', $user->currency_symbol);
        $user->password    = $request->get('password', $user->password);

        $user->save();

        if ($request->has('avatar_media_id') && $request->avatar_media_id) {
            if ($user->hasMedia('avatar')) {
                $user->clearMediaCollection('avatar');
            }
            ImageResource::associateImageWithModel($request->avatar_media_id, $user, $request->collectionName ?? 'avatar');
        }

        $userDetails = UserDetails::firstOrNew(['user_id' => $user->id]);
        $userDetails->bio = $request->get('bio', $userDetails->bio);
        $userDetails->occupation = $request->get('occupation', $userDetails->occupation);
        $userDetails->social_links = $request->get('social_links', $userDetails->social_links);
        $userDetails->save();

        return response()->json([
            'message' => 'Updated user profile',
        ]);
    }

    /**
     * Get current loggedin user
     *
     * @param   Request  $request  [$request description]
     *
     * @return  array
     */
    public function getCurrentUser(Request $request)
    {
        $user = Auth::user();

        $userData = (new UserResource($user))->toArray(request());

        return response()->json([
            'message' => 'Current user profile',
            'data'    => $userData,
        ]);
    }

    /**
     * Change user password
     *
     * @param   Request  $request  [$request description]
     *
     * @return  JSON
     */
    public function changePassword(Request $request)
    {
        $rules = [
            'password'  => ['required', Password::min(8)->numbers(),'regex:/[A-Z]/']
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'message'       => 'Successfully updated password'
        ]);
    }

    public function updateOnboard(Request $request)
    {
        $user = Auth::user();
        $onboard = $user->onboard;
        $onboard->update([
            'checkout' => $request->checkout ?? $onboard->checkout,
            'email_verification' => $request->email_verification ?? $onboard->email_verification
        ]);

        return response()->json([
            'message'       => 'Successfully updated onboard'
        ]);
    }
    public function updateBranding(Request $request)
    {
        $user = Auth::user();
        $user->branding = $user->branding == true ? false : true;
        $user->save();

        $data = $user->branding == true ? 'Enabled' : 'Disabled';

        return response()->json([
            'message'       => 'Successfully '. $data .' Branding'
        ]);
    }
}
