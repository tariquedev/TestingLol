<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\User;
use Google\Service\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    function deleteUser($user){

        // $client = new \Google_Client();
        // $client->setClientId(config('services.google.client_id'));
        // $client->setClientSecret(config('services.google.client_secret'));
        // $client->setRedirectUri(config('services.google.redirect'));
        // $client->addScope(Calendar::CALENDAR);
        // $client->setAccessType('offline');
        // $client->setPrompt('consent');

        // $client->setConfig('calendar', ['conferenceDataVersion' => 1]);
        // // Retrieve the stored tokens
        // $user = auth()->user() ?? User::find(5); // Assuming user authentication
        // $googleCalendarToken = $user->googleCalendarToken;

        // if ($googleCalendarToken) {
        //     $client->setAccessToken([
        //         'access_token' => $googleCalendarToken->access_token,
        //         'refresh_token' => $googleCalendarToken->refresh_token,
        //         'expires_in' => $googleCalendarToken->token_expires_at->diffInSeconds(now()),
        //     ]);
        //     // Refresh the token if it's expired
        //     if ($client->isAccessTokenExpired()) {
        //         $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        //         $newAccessToken = $client->getAccessToken();
        //         Log::info('newAccessToken>' . $newAccessToken['access_token'] .'refresh_token>'.$newAccessToken['refresh_token']);
        //         if (!isset($newAccessToken['error'])) {
        //             // Save the new token details back to the database
        //             $googleCalendarToken->update([
        //                 'access_token' => $newAccessToken['access_token'],
        //                 'refresh_token' => $newAccessToken['refresh_token'] ?? $googleCalendarToken->refresh_token,
        //                 'token_expires_at' => now()->addSeconds($newAccessToken['expires_in']),
        //             ]);
        //         } else {
        //             throw new \Exception('Unable to refresh Google OAuth token.');
        //         }
        //     }
        // } else {
        //     throw new \Exception('No Google Calendar token found for the user.');
        // }
        // return $client;

        $data = User::where('email', $user)->firstOrFail();

        if ($user) {
            $data->onboard->delete();
            $data->delete();
        }

        return response()->json([
            'status' => $user." Associated Account Deleted Successfully",
        ]);
    }

    public function show($mediaId, $fileName)
    {
        $media = Media::findOrFail($mediaId);
        if ($fileName && $fileName !== $media->file_name) {
            abort(404, 'File not found.');
        }
        $file_name = $media->file_name;
        $disk = Storage::disk($media->disk); // 's3' disk
        $filePath = "media/{$media->id}/{$file_name}"; // Path to file in S3
        if (!$disk->exists($filePath)) {
            abort(404, 'File not found.');
        }
        return response()->stream(function () use ($disk, $filePath) {
            echo $disk->get($filePath);
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }
}
