<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DadosArcade;

Route::POST('/dados', [DadosArcade::class, 'store']);

Route::get('/teste', function () {
    return 'API está funcionando';
});
