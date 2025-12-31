<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;
use Inertia\Inertia;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('imageCompress', [ImageController::class, 'imageCompress']);
Route::get('imageStatusPing', [ImageController::class, 'imageStatusPing']);
Route::get('imageDownload/{id}', [ImageController::class, 'imageDownload']);
