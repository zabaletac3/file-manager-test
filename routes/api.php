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


Route::get('home/get-all', [FileManagerController::class, 'getAll']);
Route::get('home/get-by-id/{id}', [FileManagerController::class, 'getById']);
Route::get('home/get-by-folder/{id}', [FileManagerController::class, 'getByFolderId']);
Route::post('home/upload/{folderId}', [FileManagerController::class, 'upload']);
Route::get('home/download/{id}', [FileManagerController::class, 'download']);
Route::delete('home/delete/{id}', [FileManagerController::class, 'delete']);
Route::get('home/search', [FileManagerController::class, 'search']);


Route::get('folder/all', [FolderManagerController::class, 'getfolder']);
Route::get('folder/get-one/{id}', [FolderManagerController::class, 'getOne']);
Route::post('folder/create', [FolderManagerController::class, 'create']);
Route::post('folder/create-sub-folder/{id}', [FolderManagerController::class, 'createFolderInFolder']);
Route::put('folder/update/{id}', [FolderManagerController::class, 'edit']);
Route::delete('folder/delete/{id}', [FolderManagerController::class, 'delete']);
