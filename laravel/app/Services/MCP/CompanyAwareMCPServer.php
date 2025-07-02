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
        $this->companyId = $this->user->company_id;
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
            'company_name' => $company->name,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
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
        
        // Construir prompt com contexto da empresa
        $enhancedMessage = $this->buildEnhancedPrompt($message, $context);
        
        $result = $ollamaService->chat($enhancedMessage, $this->companyId);
        
        // Log da interação
        $this->logInteraction($message, $result, $context);
        
        return $result;
    }
    
    protected function buildEnhancedPrompt(string $message, array $context): string
    {
        $config = $this->getCompanyConfig();
        $prompt = $config['custom_instructions'] . "\n\n";
        
        $prompt .= "Contexto da Empresa:\n";
        $prompt .= "- Empresa ID: " . $context['company_id'] . "\n";
        $prompt .= "- Nome da Empresa: " . $context['company_name'] . "\n";
        $prompt .= "- Usuário: " . $context['user_name'] . " (ID: " . $context['user_id'] . ")\n";
        $prompt .= "- Data/Hora: " . $context['date_context'] . "\n\n";
        
        if (!empty($context['user_permissions'])) {
            $prompt .= "Permissões do Usuário: " . implode(', ', $context['user_permissions']) . "\n\n";
        }
        
        $prompt .= "Pergunta do usuário: " . $message;
        
        return $prompt;
    }
    
    protected function logInteraction(string $message, array $result, array $context): void
    {
        DB::table('mcp_interactions')->insert([
            'company_id' => $this->companyId,
            'user_id' => $this->user->id,
            'message' => $message,
            'response' => json_encode($result),
            'context' => json_encode($context),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}