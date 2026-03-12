<?php

use App\Http\Controllers\AppUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\SettingPermissionController;
use App\Http\Controllers\SettingRoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


require __DIR__ . '/auth.php';

Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {
    Route::get('', [RoutingController::class, 'index'])->name('root');
    Route::get('/home', [DashboardController::class, 'analytics'])->name('home');
    Route::get('/dashboards/analytics', [DashboardController::class, 'analytics']);

    Route::resource('users', UserController::class);
    Route::patch('users/{user}/toggle-block', [UserController::class, 'toggleBlock'])->name('users.toggle-block');

    Route::resource('app-users', AppUserController::class)
        ->parameters(['app-users' => 'app_user'])
        ->except(['destroy']);
    Route::patch('app-users/{app_user}/toggle-status', [AppUserController::class, 'toggleStatus'])->name('app-users.toggle-status');
    Route::patch('app-users/{app_user}/posts/{post}/toggle-visibility', [AppUserController::class, 'togglePostVisibility'])->name('app-users.posts.toggle-visibility');

    Route::resource('settings/roles', SettingRoleController::class)->names('settings.roles');

    Route::controller(SettingPermissionController::class)->prefix('settings/permissions')->name('settings.permissions.')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('', 'store')->name('store');
        Route::get('{permissionId}', 'show')->whereNumber('permissionId')->name('show');
        Route::get('{permissionId}/edit', 'edit')->whereNumber('permissionId')->name('edit');
        Route::put('{permissionId}', 'update')->whereNumber('permissionId')->name('update');
        Route::delete('{permissionId}', 'destroy')->whereNumber('permissionId')->name('destroy');
        Route::match(['get', 'post'], '{permissionId}/delete', 'delete')->whereNumber('permissionId')->name('delete');
    });

    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])->name('any');
});
