<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

  // laravel 7
  Route::get('/payment', 'PaymentController@index');
  Route::post('/process-payment', 'PaymentController@processPayment');

  // laravel 8
  Route::get('/payment', [PaymentController::class, 'index']);
  Route::post('/process-payment', [PaymentController::class, 'processPayment']);