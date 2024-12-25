<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\{
    AppointmentController,
    MediaController,
    StaticDataController, StoreController, StripeController,TestController,WithdrawMethodController,ZoomController,
};
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\PaymentMethod;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Stripe;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
});

// User routes.
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/users', [ProfileController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::get('/profile', [ProfileController::class, 'getUser']);
    Route::get('/currentUser', [ProfileController::class, 'getCurrentUser']);
    Route::patch('/change-password', [ProfileController::class, 'changePassword']);
    Route::patch('/onboard', [ProfileController::class, 'updateOnboard']);
    Route::get('/branding', [ProfileController::class, 'updateBranding']);

    // Bank details.

    Route::get('/banks', [BankAccountController::class, 'index']);
    Route::post('/add-bank-account', [BankAccountController::class, 'store']);
    Route::put('/banks/{bank}', [BankAccountController::class, 'update']);
    Route::delete('/banks/{bank}', [BankAccountController::class, 'delete']);

    // Stripe Connect
    Route::post('/connect/stripe', [StripeController::class, 'stripeConnect']);
    // Route::get('/connect/transfer-money', [StripeController::class, 'userCheck']);
    Route::get('/stripe/return', [StripeController::class, 'returning'])->name('stripeReturn');
    Route::get('/stripe/refresh', [StripeController::class, 'refresh'])->name('stripeRefresh');
    Route::delete('/remove-stripe', [StripeController::class, 'stripeDelete'])->name('stripeDelete');
    Route::get('/stripe/connected', [StripeController::class, 'isStripeConnected'])->name('isStripeConnected');
    // Wise Connect
    Route::post('/connect-wise', [WithdrawMethodController::class, 'wiseConnect']);
    Route::get('/get-wise-details', [WithdrawMethodController::class, 'getWiseDetails']);
    Route::delete('/remove-wise', [WithdrawMethodController::class, 'wiseRemove']);

    Route::put('/set-default-withdraw-method', [WithdrawMethodController::class, 'setDefaultWithdrawMethod']);
    Route::get('/get-default-withdraw-method', [WithdrawMethodController::class, 'getDefaultWithdrawMethod']);
});
// Subscription Route.
Route::prefix('subscription')->group(function() {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/packages', [SubscriptionController::class, 'getPlans']);
        Route::get('/payment-intent', [SubscriptionController::class, 'getPaymentIntent']);
        Route::get('/plan', [SubscriptionController::class, 'getSubscriptionDetails']);
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
        Route::get('/unsubscribe', [SubscriptionController::class, 'unsubscribe']);
        Route::get('/resume', [SubscriptionController::class, 'resume']);
        Route::delete('/remove-payment-method', [SubscriptionController::class, 'deletePaymentMethod']);
        Route::post('/add-or-update-card', [SubscriptionController::class, 'addOrUpdateCard']);
    });
});
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/check-storename/{store_name}', [AuthController::class, 'checkStoreName']);
    Route::post('/image-upload', [ProductController::class, 'uploadImage']);
});
Route::get('/check-user/{email}', [AuthController::class, 'checkUser']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [PasswordController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordController::class, 'resetPassword']);

// Country endpoint.
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/currencies', [CountryController::class, 'getCurrencySymbols']);
Route::get('/product-types', [StaticDataController::class, 'getProductTypes']);
// Route::get('/country', [CountryController::class, 'getCountry']);
// Route::get('/ip', [CountryController::class, 'getIp']);

// Social auth.
Route::get('/auth/{provider}/url', [SocialAuthController::class, 'authRedirect']);
Route::post('/auth/{provider}/verify', [SocialAuthController::class, 'authCallback']);

// Products Route.
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'delete']);
    Route::post('/products/reordering', [ProductController::class, 'reordering']);
});
// Google Calendar
// Route::middleware(['auth:sanctum'])->group(function () {
//     Route::get('google-calendar/connect', [GoogleCalendarController::class, 'redirectToGoogle'])->name('google.redirect');
//     Route::post('google-calendar/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])->name('google.callback');
//     Route::get('google/calendar', [GoogleCalendarController::class, 'showCalendarEvents'])->name('google.calendar');
//     Route::get('google-calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google.disconnect');
//     Route::get('google-calendar/connected', [GoogleCalendarController::class, 'isCalendarConnected'])->name('google.isCalendarConnected');
// });
Route::middleware('auth:sanctum')->group(function () {
    // Google Calendar Integration
    Route::prefix('google-calendar')->group(function () {
        Route::get('/connect', [GoogleCalendarController::class, 'redirectToGoogle'])->name('google.redirect');
        Route::post('/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])->name('google.callback');
        Route::get('/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google.disconnect');
        Route::get('/connected', [GoogleCalendarController::class, 'isCalendarConnected'])->name('google.isCalendarConnected');
        Route::get('/calendar', [GoogleCalendarController::class, 'showCalendarEvents'])->name('google.calendar');
    });
// ZOOM Connect
    Route::prefix('zoom')->group(function () {
        Route::get('connect', [ZoomController::class, 'generateAuthLink'])->name('zoom.connect');
        Route::post('callback', [ZoomController::class, 'handleCallback'])->name('zoom.callback');
        Route::get('disconnect', [ZoomController::class, 'disconnect'])->name('zoom.disconnect');
        Route::get('connected', [ZoomController::class, 'zoomConnectCheck'])->name('zoom.check');
    });
});
// Appointment
Route::post('/appointment', [AppointmentController::class, 'storeAppointment']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/get/my-appointments', [AppointmentController::class, 'getAppointments']);
    Route::get('/appointment/details/{slug}', [AppointmentController::class, 'singleAppointment']);
    Route::get('/orders', [AppointmentController::class, 'myOrders']);
});
// Store Details
Route::get('/{store_name}/product/{slug}', [StoreController::class, 'storeDetails']);
Route::get('/get-calendar/{slug}', [StoreController::class, 'storeCalendarDetails']);
Route::get('/available-slot/{slug}/{date}', [StoreController::class, 'getAvailableSlots']);

Route::get('/{store_name}', [StoreController::class, 'publicStore']);

// Temp Route
Route::delete('/delete/{user}', [MediaController::class, 'deleteUser']);