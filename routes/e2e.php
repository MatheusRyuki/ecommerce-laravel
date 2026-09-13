<?php

use App\Http\Controllers\E2e\ArquivoPublicoE2eController;
use App\Http\Controllers\E2e\DiagnosticoE2eController;
use Illuminate\Support\Facades\Route;

Route::get('/armazenamento-e2e/{caminho}', ArquivoPublicoE2eController::class)
    ->where('caminho', '.*')
    ->name('e2e.armazenamento');

Route::get('/_e2e/diagnostico', DiagnosticoE2eController::class)
    ->name('e2e.diagnostico');
