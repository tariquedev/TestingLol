<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ZoomData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ZoomController extends Controller
{
    // public function connect()
    // {
    //     $clientId = env('ZOOM_CLIENT_ID');
    //     $redirectUri = env('ZOOM_REDIRECT_URL');

    //     $zoomAuthUrl = "https://zoom.us/oauth/authorize";
    //     $params = http_build_query([
    //         'response_type' => 'code',
    //         'client_id' => $clientId,
    //         'redirect_uri' => $redirectUri,
    //     ]);

    //     return redirect("$zoomAuthUrl?$params");
    // }

    // public function callback(Request $request)
    // {
    //     $code = $request->get('code');
    //     if (!$code) {
    //         return redirect('/')->with('error', 'Authorization failed.');
    //     }

    //     $client = new \GuzzleHttp\Client();
    //     $response = $client->post('https://zoom.us/oauth/token', [
    //         'form_params' => [
    //             'grant_type' => 'authorization_code',
    //             'code' => $code,
    //             'redirect_uri' => route('zoom.callback'),
    //         ],
    //         'headers' => [
    //             'Authorization' => 'Basic ' . base64_encode(env('ZOOM_CLIENT_ID') . ':' . env('ZOOM_CLIENT_SECRET')),
    //         ],
    //     ]);

    //     $data = json_decode($response->getBody(), true);
    //     if (isset($data['access_token'])) {
    //         // Save the tokens (access and refresh) in the database
    //         $user = auth()->user();
    //         $user->update([
    //             'zoom_access_token' => $data['access_token'],
    //             'zoom_refresh_token' => $data['refresh_token'],
    //         ]);

    //         return $data;
    //     }

    //     return 'Failed to connect Zoom.';
    // }

    // public function disconnect()
    // {
    //     $user = auth()->user();

    //     if (!$user->zoom_access_token) {
    //         return response()->json(['error' => 'Zoom is not connected'], 400);
    //     }

    //     $client = new \GuzzleHttp\Client();
    //     $response = $client->post('https://zoom.us/oauth/revoke', [
    //         'form_params' => [
    //             'token' => $user->zoom_access_token,
    //         ],
    //     ]);

    //     // Remove tokens from the database
    //     $user->update([
    //         'zoom_access_token' => null,
    //         'zoom_refresh_token' => null,
    //     ]);

    //     return $response;
    // }

    public function generateAuthLink()
    {
        $state = base64_encode(json_encode([
            'user_id' => Auth::id(),
            'scope' => 'zoom',
            'return_url' => route('dashboard'),
        ]));

        $authUrl = env('ZOOM_AUTH_URL') . '?' . http_build_query([
            'client_id' => env('ZOOM_CLIENT_ID'),
            'response_type' => 'code',
            'redirect_uri' => env('ZOOM_REDIRECT_URI'),
            'state' => $state,
        ]);
        return view('welcome', [
            'authUrl' => $authUrl
        ]);
    }

    public function handleCallback(Request $request)
    {
        $code = $request->input('code');
        $state = json_decode(base64_decode($request->input('state')), true);

        if (!$code) {
            return 'Authorization failed';
        }

        // Exchange code for access token
        // $response = Http::asForm()->withBasicAuth(
        //     env('ZOOM_CLIENT_ID'),
        //     env('ZOOM_CLIENT_SECRET')
        // )->post(env('ZOOM_TOKEN_URL'), [
        //     'grant_type' => 'authorization_code',
        //     'code' => $code,
        //     'redirect_uri' => env('ZOOM_REDIRECT_URI'),
        // ]);

        $response = Http::asForm()->post('https://zoom.us/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => env('ZOOM_REDIRECT_URI'),
            'client_id' => env('ZOOM_CLIENT_ID'),
            'client_secret' => env('ZOOM_CLIENT_SECRET'),
        ]);

        if ($response->failed()) {
            return 'Failed to obtain access token';
        }

        $data = $response->json();
        // Save access token, refresh token, etc., to the database (customize this as needed)
        // For example:

        $userResponse = Http::withToken($data['access_token'])->get('https://api.zoom.us/v2/users/me');

        // Debug response
        if ($userResponse->failed()) {
            return response()->json([
                'message' => 'Failed to fetch Zoom user information',
                'error' => $userResponse->json()
            ], 500);
        }

        $userData = $userResponse->json();
        // User::find($state['user_id'])->update(['zoom_token' => $data['access_token']]);
        $zData = ZoomData::updateOrCreate(
            ['user_id' => $state['user_id']],
            [
                'client_id' => env('ZOOM_CLIENT_ID'),
                'client_secret' => env('ZOOM_CLIENT_SECRET'),
                'is_connected' => true,
                'refresh_token' => $data['refresh_token'],
                'token' => $data['access_token'],
                'token_uri' => 'https://zoom.us/oauth/token',
                'scopes' => $data['scope'], // Example: "meeting:read,user:write"
                'zoom_user_id' => $userData['id'], // Add based on the response
            ]
        );

        return $zData .'<pre> New'. $userData;
    }
}
