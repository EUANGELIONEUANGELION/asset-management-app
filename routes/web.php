<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AssetAssignmentController;
use Illuminate\Support\Facades\Route;

// Halaman utama otomatis mengarah ke login
Route::get('/', function () {
    return redirect('/login');
});

// Jalur khusus Pengunjung (Hanya bisa diakses jika belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Jalur terproteksi Aplikasi (Hanya bisa diakses jika sudah login)
Route::middleware('auth')->group(function () {
    
    // Halaman Dashboard Utama
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Proses Keluar Aplikasi (Logout)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Direktori Halaman Master Aset
    Route::get('/master-aset', [AssetAssignmentController::class, 'index'])->name('master_aset.index');

    // Modul Alur Kerja Tambah Aset Masuk (Sesuai Diagram Alur)
    Route::post('/aset/assign-task', [AssetAssignmentController::class, 'storeTask'])->name('aset.storeTask');
    Route::post('/aset/submit-data/{assignment_id}', [AssetAssignmentController::class, 'submitAssetData'])->name('aset.submitData');
    Route::post('/aset/verify/{assignment_id}', [AssetAssignmentController::class, 'verifyApproval'])->name('aset.verify');
    
});
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Pastikan baris ini ada untuk melayani halaman Master Aset
    Route::get('/master-aset', [AssetAssignmentController::class, 'index'])->name('master_aset.index');

    Route::post('/aset/confirm/{id}', [AssetAssignmentController::class, 'confirmTask']);
    
    // Pastikan namanya mengarah ke verifyApproval, sesuai dengan aksi form di view Blade
Route::post('/aset/verify/{id}', [AssetAssignmentController::class, 'verifyApproval'])->name('aset.verify');
    // ... rute post untuk simpan data dan verify ...

Route::get('/master-aset', [AssetAssignmentController::class, 'index'])->name('master_aset.index');
Route::post('/aset/store',           [AssetAssignmentController::class, 'storeTask'])->name('aset.storeTask');
Route::put('/aset/update/{id}',      [AssetAssignmentController::class, 'updateTask'])->name('aset.updateTask');
Route::delete('/aset/delete/{id}',   [AssetAssignmentController::class, 'destroyTask'])->name('aset.destroyTask');
Route::post('/aset/confirm/{id}',    [AssetAssignmentController::class, 'confirmTask'])->name('aset.confirmTask');
Route::post('/aset/submit-data/{id}',[AssetAssignmentController::class, 'submitAssetData'])->name('aset.submitAssetData');
Route::post('/aset/verify/{id}',     [AssetAssignmentController::class, 'verifyApproval'])->name('aset.verifyApproval');

});


