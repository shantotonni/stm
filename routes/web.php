<?php
use \App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/command', function() {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('optimize');
    return 'DONE';
});


Route::get('/{app?}',[HomeController::class,'index'])->where('app','.*');

