<?php

use App\Http\Controllers\RoutingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\ContractSchedulingController;
use App\Http\Controllers\Admin\DashboardController;

require __DIR__ . '/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});

Route::get('/media/{path}', [FileController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {

    // system admins
    Route::resource('users', UserController::class);

    // company team hierarchy (engineer / supervisor / technician)
    Route::resource('team', TeamController::class);

    // AMC contracts
    Route::resource('contracts', ContractController::class);

    Route::get('contracts/{contract}/renew', [ContractController::class, 'renewForm'])->name('contracts.renew.form');
    Route::post('contracts/{contract}/renew', [ContractController::class, 'renew'])->name('contracts.renew.store');
    Route::post('contracts/{contract}/import-units', [ContractController::class, 'importElevatorUnits'])->name('contracts.units.import');

    Route::resource('sites', SiteController::class);

    Route::resource('jobs', JobController::class);

    //customer
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    //profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

 Route::prefix('scheduling')->name('scheduling.')->group(function () {
    Route::get('/', [ContractSchedulingController::class, 'index'])->name('index');
    Route::get('/{contract}/create', [ContractSchedulingController::class, 'create'])->name('create');
    Route::post('/{contract}', [ContractSchedulingController::class, 'store'])->name('store');
    Route::get('/{contract}', [ContractSchedulingController::class, 'show'])->name('show');
});
});

Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {
    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])->name('any');
});

Route::get('/login', function () {
    return view('auth.signin');
})->name('login');

Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
