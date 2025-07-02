<?php

namespace App\Http\Controllers;

use App\Services\MCP\CompanyAwareMCPServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MCPController extends Controller
{
    protected $mcpServer;
    
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware(\App\Http\Middleware\EnsureCompanyAccess::class);
    }
    
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'context' => 'nullable|array'
        ]);

        try {
            $mcpServer = new CompanyAwareMCPServer();
            
            $response = $mcpServer->processMessage(
                $request->input('message'),
                $request->input('context', [])
            );
            
            return response()->json([
                'success' => true,
                'response' => $response,
                'company_id' => auth()->user()->company_id,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar mensagem',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}