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
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\ShutdownUnitController;
use App\Http\Controllers\Admin\TimesheetAdjustmentController;
use App\Http\Controllers\Technician\TechnicianLeaveController;

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'not.technician'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});

Route::get('/media/{path}', [FileController::class, 'show'])
    ->where('path', '.*')
    ->middleware('auth')
    ->name('media.show');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'not.technician', 'no.cache'])->group(function () {

    // system admins
    Route::resource('users', UserController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->middleware('role:Super Admin,Admin');

    // company team hierarchy (engineer / supervisor / technician)
    Route::resource('team', TeamController::class);

    // AMC contracts
    Route::post('contracts/bulk-delete', [ContractController::class, 'bulkDestroy'])->name('contracts.bulk-destroy');
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
    Route::get('routes', [RouteController::class, 'index'])->name('routes.index');
    Route::delete('routes/{route}', [RouteController::class, 'destroy'])->name('routes.destroy');

    Route::get('monthly-maintenance', [MonthlyMaintenanceController::class, 'index'])->name('monthly-maintenance.index');

    //profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('scheduling')->name('scheduling.')->group(function () {
        Route::get('/', [ContractSchedulingController::class, 'index'])->name('index');
        Route::post('/jobs/{job}/reschedule', [ContractSchedulingController::class, 'reschedule'])->name('jobs.reschedule');
        Route::post('/jobs/{job}/undo-reschedule', [ContractSchedulingController::class, 'undoReschedule'])->name('jobs.undo-reschedule');
        Route::post('/{contract}/batches/{batch}/undo', [ContractSchedulingController::class, 'undoBatch'])->name('batches.undo');
        Route::post('/{contract}/units/{unit}/unschedule', [ContractSchedulingController::class, 'unscheduleUnit'])->name('units.unschedule');
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

    // Manual Timesheet Adjustment (engineers for their team; admins for all)
    Route::post('tracking/{job}/adjust', [TimesheetAdjustmentController::class, 'store'])->name('tracking.adjust');
    // Stores: inventory + Material Requests (MRN) — Rev 8
    Route::prefix('stores')->name('stores.')->group(function () {
        Route::get('inventory/search', [\App\Http\Controllers\Admin\InventoryController::class, 'search'])->name('inventory.search');
        Route::get('analytics', [\App\Http\Controllers\Admin\InventoryController::class, 'analytics'])->name('analytics');
        Route::post('inventory/{item}/receive', [\App\Http\Controllers\Admin\InventoryController::class, 'receive'])->name('inventory.receive');
        Route::post('inventory/{item}/adjust', [\App\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::resource('inventory', \App\Http\Controllers\Admin\InventoryController::class)->parameters(['inventory' => 'item']);

        Route::get('requests', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/create', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/units/{contract}', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'units'])->name('requests.units');
        Route::get('requests/{mr}', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{mr}/approve', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'approve'])->name('requests.approve');
        Route::post('requests/{mr}/reject', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'reject'])->name('requests.reject');
        Route::post('requests/{mr}/issue', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'issue'])->name('requests.issue');
        Route::post('requests/{mr}/cancel', [\App\Http\Controllers\Admin\MaterialRequestController::class, 'cancel'])->name('requests.cancel');
    });

    // Idle / travel time between jobs (Rev 8)
    Route::get('idle-time', [\App\Http\Controllers\Admin\IdleTimeController::class, 'index'])->name('idle-time.index');
    // Office "Mark as Completed" (engineer / supervisor of the technician, or an admin)
    Route::post('jobs/{job}/mark-completed', [\App\Http\Controllers\Admin\JobCompletionController::class, 'store'])->name('jobs.mark-completed');

    // Elevators currently shut down (priority list)
    Route::get('shutdown-units', [ShutdownUnitController::class, 'index'])->name('shutdown-units.index');
    Route::post('shutdown-units/{unit}/mark-working', [ShutdownUnitController::class, 'markWorking'])->name('shutdown-units.mark-working');

    // Team jobs: add another technician to a breakdown / repair / casual job
    Route::post('job-types/{job}/team', [AdminJobTypeController::class, 'addTechnician'])->name('job-types.add-technician');

    // Technician leave requests → supervisor approval
    Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('leave-requests/{leave}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leave}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');

    // Dashboard live widgets
    Route::get('dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');
    Route::get('dashboard/breakdowns', [DashboardController::class, 'breakdownsOnDay'])->name('dashboard.breakdowns');

    Route::prefix('holidays')->name('holidays.')->middleware('role:Super Admin,Admin,Manager')->group(function () {
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('timesheet-approval')->name('timesheet-approval.')->group(function () {
        Route::get('/', [TimesheetApprovalController::class, 'index'])->name('index');
        Route::post('/bulk-approve', [TimesheetApprovalController::class, 'bulkApprove'])->name('bulk-approve');
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

    Route::get('/leave', [TechnicianLeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave', [TechnicianLeaveController::class, 'store'])->name('leave.store');
    Route::post('/leave/{leave}/cancel', [TechnicianLeaveController::class, 'cancel'])->name('leave.cancel');
});

Route::group(['prefix' => '/', 'middleware' => ['auth', 'not.technician']], function () {
    Route::get('{first}/{second}',[RoutingController::class, 'secondLevel'])->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])->name('any');
});

Route::get('/login', function () {
    return view('auth.signin');
})->name('login');

Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
