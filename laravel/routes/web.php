<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MCPWebController;

Route::get('/', function () {
    return view('welcome');
});

// MCP Frontend Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/mcp', [MCPWebController::class, 'index'])->name('mcp.index');
    Route::get('/mcp/chat', [MCPWebController::class, 'chat'])->name('mcp.chat');
    Route::get('/mcp/analytics', [MCPWebController::class, 'analytics'])->name('mcp.analytics');
    Route::get('/mcp/settings', [MCPWebController::class, 'settings'])->name('mcp.settings');
    Route::post('/mcp/settings', [MCPWebController::class, 'updateSettings'])->name('mcp.settings.update');
});

// Auth Routes (se não estiver usando Breeze/Jetstream)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    Route::post('/login', [MCPWebController::class, 'authenticate'])->name('login.post');
});

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');
