<?php

namespace App\Services\MCP;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyAwareMCPServer 
{
    protected $companyId;
    protected $user;
    
    public function __construct()
    {
        $this->user = Auth::user();
        $this->companyId = $this->user ? $this->user->company_id : 1; // Usar empresa padrão se não autenticado
    }
    
    public function processMessage(string $message, array $additionalContext = []): array
    {
        $context = $this->buildCompanyContext($additionalContext);
        
        return $this->sendToAI($message, $context);
    }
    
    protected function buildCompanyContext(array $additionalContext = []): array
    {
        $company = $this->getCompanyInfo();
        
        return array_merge([
            'company_id' => $this->companyId,
            'company_name' => $company->name ?? 'Empresa Padrão',
            'user_id' => $this->user ? $this->user->id : null,
            'user_name' => $this->user ? $this->user->name : 'Usuário Anônimo',
            'user_permissions' => $this->getUserPermissions(),
            'available_tables' => $this->getAccessibleTables(),
            'company_config' => $this->getCompanyConfig(),
            'date_context' => now()->format('Y-m-d H:i:s'),
        ], $additionalContext);
    }
    
    protected function getCompanyInfo()
    {
        return DB::table('companies')
            ->where('id', $this->companyId)
            ->first();
    }
    
    protected function getUserPermissions(): array
    {
        // Busca permissões do usuário na empresa
        if (!$this->user) {
            return ['anonymous_access']; // Permissões padrão para usuários não autenticados
        }
        
        return DB::table('user_permissions')
            ->where('user_id', $this->user->id)
            ->where('company_id', $this->companyId)
            ->pluck('permission')
            ->toArray();
    }
    
    protected function getAccessibleTables(): array
    {
        // Define quais tabelas o MCP pode acessar para esta empresa
        return [
            'users' => 'SELECT id, name, email FROM users WHERE company_id = ' . $this->companyId,
            'products' => 'SELECT * FROM products WHERE company_id = ' . $this->companyId,
            'orders' => 'SELECT * FROM orders WHERE company_id = ' . $this->companyId,
            'customers' => 'SELECT * FROM customers WHERE company_id = ' . $this->companyId,
        ];
    }
    
    protected function getCompanyConfig(): array
    {
        $config = DB::table('company_mcp_configs')
            ->where('company_id', $this->companyId)
            ->first();
            
        return $config ? json_decode($config->config, true) : $this->getDefaultConfig();
    }
    
    protected function getDefaultConfig(): array
    {
        return [
            'ai_model' => config('ollama.model', 'llama3'),
            'max_context_length' => config('ollama.max_context', 4000),
            'allowed_tools' => ['get_users', 'get_analytics', 'get_products', 'create_customer'],
            'custom_instructions' => 'Você é um assistente IA especializado para esta empresa. Sempre responda em português e seja útil.',
            'security_rules' => [
                'never_show_sensitive_data' => true,
                'respect_company_isolation' => true,
                'log_all_interactions' => true
            ]
        ];
    }
    
    protected function sendToAI(string $message, array $context): array
    {
        $ollamaService = app(\App\Services\OllamaService::class);
        
        // Usar prompt simples e direto
        $result = $ollamaService->generateResponse($message);
        
        // Log da interação
        $this->logInteraction($message, $result, $context);
        
        return $result;
    }
    
    protected function buildEnhancedPrompt(string $message, array $context): string
    {
        // Prompt simples e direto para evitar problemas
        return $message;
    }
    
    protected function logInteraction(string $message, array $result, array $context): void
    {
        DB::table('mcp_interactions')->insert([
            'company_id' => $this->companyId,
            'user_id' => $this->user ? $this->user->id : null,
            'message' => $message,
            'response' => json_encode($result),
            'context' => json_encode($context),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}