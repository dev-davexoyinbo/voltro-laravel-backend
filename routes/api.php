<?php

use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\CardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix("auth")->group(function(){
    Route::post("/register", [AuthenticationController::class, "register"]);
    Route::post("/login", [AuthenticationController::class, "login"]);
    Route::get("/me", [AuthenticationController::class, "me"])->middleware("auth:api");
    Route::get("/logout", [AuthenticationController::class, "logout"])->middleware("auth:api");
});

Route::middleware("auth:api")->group(function() {
    Route::get("/cards", [CardController::class, "index"]);
    Route::post("/cards", [CardController::class, "store"]);
    Route::get("/cards/{card}", [CardController::class, "show"]);
    Route::delete("/cards/{card}", [CardController::class, "destroy"]);
    Route::post("/cards/{card}/edit", [CardController::class, "edit"]);
});