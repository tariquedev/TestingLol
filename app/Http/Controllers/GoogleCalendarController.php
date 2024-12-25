<?php

namespace App\Http\Controllers;

use App\Models\User;
use Google\Client;
use Google\Service\Calendar;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    // Redirect user to Google OAuth
    public function redirectToGoogle()
    {
        $client = $this->getGoogleClient();
        $authUrl = $client->createAuthUrl();

        return response()->json(['url' => $authUrl]);
    }

    // Handle OAuth callback and store tokens
    public function handleGoogleCallback(Request $request)
    {
        $client = $this->getGoogleClient();
        $payload = $request->payload;

        if (!$payload) {
            return response()->json(['error' => 'Payload not provided'], 400);
        }

        // Parse the payload
        parse_str($payload, $parsedData);

        if (!$parsedData['code']) {
            return response()->json(['error' => 'Authorization code not provided'], 400);
        }

        $token = $client->fetchAccessTokenWithAuthCode($parsedData['code']);

        if (isset($token['error'])) {
            return response()->json(['error' => $token['error']], 400);
        }

        $user = auth()->user();

        // Save tokens to the database
        $user->googleCalendarToken()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($token['expires_in']),
            ]
        );

        return response()->json(['message' => 'Google account connected successfully']);
    }

    // Create an event in Google Calendar


    private function getGoogleClient()
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.calendar_redirect'));
        $client->addScope(Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function disconnect()
    {
        $user = auth()->user();
        $user->googleCalendarToken()->delete();

        return response()->json([
            'message' => 'Google Calendar disconnected'
        ]);
    }
    public function isCalendarConnected()
    {
        $user = auth()->user();

        if (!$user->googleCalendarToken) {
            return response()->json([
                'is_connected' => false,
                'message' => 'No Google account Connected.',
            ], 200);
        }

        return response()->json([
            'is_connected' => true,
            'message' => 'Google Calendar Connected.',
        ], 200);

    }
}
