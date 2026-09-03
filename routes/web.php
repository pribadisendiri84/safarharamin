<?php

use App\Http\Controllers\Admin\AirlineController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\InquiryPilgrimImportController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\PackageImportController;
use App\Http\Controllers\Admin\DepartureController;
use App\Http\Controllers\Admin\DepartureRecapController;
use App\Http\Controllers\Admin\OperationsDashboardController;
use App\Http\Controllers\Admin\PilgrimController;
use App\Http\Controllers\Admin\PilgrimTransactionController;
use App\Http\Controllers\Admin\RoomGroupingController;
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
            Route::get('packages/import', [PackageImportController::class, 'create'])->name('packages.import');
            Route::post('packages/import', [PackageImportController::class, 'store'])->name('packages.import.store');
            Route::get('packages/import/template', [PackageImportController::class, 'template'])->name('packages.import.template');
            Route::resource('packages', AdminPackageController::class)->except(['show']);
            Route::get('packages/{package}/duplicate', [AdminPackageController::class, 'duplicate'])->name('packages.duplicate');
            Route::post('packages/{package}/restore', [AdminPackageController::class, 'restore'])->withTrashed()->name('packages.restore');
            Route::patch('packages/{package}/featured', [AdminPackageController::class, 'toggleFeatured'])->name('packages.toggle-featured');
            Route::patch('packages/{package}/status', [AdminPackageController::class, 'updateStatus'])->name('packages.update-status');
            Route::post('packages/reorder-home', [AdminPackageController::class, 'reorderHome'])->name('packages.reorder-home');
            Route::resource('gallery', GalleryController::class)->except(['show']);
            Route::patch('gallery/{gallery}/home', [GalleryController::class, 'toggleHome'])->name('gallery.toggle-home');
            Route::post('gallery/reorder', [GalleryController::class, 'reorder'])->name('gallery.reorder');
            Route::post('gallery/{gallery}/restore', [GalleryController::class, 'restore'])->withTrashed()->name('gallery.restore');
            Route::resource('testimonials', TestimonialController::class)->except(['show']);
            Route::post('testimonials/{testimonial}/restore', [TestimonialController::class, 'restore'])->withTrashed()->name('testimonials.restore');
            Route::get('cities', [CityController::class, 'index'])->name('cities.index');
            Route::post('cities', [CityController::class, 'store'])->name('cities.store');
            Route::put('cities/{city}', [CityController::class, 'update'])->name('cities.update');
            Route::delete('cities/{city}', [CityController::class, 'destroy'])->name('cities.destroy');
            Route::post('cities/{city}/restore', [CityController::class, 'restore'])->withTrashed()->name('cities.restore');
            Route::get('hotels', [HotelController::class, 'index'])->name('hotels.index');
            Route::post('hotels', [HotelController::class, 'store'])->name('hotels.store');
            Route::put('hotels/{hotel}', [HotelController::class, 'update'])->name('hotels.update');
            Route::delete('hotels/{hotel}', [HotelController::class, 'destroy'])->name('hotels.destroy');
            Route::post('hotels/{hotel}/restore', [HotelController::class, 'restore'])->withTrashed()->name('hotels.restore');
            Route::get('airlines', [AirlineController::class, 'index'])->name('airlines.index');
            Route::post('airlines', [AirlineController::class, 'store'])->name('airlines.store');
            Route::put('airlines/{airline}', [AirlineController::class, 'update'])->name('airlines.update');
            Route::delete('airlines/{airline}', [AirlineController::class, 'destroy'])->name('airlines.destroy');
            Route::post('airlines/{airline}/restore', [AirlineController::class, 'restore'])->withTrashed()->name('airlines.restore');
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        });

        Route::get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/create', [InquiryController::class, 'create'])->name('inquiries.create');
        Route::post('inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
        Route::get('inquiries/{inquiry}/edit', [InquiryController::class, 'edit'])->name('inquiries.edit');
        Route::put('inquiries/{inquiry}/edit', [InquiryController::class, 'updateLead'])->name('inquiries.edit.update');
        Route::get('inquiries/{inquiry}', [InquiryController::class, 'show'])->withTrashed()->name('inquiries.show');
        Route::put('inquiries/{inquiry}', [InquiryController::class, 'update'])->name('inquiries.update');
        Route::post('inquiries/{inquiry}/notes', [InquiryController::class, 'storeNote'])->name('inquiries.notes.store');
        Route::delete('inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiries.destroy');
        Route::post('inquiries/{inquiry}/restore', [InquiryController::class, 'restore'])->withTrashed()->name('inquiries.restore');
        Route::post('inquiries/{inquiry}/import-jamaah', [InquiryPilgrimImportController::class, 'store'])->name('inquiries.import-pilgrims');

        Route::prefix('operasi')->name('operations.')->group(function () {
            Route::get('/', OperationsDashboardController::class)->name('dashboard');
            Route::resource('keberangkatan', DepartureController::class)
                ->parameters(['keberangkatan' => 'departure'])
                ->names('departures')
                ->except(['show']);
            Route::post('keberangkatan/{departure}/restore', [DepartureController::class, 'restore'])->name('departures.restore');
            Route::resource('jamaah', PilgrimController::class)
                ->parameters(['jamaah' => 'pilgrim'])
                ->names('pilgrims');
            Route::post('jamaah/{pilgrim}/restore', [PilgrimController::class, 'restore'])->name('pilgrims.restore');
            Route::post('jamaah/{pilgrim}/transaksi', [PilgrimTransactionController::class, 'store'])->name('pilgrims.transactions.store');
            Route::get('jamaah/{pilgrim}/transaksi/{transaction}/invoice', [PilgrimTransactionController::class, 'showInvoice'])->name('pilgrims.transactions.invoice.show');
            Route::delete('jamaah/{pilgrim}/transaksi/{transaction}', [PilgrimTransactionController::class, 'destroy'])->name('pilgrims.transactions.destroy');
            Route::get('keberangkatan/{departure}/grouping', [RoomGroupingController::class, 'index'])->name('grouping.index');
            Route::post('keberangkatan/{departure}/grouping/auto', [RoomGroupingController::class, 'autoGroup'])->name('grouping.auto');
            Route::post('keberangkatan/{departure}/grouping/rooms', [RoomGroupingController::class, 'storeRoom'])->name('grouping.rooms.store');
            Route::delete('keberangkatan/{departure}/grouping/rooms/{room}', [RoomGroupingController::class, 'destroyRoom'])->name('grouping.rooms.destroy');
            Route::post('keberangkatan/{departure}/grouping/assign', [RoomGroupingController::class, 'assign'])->name('grouping.assign');
            Route::post('keberangkatan/{departure}/grouping/move', [RoomGroupingController::class, 'move'])->name('grouping.move');
            Route::post('keberangkatan/{departure}/grouping/remove', [RoomGroupingController::class, 'remove'])->name('grouping.remove');
            Route::get('keberangkatan/{departure}/rekap', [DepartureRecapController::class, 'show'])->name('recap.show');
        });

        Route::middleware('can:manage-users')->group(function () {
            Route::get('riwayat', [HistoryController::class, 'index'])->name('history.index');
            Route::get('trafik', [TrafficController::class, 'index'])->name('traffic.index');
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('users/{user}/restore', [UserController::class, 'restore'])->withTrashed()->name('users.restore');
        });
    });
});
