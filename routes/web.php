<?php

use App\Http\Controllers\RequestFlowController;
use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::redirect('/', '/tasks');

Route::get('/fluxo', [RequestFlowController::class, 'index'])->name('flow.index');
// As leituras do painel usam o cookie descriptografado sem envelhecer os dados flash.
Route::withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])->group(function (): void {
    Route::get('/fluxo/requisicoes', [RequestFlowController::class, 'events'])->name('flow.events');
    Route::get('/fluxo/arquivos/{file}', [RequestFlowController::class, 'source'])->where('file', '[a-z-]+')->name('flow.source');
});

Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
