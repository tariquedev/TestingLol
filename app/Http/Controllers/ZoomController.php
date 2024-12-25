<?php

namespace App\Http\Controllers;

use App\Models\Zoom;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomController extends Controller
{
    public function generateAuthLink()
    {
        $state = base64_encode(json_encode([
            'user_id' => Auth::id(),
            'scope' => 'zoom',
            // 'return_url' => route('dashboard'),
        ]));

        $authUrl = env('ZOOM_AUTH_URL') . '?' . http_build_query([
            'client_id' => env('ZOOM_CLIENT_ID'),
            'response_type' => 'code',
            'redirect_uri' => env('ZOOM_REDIRECT_URL'),
            'state' => $state,
        ]);

        return response()->json([
            'message' => 'Zoom Authentication URL',
            'authUrl' => $authUrl
        ]);
    }

    public function handleCallback(Request $request)
    {
        $payload = $request->payload;

        if (!$payload) {
            return response()->json(['error' => 'Payload not provided'], 400);
        }

        // Parse the payload
        parse_str($payload, $parsedData);

        $code = $parsedData['code'];
        $state = json_decode(base64_decode($parsedData['state']), true);

        if (!$code) {
            return response()->json([
                'message' => 'Authorization failed'
            ]);
        }

        $response = Http::asForm()->post(env('ZOOM_TOKEN_URL'), [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => env('ZOOM_REDIRECT_URL'),
            'client_id' => env('ZOOM_CLIENT_ID'),
            'client_secret' => env('ZOOM_CLIENT_SECRET'),
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to obtain access token'
            ]);
        }

        $data = $response->json();

        $userResponse = Http::withToken($data['access_token'])->get('https://api.zoom.us/v2/users/me');

        if ($userResponse->failed()) {
            return response()->json([
                'message' => 'Failed to fetch Zoom user information',
                'error' => $userResponse->json()
            ], 500);
        }

        $userData = $userResponse->json();

        $zData = Zoom::updateOrCreate(
            ['user_id' => $state['user_id']],
            [
                'client_id' => env('ZOOM_CLIENT_ID'),
                'client_secret' => env('ZOOM_CLIENT_SECRET'),
                'is_connected' => true,
                'refresh_token' => $data['refresh_token'],
                'access_token' => $data['access_token'],
                'token_uri' => env('ZOOM_TOKEN_URL'),
                'scopes' => json_encode($data['scope']),
                'zoom_user_id' => $userData['id'],
            ]
        );

        return response()->json([
            'message' => 'Zoom Connected Successfully',
            'data' => $zData
        ]);
    }

    public function disconnect() {

        $clientId = env('ZOOM_CLIENT_ID');
        $clientSecret = env('ZOOM_CLIENT_SECRET');

        // Encode client_id:client_secret as Base64
        $authHeader = base64_encode("$clientId:$clientSecret");
        // Make the revoke request
        if (!auth()->user()->zoomConnect) {
            return response()->json([
                'message' => "Error! Something went wrong",
            ]);
        }
        $response = Http::withHeaders([
            'Authorization' => "Basic $authHeader",
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post('https://zoom.us/oauth/revoke', [
            'token' => auth()->user()->zoomConnect->access_token,
        ]);
        // Delete Zoom Data from Database
        auth()->user()->zoomConnect->delete();
        if ($response->ok()) {
            return response()->json([
                'message' => "App successfully disconnected!",
            ]);
        } else {
            return response()->json([
                'message' => "Error! Something went wrong",
            ]);
        }
    }

    public function zoomConnectCheck(){
        if (auth()->user()->zoomConnect) {
            return response()->json([
                'is_connected' => true,
                'data' => auth()->user()->zoomConnect
            ]);
        }
        return response()->json([
            'is_connected' => false
        ]);
    }
}
