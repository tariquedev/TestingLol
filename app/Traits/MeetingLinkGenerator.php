<?php

namespace App\Traits;

use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait MeetingLinkGenerator
{
    private function createMeeting($type, $details)
    {
        if ($type === 'google-meet') {
            $event = $this->createGoogleCalendarEvent($details);
            return [
                'link' => $event->conferenceData->entryPoints[0]->uri,
                'event_id' => $event->getId(),
            ];
        } elseif ($type === 'zoom') {
            $meeting = $this->createZoomMeeting($details);

            return [
                'link' => $meeting['join_url'],
                'event_id' => $meeting['id'],
            ];
        }
        throw new \Exception('Unsupported meeting type');
    }

    private function createGoogleCalendarEvent($details)
    {
        $client = $this->getGoogleClient($details['user_id']);
        $service = new Calendar($client);
        $startTime = Carbon::parse($details['date'].$details['start_time'], $details['visitor_timezone']->toIso8601String() ?? $details['timezone'])->toIso8601String();
        $endTime = Carbon::parse($details['date'].$details['end_time'], $details['visitor_timezone']->toIso8601String() ?? $details['timezone'])->toIso8601String();
        if ($endTime <= $startTime) {
            throw new \Exception('End time must be after start time.');
        }

        if (!empty($details['event_id'])) {
            $event = $service->events->get('primary', $details['event_id']);

            $event->setAttendees([['email' => $details['attendee_email']]]);
            $event = $service->events->update('primary', $event->getId(), $event);
            return $event;
        }
        $event = new Event([
            'summary' => $details['title'],
            'start' => [
                'dateTime' => $startTime,
            ],
            'end' => [
                'dateTime' => $endTime,
            ],
            'attendees' =>[
                'email' => $details['attendee_email']
            ],
            'conferenceData' => [
                'createRequest' => [
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet', // Create a Google Meet link
                    ],
                    'requestId' => 'meet-' . uniqid(), // Unique request ID
                ],
            ],
        ]);

        $calendarId = 'primary';
        $event = $service->events->insert($calendarId, $event, ['conferenceDataVersion' => 1]);

        return $event;
    }

    private function createZoomMeeting($details)
    {
        $user = User::find($details['user_id'])->zoomConnect;

        $this->refreshZoomToken($user);
        $zoomAccessToken = $user->access_token;
        $response = Http::withToken($zoomAccessToken)->get('https://api.zoom.us/v2/users/me/token');

            $tokenInfo = $response->json();
        Log::info($tokenInfo);

        $headers = [
            'Authorization' => 'Bearer ' . $zoomAccessToken,
            'Content-Type' => 'application/json',
        ];

        if (!empty($details['event_id'])) {
            $this->addAttendeeToZoomMeeting($headers, $details['event_id'], $details);
        }

        $startTime = Carbon::parse($details['date'] . ' ' . $details['start_time'], $details['visitor_timezone']->toIso8601String() ?? $details['timezone'])->toIso8601String();

        $meetingDetails = [
            'topic' => $details['title'] ?? 'Meeting',
            'type' => 2, // Scheduled meeting
            'start_time' => $startTime,
            'duration' => (int)$details['duration'],
            'timezone' => $details['visitor_timezone'] ?? $details['timezone'],
            'settings' => [
                'join_before_host' => $details['settings']['join_before_host'] ?? false,
                'mute_upon_entry' => $details['settings']['mute_upon_entry'] ?? true,
                'waiting_room' => $details['settings']['waiting_room'] ?? true,
            ],
        ];
        $response = Http::withHeaders($headers)
        ->withBody(json_encode($meetingDetails), 'application/json')
        ->post('https://api.zoom.us/v2/users/me/meetings');

        if (!$response->successful()) {
            throw new \Exception('Failed to create Zoom meeting: ' . $response->body());
        }

        $meeting = $response->json();
        $meetingId = $meeting['id'];

        $registrantDetails = [
            'email' => $details['attendee_email'],
            'first_name' => $details['attendee_name'],
        ];

        $addRegistrantResponse = Http::withHeaders($headers)
            ->withBody(json_encode($registrantDetails), 'application/json')
            ->post("https://api.zoom.us/v2/meetings/{$meetingId}/registrants");

        if (!$addRegistrantResponse->successful()) {
            throw new \Exception('Failed to add attendee to Zoom meeting: ' . $addRegistrantResponse->body());
        }

        $registrant = $addRegistrantResponse->json();

        return $registrant;

    }
    private function refreshZoomToken($user)
    {
        $clientId = env('ZOOM_CLIENT_ID');
        $clientSecret = env('ZOOM_CLIENT_SECRET');

        $response = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $user->refresh_token,
            ]);

        if ($response->ok()) {
            $data = $response->json();
            $user->access_token = $data['access_token'];
            $user->refresh_token = $data['refresh_token'];
            $user->save();

            return $data['access_token'];
        }

        throw new \Exception('Unable to refresh Zoom token: ' . $response->body());
    }

    private function getGoogleClient($user)
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->addScope(Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $user = User::find($user);

        if (!$user->googleCalendarToken) {
            throw new \Exception('Google tokens not found for the user.');
        }
        $googleToken = $user->googleCalendarToken;
        if (Carbon::now()->greaterThan(Carbon::parse($googleToken->token_expires_at))) {
            $client->setAccessToken([
                'access_token' => $googleToken->access_token,
                'refresh_token' => $googleToken->refresh_token,
                'expires_in' => Carbon::parse($googleToken->token_expires_at)->diffInSeconds()
            ]);

            if ($client->isAccessTokenExpired()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($googleToken->refresh_token);

                if (isset($newToken['error'])) {
                    throw new \Exception('Failed to refresh access token: ' . $newToken['error']);
                }
                $googleToken->update([
                    'access_token' => $newToken['access_token'],
                    'token_expires_at' => Carbon::now()->addSeconds($newToken['expires_in']),
                ]);
            }
        } else {
            $client->setAccessToken([
                'access_token' => $googleToken->access_token,
                'refresh_token' => $googleToken->refresh_token,
                'expires_in' => Carbon::parse($googleToken->token_expires_at)->diffInSeconds()
            ]);
        }
        return $client;
    }

    private function addAttendeeToZoomMeeting($headers, $meetingId, $details)
    {
        $registrantDetails = [
            'email' => $details['attendee_email'],
            'first_name' => $details['attendee_name'],
        ];

        $response = Http::withHeaders($headers)
            ->post("https://api.zoom.us/v2/meetings/{$meetingId}/registrants", json_encode($registrantDetails));

        if (!$response->successful()) {
            throw new \Exception('Failed to add attendee to Zoom meeting: ' . $response->body());
        }

        return $response->json();
    }
}
