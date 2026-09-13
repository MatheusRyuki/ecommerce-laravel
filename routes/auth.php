<?php

use App\Http\Controllers\Auth\AvisoVerificacaoEmailController;
use App\Http\Controllers\Auth\CadastroUsuarioController;
use App\Http\Controllers\Auth\ConfirmacaoSenhaController;
use App\Http\Controllers\Auth\LinkRedefinicaoSenhaController;
use App\Http\Controllers\Auth\NotificacaoVerificacaoEmailController;
use App\Http\Controllers\Auth\NovaSenhaController;
use App\Http\Controllers\Auth\SenhaController;
use App\Http\Controllers\Auth\SessaoAutenticadaController;
use App\Http\Controllers\Auth\VerificarEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [CadastroUsuarioController::class, 'exibir'])
        ->name('register');

    Route::post('register', [CadastroUsuarioController::class, 'cadastrar']);

    Route::get('login', [SessaoAutenticadaController::class, 'exibir'])
        ->name('login');

    Route::post('login', [SessaoAutenticadaController::class, 'entrar']);

    Route::get('forgot-password', [LinkRedefinicaoSenhaController::class, 'exibir'])
        ->name('password.request');

    Route::post('forgot-password', [LinkRedefinicaoSenhaController::class, 'enviar'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NovaSenhaController::class, 'exibir'])
        ->name('password.reset');

    Route::post('reset-password', [NovaSenhaController::class, 'salvar'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', AvisoVerificacaoEmailController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerificarEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [NotificacaoVerificacaoEmailController::class, 'reenviar'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmacaoSenhaController::class, 'exibir'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmacaoSenhaController::class, 'confirmar']);

    Route::put('password', [SenhaController::class, 'atualizar'])->name('password.update');

    Route::post('logout', [SessaoAutenticadaController::class, 'sair'])
        ->name('logout');
});
