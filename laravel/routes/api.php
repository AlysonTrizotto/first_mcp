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

Route::get('/simple-test', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Simple GET test works',
        'timestamp' => now()
    ]);
});

Route::post('/simple-test', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Simple test works',
        'data' => $request->all(),
        'timestamp' => now()
    ]);
});

/*
|--------------------------------------------------------------------------
| MCP Routes
|--------------------------------------------------------------------------
*/

Route::prefix('mcp')->group(function () {
    Route::post('/chat', [MCPController::class, 'chat']);
    Route::post('/test', function (Request $request) {
        return response()->json([
            'message' => 'Test successful',
            'received' => $request->all(),
            'timestamp' => now()
        ]);
    });
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

Route::middleware(['auth:web'])->prefix('company')->group(function () {
    Route::get('/users', [AuthController::class, 'getCompanyUsers']);
    Route::post('/users', [AuthController::class, 'createCompanyUser']);
});

/*
|--------------------------------------------------------------------------
| Ollama Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health/ollama', function () {
    // URLs para testar
    $internalUrl = 'http://ollama:11434';
    $externalUrl = config('ollama.url');
    
    $result = [
        'internal_url' => $internalUrl,
        'external_url' => $externalUrl,
        'internal_status' => 'unknown',
        'external_status' => 'unknown',
        'active_url' => null,
        'models_available' => [],
        'timestamp' => now()
    ];
    
    // Testar URL interna primeiro
    try {
        $response = Http::timeout(3)->get($internalUrl . '/api/tags');
        if ($response->successful()) {
            $result['internal_status'] = 'online';
            $result['active_url'] = $internalUrl;
            $result['models_available'] = $response->json()['models'] ?? [];
        } else {
            $result['internal_status'] = 'offline';
        }
    } catch (\Exception $e) {
        $result['internal_status'] = 'error: ' . $e->getMessage();
    }
    
    // Se URL externa for diferente da interna, testar também
    if ($externalUrl !== $internalUrl) {
        try {
            $response = Http::timeout(3)->get($externalUrl . '/api/tags');
            if ($response->successful()) {
                $result['external_status'] = 'online';
                if (!$result['active_url']) {
                    $result['active_url'] = $externalUrl;
                    $result['models_available'] = $response->json()['models'] ?? [];
                }
            } else {
                $result['external_status'] = 'offline';
            }
        } catch (\Exception $e) {
            $result['external_status'] = 'error: ' . $e->getMessage();
        }
    } else {
        $result['external_status'] = 'same_as_internal';
    }
    
    // Determinar status geral
    $result['ollama_status'] = $result['active_url'] ? 'online' : 'offline';
    
    return response()->json($result);
});
