<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\MyAuthController;
use App\Http\Controllers\API\Employees\DestroyEmployeeController;
use App\Http\Controllers\API\Employees\ListEmployeesController;
use App\Http\Controllers\API\Employees\ShowEmployeeController;
use App\Http\Controllers\API\Employees\UploadEmployeesController;
use App\Http\Controllers\API\HealthController;
use Illuminate\Support\Facades\Route;


Route::post('/auth/login', LoginController::class)->name('auth.login');

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/user', MyAuthController::class);

    Route::get('/teste', HealthController::class)->name('teste');

    Route::get('/employees', ListEmployeesController::class)->name('employees.get');

    Route::get('/employees/{employee}', ShowEmployeeController::class)->name('employees.show');

    Route::delete('/employees/{employee}', [DestroyEmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::post('/employees', UploadEmployeesController::class)->name('employees.upload');
});
