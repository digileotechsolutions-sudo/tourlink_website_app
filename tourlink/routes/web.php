<?php

use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminCatalogController;
use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminFinanceController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\AdminModerationController;
use App\Http\Controllers\AdminReferralController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminReviewController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\AdminTripController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminVehicleController;
use App\Http\Controllers\AdminVerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MpesaPaymentController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TravelerController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleOwnerController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\RequireTermsAcceptance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/terms', fn () => view('pages.terms'))->name('terms');
Route::post('/terms/accept', function (Request $request) {
    $request->validate(['accepted' => ['accepted']]);
    $cookie = cookie(
        RequireTermsAcceptance::COOKIE_NAME,
        RequireTermsAcceptance::tokenFor(),
        60 * 24 * 365,
        '/',
        null,
        config('session.secure') ?? $request->isSecure(),
        true,
        false,
        'lax',
    );
    $destination = $request->session()->pull('terms.intended', route('home'));
    if (! is_string($destination) || ! str_starts_with($destination, '/') || str_starts_with($destination, '//')) {
        $destination = route('home');
    }

    return redirect()->to($destination)->withCookie($cookie);
})->name('terms.accept');

Route::view('/about', 'pages.about')->name('about');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/how-it-works', 'pages.how-it-works')->name('how-it-works');

Route::get('/', HomeController::class)->middleware('pwa.public')->name('home');
Route::get('/trips', [TripController::class, 'index'])->middleware('pwa.public')->name('trips.index');
Route::get('/trips/{trip:slug}', [TripController::class, 'show'])->name('trips.show');
Route::get('/vehicles', [VehicleController::class, 'index'])->middleware('pwa.public')->name('vehicles.index');
Route::get('/vehicles/{vehicle:slug}', [VehicleController::class, 'show'])->name('vehicles.show');
Route::get('/compare', [TravelerController::class, 'compare'])->name('compare');
Route::get('/media/blog/{filename}', [BlogController::class, 'image'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('blog.image');
Route::get('/journal', [BlogController::class, 'index'])->middleware('pwa.public')->name('blog.index');
Route::get('/journal/{blogPost:slug}', [BlogController::class, 'show'])->middleware('pwa.public')->name('blog.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::get('/register', [AuthController::class, 'showRegistration'])->middleware(RequireTermsAcceptance::class)->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware([RequireTermsAcceptance::class, 'throttle:auth-register']);
    Route::post('/auth/google', [GoogleAuthController::class, 'authenticate'])->middleware([RequireTermsAcceptance::class, 'throttle:auth-google'])->name('google.authenticate');
    Route::get('/auth/google/complete', [GoogleAuthController::class, 'showCompletion'])->middleware(RequireTermsAcceptance::class)->name('google.complete');
    Route::post('/auth/google/complete', [GoogleAuthController::class, 'complete'])->middleware([RequireTermsAcceptance::class, 'throttle:auth-register']);
    Route::get('/forgot-password', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendResetLink'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:password-reset')->name('password.update');
});

Route::get('/verify/{user}', [VerificationController::class, 'show'])->name('verification.notice');
Route::post('/verify', [VerificationController::class, 'verify'])->middleware('throttle:auth-otp')->name('verification.verify');
Route::post('/verify/resend', [VerificationController::class, 'resend'])->middleware('throttle:auth-otp')->name('verification.resend');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function (): void {
    Route::get('/payments/{payment}/receipt', [PaymentReceiptController::class, 'show'])->name('payments.receipt');
    Route::get('/payments/{payment}/receipt/download', [PaymentReceiptController::class, 'download'])->name('payments.receipt.download');
    Route::get('/account/password', [PasswordController::class, 'changeForm'])->name('password.change');
    Route::put('/account/password', [PasswordController::class, 'change'])->name('password.change.update');
});

Route::middleware(['auth', 'account.access:TRAVELER'])->group(function (): void {
    Route::get('/pwa/csrf', fn (Request $request) => response()->json([
        'token' => csrf_token(),
        'user_id' => $request->user()->id,
    ])->header('Cache-Control', 'private, no-store, max-age=0')->header('Pragma', 'no-cache'))->name('pwa.csrf');
    Route::get('/dashboard', [TravelerController::class, 'dashboard'])->name('dashboard');
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::post('/support/conversation', [SupportController::class, 'start'])->middleware('throttle:support-chat')->name('support.conversations.store');
    Route::post('/support/conversation/{supportConversation}/messages', [SupportController::class, 'send'])->middleware('throttle:support-chat')->name('support.messages.store');
    Route::get('/support/conversation/{supportConversation}/messages', [SupportController::class, 'messages'])->name('support.messages.index');
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/bookings/trips', [BookingController::class, 'storeTrip'])->name('bookings.trips.store');
    Route::post('/bookings/vehicles', [BookingController::class, 'storeVehicle'])->name('bookings.vehicles.store');
    Route::get('/traveler/bookings', [TravelerController::class, 'bookings'])->name('traveler.bookings');
    Route::patch('/traveler/bookings/{booking}/cancel', [TravelerController::class, 'cancelBooking'])->name('traveler.bookings.cancel');
    Route::get('/traveler/profile', [TravelerController::class, 'profile'])->name('traveler.profile');
    Route::put('/traveler/profile', [TravelerController::class, 'updateProfile'])->name('traveler.profile.update');
    Route::get('/traveler/favorites', [TravelerController::class, 'favorites'])->name('traveler.favorites');
    Route::post('/traveler/favorites/{trip:slug}/save', [TravelerController::class, 'saveFavorite'])->name('traveler.favorites.save');
    Route::post('/traveler/favorites/{trip}', [TravelerController::class, 'toggleFavorite'])->name('traveler.favorites.toggle');
    Route::get('/traveler/notifications', [TravelerController::class, 'notifications'])->name('traveler.notifications');
    Route::post('/traveler/notifications/read', [TravelerController::class, 'markNotificationsRead'])->name('traveler.notifications.read');
    Route::get('/traveler/bookings/{booking}/review', [TravelerController::class, 'review'])->name('traveler.reviews.create');
    Route::post('/traveler/bookings/{booking}/review', [TravelerController::class, 'storeReview'])->name('traveler.reviews.store');
    Route::get('/traveler/bookings/{booking}/payment', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/traveler/bookings/{booking}/payment/mpesa', [PaymentController::class, 'startMpesa'])->middleware('throttle:payments')->name('payments.mpesa');
    Route::post('/traveler/bookings/{booking}/payment/card', [PaymentController::class, 'startCard'])->middleware('throttle:payments')->name('payments.card');
    Route::get('/traveler/payments/pesapal/return/{payment}', [PaymentController::class, 'pesapalReturn'])->name('payments.pesapal.return');
    Route::get('/traveler/{section}', [TravelerController::class, 'section'])->whereIn('section', ['upcoming', 'past', 'payments', 'receipts', 'messages', 'reviews', 'settings'])->name('traveler.section');
});

Route::prefix('operator')->name('operator.')->middleware(['auth', 'account.access:OPERATOR'])->group(function (): void {
    Route::get('/', [OperatorController::class, 'dashboard'])->name('dashboard');
    Route::get('/trips', [OperatorController::class, 'trips'])->name('trips.index');
    Route::get('/trips/create', [OperatorController::class, 'createTrip'])->name('trips.create');
    Route::post('/trips', [OperatorController::class, 'storeTrip'])->name('trips.store');
    Route::get('/trips/{trip}/edit', [OperatorController::class, 'editTrip'])->name('trips.edit');
    Route::put('/trips/{trip}', [OperatorController::class, 'updateTrip'])->name('trips.update');
    Route::delete('/trips/{trip}', [OperatorController::class, 'deleteTrip'])->name('trips.delete');
    Route::patch('/trips/{trip}/publish', [OperatorController::class, 'toggleTrip'])->name('trips.toggle');
    Route::put('/trips/{trip}/availability', [OperatorController::class, 'updateAvailability'])->name('trips.availability');
    Route::get('/bookings', [OperatorController::class, 'bookings'])->name('bookings.index');
    Route::patch('/bookings/{booking}', [OperatorController::class, 'updateBooking'])->name('bookings.update');
    Route::get('/profile', [OperatorController::class, 'profile'])->name('profile');
    Route::put('/profile', [OperatorController::class, 'updateProfile'])->name('profile.update');
    Route::post('/verification', [OperatorController::class, 'requestVerification'])->name('verification.store');
    Route::get('/{section}', [OperatorController::class, 'section'])->whereIn('section', ['customers', 'vehicles', 'messages', 'reviews', 'earnings', 'payments', 'analytics', 'settings', 'verification'])->name('section');
});

Route::prefix('vehicle-owner')->name('vehicle-owner.')->middleware(['auth', 'account.access:VEHICLE_OWNER'])->group(function (): void {
    Route::get('/', [VehicleOwnerController::class, 'dashboard'])->name('dashboard');
    Route::get('/vehicles', [VehicleOwnerController::class, 'vehicles'])->name('vehicles.index');
    Route::get('/vehicles/create', [VehicleOwnerController::class, 'createVehicle'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleOwnerController::class, 'storeVehicle'])->name('vehicles.store');
    Route::get('/vehicles/{vehicle}/edit', [VehicleOwnerController::class, 'editVehicle'])->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [VehicleOwnerController::class, 'updateVehicle'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleOwnerController::class, 'deleteVehicle'])->name('vehicles.delete');
    Route::patch('/vehicles/{vehicle}/publish', [VehicleOwnerController::class, 'toggleVehicle'])->name('vehicles.toggle');
    Route::get('/bookings', [VehicleOwnerController::class, 'bookings'])->name('bookings.index');
    Route::patch('/bookings/{booking}', [VehicleOwnerController::class, 'updateBooking'])->name('bookings.update');
    Route::get('/profile', [VehicleOwnerController::class, 'profile'])->name('profile');
    Route::put('/profile', [VehicleOwnerController::class, 'updateProfile'])->name('profile.update');
    Route::post('/verification/identity', [VehicleOwnerController::class, 'requestIdentityVerification'])->name('verification.identity.store');
    Route::post('/verification/vehicle', [VehicleOwnerController::class, 'requestVehicleVerification'])->name('verification.vehicle.store');
    Route::get('/{section}', [VehicleOwnerController::class, 'section'])->whereIn('section', ['earnings', 'ratings', 'messages', 'verification', 'settings'])->name('section');
});

// Shared across every account type, so no role prefix or redirect dance.
Route::middleware(['auth', 'account.access'])->group(function (): void {
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', [AdminModerationController::class, 'dashboard'])->name('dashboard');
    Route::patch('/trips/{trip}', [AdminModerationController::class, 'updateTrip'])->name('trips.update');
    Route::get('/trips', [AdminTripController::class, 'index'])->name('trips.index');
    Route::get('/trips/create', [AdminTripController::class, 'create'])->name('trips.create');
    Route::post('/trips', [AdminTripController::class, 'store'])->name('trips.store');
    Route::get('/trips/{trip}/edit', [AdminTripController::class, 'edit'])->name('trips.edit');
    Route::put('/trips/{trip}', [AdminTripController::class, 'update'])->name('trips.save');
    Route::delete('/trips/{trip}', [AdminTripController::class, 'archive'])->name('trips.archive');
    Route::get('/vehicles', [AdminVehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/create', [AdminVehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [AdminVehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicles/{vehicle}/edit', [AdminVehicleController::class, 'edit'])->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [AdminVehicleController::class, 'update'])->name('vehicles.save');
    Route::delete('/vehicles/{vehicle}', [AdminVehicleController::class, 'archive'])->name('vehicles.archive');
    Route::get('/catalog', [AdminCatalogController::class, 'index'])->name('catalog.index');
    Route::post('/catalog/destinations', [AdminCatalogController::class, 'storeDestination'])->name('catalog.destinations.store');
    Route::put('/catalog/destinations/{destination}', [AdminCatalogController::class, 'updateDestination'])->name('catalog.destinations.update');
    Route::delete('/catalog/destinations/{destination}', [AdminCatalogController::class, 'deleteDestination'])->name('catalog.destinations.delete');
    Route::post('/catalog/categories', [AdminCatalogController::class, 'storeCategory'])->name('catalog.categories.store');
    Route::put('/catalog/categories/{category}', [AdminCatalogController::class, 'updateCategory'])->name('catalog.categories.update');
    Route::delete('/catalog/categories/{category}', [AdminCatalogController::class, 'deleteCategory'])->name('catalog.categories.delete');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
    Route::patch('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/cash-payments', [AdminPaymentController::class, 'recordCash'])->name('bookings.cash-payments.store');
    Route::post('/bookings/{booking}/bank-transfers', [AdminPaymentController::class, 'recordBankTransfer'])->name('bookings.bank-transfers.store');
    Route::patch('/payments/{payment}/verify-bank-transfer', [AdminPaymentController::class, 'verifyBankTransfer'])->name('payments.verify-bank-transfer');
    Route::post('/payments/{payment}/verify-pesapal', [AdminPaymentController::class, 'verifyPesapal'])->name('payments.verify-pesapal');
    Route::post('/payments/{payment}/refunds', [AdminPaymentController::class, 'requestRefund'])->name('payments.refunds.store');
    Route::patch('/refunds/{refund}/complete', [AdminPaymentController::class, 'completeRefund'])->name('refunds.complete');
    Route::get('/payments', [AdminFinanceController::class, 'payments'])->name('payments.index');
    Route::get('/refunds', [AdminFinanceController::class, 'refunds'])->name('refunds.index');
    Route::get('/commissions', [AdminFinanceController::class, 'commissions'])->name('commissions.index');
    Route::get('/payouts', [AdminFinanceController::class, 'payouts'])->name('payouts.index');
    Route::get('/events', [AdminContentController::class, 'events'])->name('events.index');
    Route::get('/events/create', [AdminContentController::class, 'createEvent'])->name('events.create');
    Route::post('/events', [AdminContentController::class, 'storeEvent'])->name('events.store');
    Route::get('/events/{event}/edit', [AdminContentController::class, 'editEvent'])->name('events.edit');
    Route::put('/events/{event}', [AdminContentController::class, 'updateEvent'])->name('events.update');
    Route::patch('/events/{event}/publish', [AdminContentController::class, 'toggleEvent'])->name('events.publish');
    Route::get('/blog', [AdminContentController::class, 'blog'])->name('blog.index');
    Route::get('/blog/create', [AdminContentController::class, 'createPost'])->name('blog.create');
    Route::post('/blog', [AdminContentController::class, 'storePost'])->name('blog.store');
    Route::get('/blog/{blogPost}/edit', [AdminContentController::class, 'editPost'])->name('blog.edit');
    Route::put('/blog/{blogPost}', [AdminContentController::class, 'updatePost'])->name('blog.update');
    Route::patch('/blog/{blogPost}/publish', [AdminContentController::class, 'togglePost'])->name('blog.publish');
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingsController::class, 'store'])->name('settings.store');
    Route::put('/settings/payment-gateways', [AdminSettingsController::class, 'updatePaymentGateways'])->name('settings.payment-gateways.update');
    Route::put('/settings/{setting}', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::patch('/reviews/{review}/visibility', [AdminReviewController::class, 'updateVisibility'])->name('reviews.visibility');
    Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [AdminMessageController::class, 'show'])->name('messages.show');
    Route::get('/support', [AdminSupportController::class, 'index'])->name('support.index');
    Route::get('/support/{supportConversation}', [AdminSupportController::class, 'show'])->name('support.show');
    Route::get('/support/{supportConversation}/messages', [AdminSupportController::class, 'messages'])->name('support.messages.index');
    Route::post('/support/{supportConversation}/messages', [AdminSupportController::class, 'send'])->middleware('throttle:support-chat')->name('support.messages.store');
    Route::patch('/support/{supportConversation}/status', [AdminSupportController::class, 'updateStatus'])->name('support.status');
    Route::patch('/support/{supportConversation}/assignment', [AdminSupportController::class, 'assign'])->name('support.assignment');
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');
    Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals.index');
    Route::put('/referrals/settings', [AdminReferralController::class, 'updateSettings'])->name('referrals.settings');
    Route::post('/referrals/{referral}/reward', [AdminReferralController::class, 'reward'])->name('referrals.reward');
    Route::get('/verification', [AdminVerificationController::class, 'index'])->name('verification.index');
    Route::patch('/verification/{verificationRequest}', [AdminVerificationController::class, 'update'])->name('verification.update');
    Route::get('/verification/{verificationRequest}/documents/{document}', [AdminVerificationController::class, 'document'])
        ->where('document', '[A-Za-z0-9_-]+')
        ->name('verification.document');
    Route::get('/verification/{verificationRequest}/documents/{document}/download', [AdminVerificationController::class, 'downloadDocument'])
        ->where('document', '[A-Za-z0-9_-]+')
        ->name('verification.document.download');
    Route::patch('/vehicles/{vehicle}', [AdminModerationController::class, 'updateVehicle'])->name('vehicles.update');
});

Route::post('/api/payments/mpesa/stk', [MpesaPaymentController::class, 'stk'])
    ->middleware(['auth', 'account.access:TRAVELER', 'throttle:payments'])
    ->name('payments.mpesa.stk');
