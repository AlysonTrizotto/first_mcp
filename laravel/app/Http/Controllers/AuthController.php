<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'company_cnpj' => 'nullable|string|max:20|unique:companies,cnpj',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Criar empresa primeiro
            $companyId = DB::table('companies')->insertGetId([
                'name' => $request->company_name,
                'cnpj' => $request->company_cnpj,
                'phone' => $request->company_phone,
                'address' => $request->company_address,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Criar usuário
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $companyId,
                'is_admin' => true, // Primeiro usuário da empresa é admin
            ]);

            // Criar configuração padrão MCP para a empresa
            DB::table('company_mcp_configs')->insert([
                'company_id' => $companyId,
                'ai_model' => config('ollama.model', 'llama3'),
                'max_context_length' => config('ollama.max_context', 4000),
                'allowed_tools' => json_encode(['get_users', 'get_analytics', 'get_products']),
                'custom_instructions' => 'Você é um assistente IA especializado para esta empresa. Sempre responda em português e seja útil.',
                'security_rules' => json_encode([
                    'never_show_sensitive_data' => true,
                    'respect_company_isolation' => true,
                    'log_all_interactions' => true
                ]),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Criar token de acesso
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuário e empresa criados com sucesso!',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'company_id' => $user->company_id,
                        'is_admin' => $user->is_admin,
                    ],
                    'company' => [
                        'id' => $companyId,
                        'name' => $request->company_name,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro ao criar usuário'
            ], 500);
        }
    }

    /**
     * Login do usuário
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ], 401);
        }

        // Verificar se a empresa está ativa
        $company = DB::table('companies')
            ->where('id', $user->company_id)
            ->where('active', true)
            ->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa inativa ou inexistente'
            ], 403);
        }

        // Revogar tokens antigos (opcional)
        $user->tokens()->delete();

        // Criar novo token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                    'is_admin' => $user->is_admin ?? false,
                ],
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Logout do usuário
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso'
        ]);
    }

    /**
     * Listar usuários da empresa (apenas admins)
     */
    public function getCompanyUsers(Request $request)
    {
        $user = $request->user();
        
        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem visualizar usuários.'
            ], 403);
        }

        $users = User::where('company_id', $user->company_id)
            ->select('id', 'name', 'email', 'is_admin', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Criar usuário adicional na empresa (apenas admins)
     */
    public function createCompanyUser(Request $request)
    {
        $currentUser = $request->user();
        
        if (!$currentUser->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem criar usuários.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'is_admin' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $currentUser->company_id,
                'is_admin' => $request->is_admin ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuário criado com sucesso!',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                    'is_admin' => $user->is_admin,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar usuário',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro interno'
            ], 500);
        }
    }
}
