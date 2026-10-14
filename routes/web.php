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
use App\Http\Controllers\Technician\TechnicianJobController;
use App\Http\Controllers\Admin\JobTrackingController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\TimesheetApprovalController;
use App\Http\Controllers\Admin\MonthlyMaintenanceController;
use App\Http\Controllers\Admin\AdminJobTypeController;

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'not.technician'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});

Route::get('/media/{path}', [FileController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'not.technician', 'no.cache'])->group(function () {

    // system admins
    Route::resource('users', UserController::class);

    // company team hierarchy (engineer / supervisor / technician)
    Route::resource('team', TeamController::class);

    // AMC contracts
    Route::resource('contracts', ContractController::class);

    Route::get('contracts/{contract}/renew', [ContractController::class, 'renewForm'])->name('contracts.renew.form');
    Route::post('contracts/{contract}/renew', [ContractController::class, 'renew'])->name('contracts.renew.store');
    Route::post('contracts/{contract}/import-units', [ContractController::class, 'importElevatorUnits'])->name('contracts.units.import');
    Route::get('contracts/master-list/template', [ContractController::class, 'downloadMasterListTemplate'])
        ->name('contracts.master-list.template');

    Route::post('contracts/master-list/import', [ContractController::class, 'importMasterList'])
        ->name('contracts.master-list.import');

    Route::resource('sites', SiteController::class);

    Route::resource('jobs', JobController::class);

    //customer
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::post('routes/quick-create', [RouteController::class, 'quickStore'])->name('routes.quick-store');

    Route::get('monthly-maintenance', [MonthlyMaintenanceController::class, 'index'])->name('monthly-maintenance.index');

    //profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('scheduling')->name('scheduling.')->group(function () {
        Route::get('/', [ContractSchedulingController::class, 'index'])->name('index');
        Route::get('/{contract}/create', [ContractSchedulingController::class, 'create'])->name('create');
        Route::post('/{contract}', [ContractSchedulingController::class, 'store'])->name('store');
        Route::get('/{contract}', [ContractSchedulingController::class, 'show'])->name('show');
    });

    Route::prefix('tracking')->name('tracking.')->group(function () {
        Route::get('/', [JobTrackingController::class, 'index'])->name('index');
        Route::get('/export', [JobTrackingController::class, 'export'])->name('export');
        Route::get('/{job}', [JobTrackingController::class, 'show'])->name('show');
    });

    Route::get('casual-job/create', [AdminJobTypeController::class, 'casualJobCreate'])->name('job-types.casual-job.create');
    Route::post('casual-job', [AdminJobTypeController::class, 'casualJobStore'])->name('job-types.casual-job.store');
    Route::get('casual-job/{contract}/units', [AdminJobTypeController::class, 'unitsForContract'])->name('job-types.casual-job.units');
    Route::get('casual-job/{contract}/technicians', [AdminJobTypeController::class, 'techniciansForContract'])->name('job-types.casual-job.technicians');

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
    });

    Route::prefix('timesheet-approval')->name('timesheet-approval.')->group(function () {
        Route::get('/', [TimesheetApprovalController::class, 'index'])->name('index');
        Route::post('/{attendance}/approve', [TimesheetApprovalController::class, 'approve'])->name('approve');
        Route::post('/{attendance}/reject', [TimesheetApprovalController::class, 'reject'])->name('reject');
    });

    Route::get('breakdown', [AdminJobTypeController::class, 'breakdown'])->name('job-types.breakdown');
    Route::get('repair', [AdminJobTypeController::class, 'repair'])->name('job-types.repair');
    Route::get('casual-job', [AdminJobTypeController::class, 'casualJob'])->name('job-types.casual-job');
    Route::get('job-types/{job}', [AdminJobTypeController::class, 'show'])->name('job-types.show');
});

Route::prefix('technician')->name('technician.')->middleware(['auth', 'only.technician', 'no.cache'])->group(function () {
    Route::get('/jobs', [TechnicianJobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{job}', [TechnicianJobController::class, 'show'])->name('jobs.show');
    Route::post('/jobs/{job}/check-in', [TechnicianJobController::class, 'checkIn'])->name('jobs.check-in');
    Route::post('/jobs/{job}/complete', [TechnicianJobController::class, 'complete'])->name('jobs.complete');
    Route::get('/timesheet', [TechnicianJobController::class, 'timesheet'])->name('timesheet');
    Route::get('/jobs-list', [TechnicianJobController::class, 'list'])->name('jobs.list');
    Route::get('/find-unit', [TechnicianJobController::class, 'findUnit'])->name('jobs.find-unit');
    Route::get('/search-unit', [TechnicianJobController::class, 'searchUnit'])->name('jobs.search-unit');

    Route::get('/activities/new', [TechnicianJobController::class, 'selectActivity'])->name('activities.select');
    Route::get('/activities/new/{type}', [TechnicianJobController::class, 'activitySearchForm'])->name('activities.search-form');
    Route::get('/activities/new/{type}/search-unit', [TechnicianJobController::class, 'activitySearchUnit'])->name('activities.search-unit');
    Route::post('/activities/new/{type}', [TechnicianJobController::class, 'activityCreate'])->name('activities.create');
    Route::get('/activities/{job}', [TechnicianJobController::class, 'activityShow'])->name('activities.show');
    Route::post('/activities/{job}/start', [TechnicianJobController::class, 'activityStart'])->name('activities.start');
    Route::post('/activities/{job}/save-draft', [TechnicianJobController::class, 'activitySaveDraft'])->name('activities.save-draft');
    Route::post('/activities/{job}/complete', [TechnicianJobController::class, 'activityComplete'])->name('activities.complete');
    Route::get('/jobs-browse', [TechnicianJobController::class, 'jobsPage'])->name('jobs.browse');
    Route::get('/schedule', [TechnicianJobController::class, 'schedulePage'])->name('schedule');
    Route::get('/profile', [TechnicianJobController::class, 'profilePage'])->name('profile');
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
