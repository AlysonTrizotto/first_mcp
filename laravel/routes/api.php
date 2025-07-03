<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\MCPController;
use App\Http\Controllers\AuthController;

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

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:web')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:web')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| MCP Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('mcp')->group(function () {
    Route::post('/chat', [MCPController::class, 'chat']);
    Route::get('/status', function () {
        return response()->json([
            'status' => 'active',
            'model' => config('ollama.model'),
            'version' => '1.0.0',
            'timestamp' => now()
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Company User Management Routes (Admin only)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('company')->group(function () {
    Route::get('/users', [AuthController::class, 'getCompanyUsers']);
    Route::post('/users', [AuthController::class, 'createCompanyUser']);
});

/*
|--------------------------------------------------------------------------
| Ollama Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health/ollama', function () {
    try {
        $response = Http::timeout(5)->get(config('ollama.url') . '/api/tags');
        
        return response()->json([
            'ollama_status' => $response->successful() ? 'online' : 'offline',
            'url' => config('ollama.url'),
            'models_available' => $response->successful() ? $response->json()['models'] ?? [] : [],
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'ollama_status' => 'offline',
            'error' => $e->getMessage(),
            'timestamp' => now()
        ], 503);
    }
});
