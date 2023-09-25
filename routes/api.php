<?php

use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\FolderManagerController;
use App\Services\FileManagerService;
use App\Services\FolderManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('home/get-data', [FileManagerController::class, 'getData']);
Route::get('home/get-files/{id}', [FileManagerController::class, 'getFiles']);
 Route::post('home/upload/{folderId}', [FileManagerService::class, 'upload']);
 Route::post('home/upload-multi/{folderId}', [FileManagerService::class, 'uploadMulti']);
 Route::get('home/download/{id}', [FileManagerService::class, 'download']);
 Route::delete('home/delete/{id}', [FileManagerService::class, 'delete']);
 Route::get('home/search', [FileManagerService::class, 'search']);


Route::get('folder/all', [FolderManagerController::class, 'getfolder']);
Route::post('folder/create', [FolderManagerController::class, 'create']);
Route::post('folder/create-sub-folder/{id}', [FolderManagerController::class, 'createFolderInFolder']);
Route::put('folder/update/{id}', [FolderManagerController::class, 'edit']);
Route::delete('folder/delete/{id}', [FolderManagerController::class, 'delete']);
