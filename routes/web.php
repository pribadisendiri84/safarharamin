<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\TrafficController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\WhatsAppRedirectController;
use App\Http\Middleware\AdminAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/go/wa', WhatsAppRedirectController::class)->name('go.whatsapp');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/paket', [PackageController::class, 'index'])->name('packages.index');
Route::get('/paket/{package:slug}', [PackageController::class, 'show'])->name('packages.show');
Route::post('/paket/{package:slug}/tanya', [PackageController::class, 'inquire'])->name('packages.inquire');
Route::get('/daftar', [RegisterController::class, 'create'])->name('register');
Route::post('/daftar', [RegisterController::class, 'store'])->name('register.store');
Route::get('/haji-plus', [PageController::class, 'haji'])->name('haji');
Route::get('/galeri', [PageController::class, 'gallery'])->name('gallery');
Route::get('/testimoni', [PageController::class, 'testimonials'])->name('testimonials');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(AdminAuthenticate::class)->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('can:manage-catalog')->group(function () {
            Route::resource('packages', AdminPackageController::class)->except(['show']);
            Route::post('packages/{package}/restore', [AdminPackageController::class, 'restore'])->withTrashed()->name('packages.restore');
            Route::resource('gallery', GalleryController::class)->except(['show']);
            Route::post('gallery/{gallery}/restore', [GalleryController::class, 'restore'])->withTrashed()->name('gallery.restore');
            Route::resource('testimonials', TestimonialController::class)->except(['show']);
            Route::post('testimonials/{testimonial}/restore', [TestimonialController::class, 'restore'])->withTrashed()->name('testimonials.restore');
            Route::get('cities', [CityController::class, 'index'])->name('cities.index');
            Route::post('cities', [CityController::class, 'store'])->name('cities.store');
            Route::put('cities/{city}', [CityController::class, 'update'])->name('cities.update');
            Route::delete('cities/{city}', [CityController::class, 'destroy'])->name('cities.destroy');
            Route::post('cities/{city}/restore', [CityController::class, 'restore'])->withTrashed()->name('cities.restore');
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        });

        Route::get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/create', [InquiryController::class, 'create'])->name('inquiries.create');
        Route::post('inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
        Route::get('inquiries/{inquiry}', [InquiryController::class, 'show'])->withTrashed()->name('inquiries.show');
        Route::put('inquiries/{inquiry}', [InquiryController::class, 'update'])->name('inquiries.update');
        Route::post('inquiries/{inquiry}/notes', [InquiryController::class, 'storeNote'])->name('inquiries.notes.store');
        Route::delete('inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiries.destroy');
        Route::post('inquiries/{inquiry}/restore', [InquiryController::class, 'restore'])->withTrashed()->name('inquiries.restore');

        Route::middleware('can:manage-users')->group(function () {
            Route::get('riwayat', [HistoryController::class, 'index'])->name('history.index');
            Route::get('trafik', [TrafficController::class, 'index'])->name('traffic.index');
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('users/{user}/restore', [UserController::class, 'restore'])->withTrashed()->name('users.restore');
        });
    });
});
