<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\MCP\CompanyAwareMCPServer;

class MCPWebController extends Controller
{
    public function __construct()
    {
        // Aplicar middlewares apenas para rotas que precisam de autenticação
        $this->middleware('auth')->except(['authenticate', 'register']);
        $this->middleware(\App\Http\Middleware\EnsureCompanyAccess::class)->except(['authenticate', 'register']);
    }

    /**
     * Dashboard principal do MCP
     */
    public function index()
    {
        $user = Auth::user();
        
        // Estatísticas básicas
        $stats = [
            'total_interactions' => DB::table('mcp_interactions')
                ->where('company_id', $user->company_id)
                ->count(),
            'interactions_today' => DB::table('mcp_interactions')
                ->where('company_id', $user->company_id)
                ->whereDate('created_at', today())
                ->count(),
            'active_users' => DB::table('mcp_interactions')
                ->where('company_id', $user->company_id)
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->distinct('user_id')
                ->count(),
        ];

        // Configuração da empresa
        $companyConfig = DB::table('company_mcp_configs')
            ->where('company_id', $user->company_id)
            ->first();

        // Status do Ollama
        $ollamaStatus = $this->checkOllamaStatus();

        return view('mcp.dashboard', compact('stats', 'companyConfig', 'ollamaStatus'));
    }

    /**
     * Interface de chat
     */
    public function chat()
    {
        $user = Auth::user();
        
        // Histórico recente de conversas
        $recentMessages = DB::table('mcp_interactions')
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('mcp.chat', compact('recentMessages'));
    }

    /**
     * Analytics e relatórios
     */
    public function analytics()
    {
        $user = Auth::user();
        
        // Dados para gráficos
        $interactionsByDay = DB::table('mcp_interactions')
            ->where('company_id', $user->company_id)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topUsers = DB::table('mcp_interactions')
            ->join('users', 'mcp_interactions.user_id', '=', 'users.id')
            ->where('mcp_interactions.company_id', $user->company_id)
            ->whereDate('mcp_interactions.created_at', '>=', now()->subDays(30))
            ->select('users.name', DB::raw('COUNT(*) as interaction_count'))
            ->groupBy('users.id', 'users.name')
            ->orderBy('interaction_count', 'desc')
            ->take(10)
            ->get();

        return view('mcp.analytics', compact('interactionsByDay', 'topUsers'));
    }

    /**
     * Configurações
     */
    public function settings()
    {
        $user = Auth::user();
        
        $config = DB::table('company_mcp_configs')
            ->where('company_id', $user->company_id)
            ->first();

        $availableModels = config('ollama.models', []);

        return view('mcp.settings', compact('config', 'availableModels'));
    }

    /**
     * Atualizar configurações
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'ai_model' => 'required|string|max:255',
            'max_context_length' => 'required|integer|min:1000|max:8000',
            'custom_instructions' => 'nullable|string|max:1000',
            'allowed_tools' => 'nullable|array',
        ]);

        $user = Auth::user();

        DB::table('company_mcp_configs')->updateOrInsert(
            ['company_id' => $user->company_id],
            [
                'ai_model' => $request->ai_model,
                'max_context_length' => $request->max_context_length,
                'custom_instructions' => $request->custom_instructions,
                'allowed_tools' => json_encode($request->allowed_tools ?? []),
                'updated_at' => now(),
            ]
        );

        return redirect()->route('mcp.settings')
            ->with('success', 'Configurações atualizadas com sucesso!');
    }

    /**
     * Verificar status do Ollama
     */
    private function checkOllamaStatus()
    {
        try {
            $response = Http::timeout(5)->get(config('ollama.url') . '/api/tags');
            
            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'models' => $response->successful() ? $response->json()['models'] ?? [] : [],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Autenticação simples (se não usar Breeze/Jetstream)
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            return redirect()->intended('/mcp');
        }

        return back()->withErrors([
            'email' => 'Credenciais inválidas.',
        ]);
    }

    /**
     * Registro de nova conta e empresa
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Criar empresa
            $company = DB::table('companies')->insertGetId([
                'name' => $request->company_name,
                'settings' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Criar usuário admin
            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'company_id' => $company,
                'is_admin' => true,
            ]);

            // Configuração inicial da empresa
            DB::table('company_mcp_configs')->insert([
                'company_id' => $company,
                'model_name' => config('ollama.model', 'llama3.2'),
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'settings' => json_encode([
                    'system_prompt' => 'Você é um assistente IA útil e prestativo.',
                    'max_history' => 10,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Login automático
            Auth::login($user);

            return redirect('/mcp')->with('success', 'Conta criada com sucesso! Bem-vindo ao Laravel MCP.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withErrors([
                'email' => 'Erro ao criar conta. Tente novamente.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }
    }
}
