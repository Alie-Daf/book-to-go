<?php

use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminDisputeController;
use App\Http\Controllers\AdminListingController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminVerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HostAvailabilityController;
use App\Http\Controllers\HostBookingController;
use App\Http\Controllers\HostDashboardController;
use App\Http\Controllers\HostProfileController;
use App\Http\Controllers\HostReviewController;
use App\Http\Controllers\HostUnitController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminFeaturedController;
use App\Http\Controllers\AdminFeeController;
use App\Http\Controllers\AdminInvoiceController;
use App\Http\Controllers\HostBillingController;
use App\Http\Controllers\HostFeaturedController;
use App\Http\Controllers\HostImagePackController;
use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrivacyController;

// "/" sends everyone to the right page for their role
Route::get('/', function () {
    return match (auth()->user()?->role) {
        'Admin' => redirect()->route('admin.dashboard'),
        'Host' => redirect()->route('host.dashboard'),
        'Guest' => redirect()->route('home'),
        default => redirect()->route('login'),
    };
});

// ---------- Login / Register ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ---------- Every role: messages and private files ----------
Route::middleware('auth')->group(function () {
    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::get('/messages/list', [MessageController::class, 'list'])->name('messages.list');
    Route::get('/messages/{user}', [MessageController::class, 'thread'])->name('messages.thread');
    Route::post('/messages', [MessageController::class, 'send'])->name('messages.send');

    Route::get('/files/document/{document}', [FileController::class, 'document'])->name('files.document');
    Route::get('/files/proof/{booking}', [FileController::class, 'proof'])->name('files.proof');
    Route::get('/files/invoice/{invoice}', [FileController::class, 'invoice'])->name('files.invoice');

    Route::get('/files/unit/{unit}/{type}', [FileController::class, 'unitDocument'])->name('files.unit');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readall');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');

    Route::get('/privacy/export', [PrivacyController::class, 'export'])->name('privacy.export');
    Route::post('/privacy/delete', [PrivacyController::class, 'destroy'])->name('privacy.delete');
});

// ---------- Guest ----------
Route::middleware(['auth', 'role:Guest'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/units', [UnitController::class, 'index'])->name('units.index');
    Route::get('/units/{unit}', [UnitController::class, 'show'])->name('units.show');
    Route::get('/units/{unit}/availability', [UnitController::class, 'availability'])->name('units.availability');
    Route::post('/units/{unit}/quote', [UnitController::class, 'quote'])->name('units.quote');
    Route::post('/units/{unit}/review', [UnitController::class, 'review'])->name('units.review');
    Route::post('/units/{unit}/report', [UnitController::class, 'report'])->name('units.report');

    // Booking steps: 1 Reservation, 2 Guests, 3 Done
    Route::get('/units/{unit}/reserve', [ReservationController::class, 'details'])->name('reservation.details');
    Route::post('/units/{unit}/reserve', [ReservationController::class, 'saveDetails'])->name('reservation.save');
    Route::get('/units/{unit}/reserve/guests', [ReservationController::class, 'guests'])->name('reservation.guests');
    Route::post('/units/{unit}/reserve/guests', [ReservationController::class, 'store'])->name('reservation.store');

    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/done', [BookingController::class, 'done'])->name('bookings.done');
    Route::post('/bookings/{booking}/payment-proof', [BookingController::class, 'uploadProof'])->name('bookings.proof');

    Route::get('/favorites', [BookmarkController::class, 'index'])->name('favorites');
    Route::post('/favorites/{unit}', [BookmarkController::class, 'toggle'])->name('favorites.toggle');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/id', [ProfileController::class, 'uploadId'])->name('profile.id');

    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
});

// ---------- Host ----------
Route::middleware(['auth', 'role:Host'])->prefix('host')->name('host.')->group(function () {
  
// open to every host, even before verification
    Route::get('/dashboard', [HostDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [HostProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [HostProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/document', [HostProfileController::class, 'uploadDocument'])->name('profile.document');

    // only for verified hosts
    Route::middleware('host.verified')->group(function () {
        Route::get('/units', [HostUnitController::class, 'index'])->name('units.index');
        Route::get('/units/create', [HostUnitController::class, 'create'])->name('units.create');
        Route::post('/units', [HostUnitController::class, 'store'])->name('units.store');
        Route::get('/units/{unit}/edit', [HostUnitController::class, 'edit'])->name('units.edit');
        Route::post('/units/{unit}', [HostUnitController::class, 'update'])->name('units.update');
        Route::post('/units/{unit}/toggle', [HostUnitController::class, 'toggle'])->name('units.toggle');
        Route::post('/photos/{image}/delete', [HostUnitController::class, 'deletePhoto'])->name('photos.delete');

        Route::get('/guests', [HostBookingController::class, 'index'])->name('guests');
        Route::get('/bookings/{booking}', [HostBookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/confirm-payment', [HostBookingController::class, 'confirmPayment'])->name('bookings.confirm');
        Route::post('/bookings/{booking}/passcode', [HostBookingController::class, 'generatePasscode'])->name('bookings.passcode');
        Route::post('/bookings/{booking}/cancel', [HostBookingController::class, 'cancel'])->name('bookings.cancel');

        Route::get('/availability', [HostAvailabilityController::class, 'index'])->name('availability');
        Route::post('/availability/{unit}/block', [HostAvailabilityController::class, 'block'])->name('availability.block');
        Route::post('/availability/{availability}/unblock', [HostAvailabilityController::class, 'unblock'])->name('availability.unblock');

        Route::get('/reviews', [HostReviewController::class, 'index'])->name('reviews');
        Route::post('/reviews/{review}/reply', [HostReviewController::class, 'reply'])->name('reviews.reply');
        Route::post('/reviews/{review}/report', [HostReviewController::class, 'report'])->name('reviews.report');

        Route::get('/billing', [HostBillingController::class, 'index'])->name('billing');
        Route::post('/billing/unit-slot', [HostBillingController::class, 'buyUnitSlot'])->name('billing.slot');
        Route::post('/billing/{invoice}/proof', [HostBillingController::class, 'uploadProof'])->name('billing.proof');

        Route::get('/image-packs', [HostImagePackController::class, 'index'])->name('packs');
        Route::post('/image-packs', [HostImagePackController::class, 'buy'])->name('packs.buy');

        Route::get('/featured', [HostFeaturedController::class, 'index'])->name('featured');
        Route::post('/featured', [HostFeaturedController::class, 'store'])->name('featured.store');
    });
});

// ---------- Admin ----------
Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::post('/users/{user}/status/{status}', [AdminUserController::class, 'status'])->name('users.status');

    Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('verifications');
    Route::post('/verifications/{verification}/{decision}', [AdminVerificationController::class, 'decideHost'])->name('verifications.decide');
    Route::post('/documents/{document}/{decision}', [AdminVerificationController::class, 'decideDocument'])->name('documents.decide');

    Route::get('/listings', [AdminListingController::class, 'index'])->name('listings');
    Route::post('/listings/{unit}/{decision}', [AdminListingController::class, 'decide'])->name('listings.decide');

    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings');
    
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');

    Route::get('/disputes', [AdminDisputeController::class, 'index'])->name('disputes');
    Route::post('/disputes/{report}/resolve', [AdminDisputeController::class, 'resolve'])->name('disputes.resolve');
    
    Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices');
    Route::post('/invoices/{invoice}/paid', [AdminInvoiceController::class, 'markPaid'])->name('invoices.paid');
    Route::post('/invoices/{invoice}/reject', [AdminInvoiceController::class, 'rejectProof'])->name('invoices.reject');

    Route::get('/featured', [AdminFeaturedController::class, 'index'])->name('featured');
    Route::post('/featured/{featured}/{decision}', [AdminFeaturedController::class, 'decide'])->name('featured.decide');

    Route::get('/fees', [AdminFeeController::class, 'index'])->name('fees');
    Route::post('/fees', [AdminFeeController::class, 'update'])->name('fees.update');

    Route::post('/bookings/{booking}/remind', [AdminBookingController::class, 'remind'])->name('bookings.remind');
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit');
});