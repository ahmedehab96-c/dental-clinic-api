<?php

use App\Http\Controllers\Api\V1\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Api\V1\Admin\BlogCategoryController as AdminBlogCategoryController;
use App\Http\Controllers\Api\V1\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Api\V1\Admin\ClinicSettingController as AdminClinicSettingController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\Api\V1\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Api\V1\Admin\GalleryCaseController as AdminGalleryCaseController;
use App\Http\Controllers\Api\V1\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\V1\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BlogPostController;
use App\Http\Controllers\Api\V1\ClinicSettingController;
use App\Http\Controllers\Api\V1\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Api\V1\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Api\V1\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\FaqController;
use App\Http\Controllers\Api\V1\GalleryCaseController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ------------------------------------------------------------------
    // Public — read-only content, no account required. Mirrors the
    // current mock data 1:1.
    // ------------------------------------------------------------------
    Route::get('doctors', [DoctorController::class, 'index']);
    Route::get('doctors/{doctor}', [DoctorController::class, 'show']);

    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);

    Route::get('posts', [BlogPostController::class, 'index']);
    Route::get('posts/{post}', [BlogPostController::class, 'show']);

    Route::get('testimonials', [TestimonialController::class, 'index']);
    Route::get('gallery', [GalleryCaseController::class, 'index']);
    Route::get('faqs', [FaqController::class, 'index']);
    Route::get('clinic-settings', [ClinicSettingController::class, 'show']);
    Route::get('availability', [AvailabilityController::class, 'index']);

    // ------------------------------------------------------------------
    // Guest — writable without an account.
    // ------------------------------------------------------------------
    // Booking a visit never requires login; if the request happens to be
    // authenticated (Sanctum), the appointment is linked to that user.
    Route::post('appointments', [AppointmentController::class, 'store']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // ------------------------------------------------------------------
    // Authenticated — any logged-in role (Sanctum), no role restriction.
    // ------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'updateProfile']);

        // Notifications — any role, always scoped to the caller's own account.
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // --------------------------------------------------------------
        // Patient — self-service on the caller's own appointments,
        // scoped by their authenticated identity, never a client-supplied
        // id. AppointmentPolicy double-checks ownership on every one.
        // --------------------------------------------------------------
        Route::middleware('role:patient')->group(function () {
            Route::get('me/appointments', [AppointmentController::class, 'mine']);
            Route::get('me/appointments/{appointment}', [AppointmentController::class, 'showMine']);
            Route::post('me/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
        });

        // --------------------------------------------------------------
        // Doctor — self-service on the caller's own linked doctor
        // profile, services, and appointments only.
        // --------------------------------------------------------------
        Route::prefix('doctor')->middleware('role:doctor')->group(function () {
            Route::get('dashboard', [DoctorDashboardController::class, 'index']);
            Route::get('profile', [DoctorProfileController::class, 'show']);
            Route::patch('profile', [DoctorProfileController::class, 'update']);
            Route::get('services', [DoctorProfileController::class, 'services']);
            Route::get('appointments', [DoctorAppointmentController::class, 'index']);
            Route::get('appointments/{appointment}', [DoctorAppointmentController::class, 'show']);
            Route::patch('appointments/{appointment}/status', [DoctorAppointmentController::class, 'updateStatus']);
        });

        // --------------------------------------------------------------
        // Admin — full management of clinic content, appointments, and
        // user accounts.
        // --------------------------------------------------------------
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('dashboard', [AdminDashboardController::class, 'index']);

            Route::get('doctors', [AdminDoctorController::class, 'index']);
            Route::get('doctors/{doctor}', [AdminDoctorController::class, 'show']);
            Route::post('doctors', [AdminDoctorController::class, 'store']);
            Route::patch('doctors/{doctor}', [AdminDoctorController::class, 'update']);
            Route::delete('doctors/{doctor}', [AdminDoctorController::class, 'destroy']);

            Route::get('services', [AdminServiceController::class, 'index']);
            Route::get('services/{service}', [AdminServiceController::class, 'show']);
            Route::post('services', [AdminServiceController::class, 'store']);
            Route::patch('services/{service}', [AdminServiceController::class, 'update']);
            Route::delete('services/{service}', [AdminServiceController::class, 'destroy']);

            Route::get('blog-categories', [AdminBlogCategoryController::class, 'index']);
            Route::post('blog-categories', [AdminBlogCategoryController::class, 'store']);
            Route::patch('blog-categories/{blog_category}', [AdminBlogCategoryController::class, 'update']);
            Route::delete('blog-categories/{blog_category}', [AdminBlogCategoryController::class, 'destroy']);

            Route::get('posts', [AdminBlogPostController::class, 'index']);
            Route::get('posts/{post}', [AdminBlogPostController::class, 'show']);
            Route::post('posts', [AdminBlogPostController::class, 'store']);
            Route::patch('posts/{post}', [AdminBlogPostController::class, 'update']);
            Route::delete('posts/{post}', [AdminBlogPostController::class, 'destroy']);

            Route::get('testimonials', [AdminTestimonialController::class, 'index']);
            Route::get('testimonials/{testimonial}', [AdminTestimonialController::class, 'show']);
            Route::post('testimonials', [AdminTestimonialController::class, 'store']);
            Route::patch('testimonials/{testimonial}', [AdminTestimonialController::class, 'update']);
            Route::delete('testimonials/{testimonial}', [AdminTestimonialController::class, 'destroy']);

            Route::get('gallery', [AdminGalleryCaseController::class, 'index']);
            Route::get('gallery/{gallery_case}', [AdminGalleryCaseController::class, 'show']);
            Route::post('gallery', [AdminGalleryCaseController::class, 'store']);
            Route::patch('gallery/{gallery_case}', [AdminGalleryCaseController::class, 'update']);
            Route::delete('gallery/{gallery_case}', [AdminGalleryCaseController::class, 'destroy']);

            Route::get('faqs', [AdminFaqController::class, 'index']);
            Route::get('faqs/{faq}', [AdminFaqController::class, 'show']);
            Route::post('faqs', [AdminFaqController::class, 'store']);
            Route::patch('faqs/{faq}', [AdminFaqController::class, 'update']);
            Route::delete('faqs/{faq}', [AdminFaqController::class, 'destroy']);

            Route::patch('clinic-settings', [AdminClinicSettingController::class, 'update']);

            Route::get('appointments', [AdminAppointmentController::class, 'index']);
            Route::get('appointments/{appointment}', [AdminAppointmentController::class, 'show']);
            Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
            Route::delete('appointments/{appointment}', [AdminAppointmentController::class, 'destroy']);

            Route::get('users', [AdminUserController::class, 'index']);
            Route::get('users/{user}', [AdminUserController::class, 'show']);
            Route::post('users', [AdminUserController::class, 'store']);
            Route::patch('users/{user}', [AdminUserController::class, 'update']);
            Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
        });
    });
});
