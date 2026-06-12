<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AssetAssignmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

// Guest only
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Auth only — SATU group saja
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Halaman utama & form terpisah
    Route::get('/master-aset', [AssetAssignmentController::class, 'index'])->name('master_aset.index');
    Route::get('/master-aset/input/{id}', [AssetAssignmentController::class, 'formInput'])->name('master_aset.formInput');
    Route::get('/master-aset/approval/{id}', [AssetAssignmentController::class, 'formApproval'])->name('master_aset.formApproval');

    // Aksi data
    Route::post('/aset/store', [AssetAssignmentController::class, 'storeTask'])->name('aset.storeTask');
    Route::put('/aset/update/{id}', [AssetAssignmentController::class, 'updateTask'])->name('aset.updateTask');
    Route::delete('/aset/delete/{id}', [AssetAssignmentController::class, 'destroyTask'])->name('aset.destroyTask');
    Route::post('/aset/confirm/{id}', [AssetAssignmentController::class, 'confirmTask']);
    Route::post('/aset/submit-data/{id}', [AssetAssignmentController::class, 'submitAssetData']);
    Route::post('/aset/verify/{id}', [AssetAssignmentController::class, 'verifyApproval']);
    Route::post('/aset/verify/{id}', [AssetAssignmentController::class, 'verifyApproval'])->name('aset.verify');
    // AJAX
    Route::get('/api/get-checkpoints/{assignment_id}', [AssetAssignmentController::class, 'getCheckpoints']);

}); // ← satu penutup saja  