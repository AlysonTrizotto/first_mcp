<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MCP\CompanyAwareMCPServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyAwareMCPServerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $mcpServer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar usuário de teste
        $this->user = User::factory()->create([
            'company_id' => 1,
            'name' => 'Test User'
        ]);
        
        // Autenticar usuário
        Auth::login($this->user);
        
        $this->mcpServer = new CompanyAwareMCPServer();
    }

    /** @test */
    public function builds_company_context_correctly()
    {
        // Criar dados de empresa fictícios
        DB::table('companies')->insert([
            'id' => 1,
            'name' => 'Test Company',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Usar reflexão para acessar método protegido
        $reflection = new \ReflectionClass($this->mcpServer);
        $method = $reflection->getMethod('buildCompanyContext');
        $method->setAccessible(true);

        $context = $method->invoke($this->mcpServer, ['extra' => 'data']);

        $this->assertEquals(1, $context['company_id']);
        $this->assertEquals('Test Company', $context['company_name']);
        $this->assertEquals($this->user->id, $context['user_id']);
        $this->assertEquals('Test User', $context['user_name']);
        $this->assertArrayHasKey('date_context', $context);
        $this->assertEquals('data', $context['extra']);
    }

    /** @test */
    public function gets_default_config_when_no_company_config()
    {
        $reflection = new \ReflectionClass($this->mcpServer);
        $method = $reflection->getMethod('getDefaultConfig');
        $method->setAccessible(true);

        $config = $method->invoke($this->mcpServer);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('ai_model', $config);
        $this->assertArrayHasKey('max_context_length', $config);
        $this->assertArrayHasKey('allowed_tools', $config);
        $this->assertArrayHasKey('custom_instructions', $config);
        $this->assertArrayHasKey('security_rules', $config);
    }

    /** @test */
    public function builds_enhanced_prompt_with_context()
    {
        DB::table('companies')->insert([
            'id' => 1,
            'name' => 'Test Company',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $reflection = new \ReflectionClass($this->mcpServer);
        $method = $reflection->getMethod('buildEnhancedPrompt');
        $method->setAccessible(true);

        $context = [
            'company_id' => 1,
            'company_name' => 'Test Company',
            'user_id' => $this->user->id,
            'user_name' => 'Test User',
            'date_context' => now()->format('Y-m-d H:i:s'),
            'user_permissions' => ['read', 'write']
        ];

        $prompt = $method->invoke($this->mcpServer, 'Como posso ajudar?', $context);

        $this->assertStringContainsString('Empresa ID: 1', $prompt);
        $this->assertStringContainsString('Test Company', $prompt);
        $this->assertStringContainsString('Test User', $prompt);
        $this->assertStringContainsString('Como posso ajudar?', $prompt);
        $this->assertStringContainsString('read, write', $prompt);
    }

    /** @test */
    public function gets_accessible_tables_with_company_filter()
    {
        $reflection = new \ReflectionClass($this->mcpServer);
        $method = $reflection->getMethod('getAccessibleTables');
        $method->setAccessible(true);

        $tables = $method->invoke($this->mcpServer);

        $this->assertIsArray($tables);
        $this->assertArrayHasKey('users', $tables);
        $this->assertArrayHasKey('products', $tables);
        $this->assertArrayHasKey('orders', $tables);
        $this->assertArrayHasKey('customers', $tables);

        // Verificar se todas as queries contêm filtro de company_id
        foreach ($tables as $table => $query) {
            $this->assertStringContainsString('company_id = 1', $query);
        }
    }

    /** @test */
    public function logs_interaction_correctly()
    {
        $reflection = new \ReflectionClass($this->mcpServer);
        $method = $reflection->getMethod('logInteraction');
        $method->setAccessible(true);

        $message = 'Teste de mensagem';
        $result = ['success' => true, 'response' => 'Resposta teste'];
        $context = ['test' => true];

        $method->invoke($this->mcpServer, $message, $result, $context);

        $this->assertDatabaseHas('mcp_interactions', [
            'company_id' => 1,
            'user_id' => $this->user->id,
            'message' => $message
        ]);

        $interaction = DB::table('mcp_interactions')
            ->where('message', $message)
            ->first();

        $this->assertEquals(json_encode($result), $interaction->response);
        $this->assertEquals(json_encode($context), $interaction->context);
    }

    /** @test */
    public function gets_company_config_from_database()
    {
        // Inserir configuração de teste
        DB::table('company_mcp_configs')->insert([
            'company_id' => 1,
            'ai_model' => 'llama3:70b',
            'max_context_length' => 8000,
            'config' => json_encode([
                'custom_setting' => 'test_value',
                'tools_enabled' => true
            ]),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $reflection = new \ReflectionClass($this->mcpServer);
        $method = $reflection->getMethod('getCompanyConfig');
        $method->setAccessible(true);

        $config = $method->invoke($this->mcpServer);

        $this->assertEquals('test_value', $config['custom_setting']);
        $this->assertTrue($config['tools_enabled']);
    }

    /** @test */
    public function falls_back_to_default_config_when_db_config_missing()
    {
        // Não inserir nenhuma configuração no banco
        
        $reflection = new \ReflectionClass($this->mcpServer);
        $method = $reflection->getMethod('getCompanyConfig');
        $method->setAccessible(true);

        $config = $method->invoke($this->mcpServer);

        // Deve retornar configuração padrão
        $this->assertArrayHasKey('ai_model', $config);
        $this->assertArrayHasKey('max_context_length', $config);
        $this->assertEquals(config('ollama.model', 'llama3'), $config['ai_model']);
    }

    /** @test */
    public function process_message_integrates_all_components()
    {
        // Configurar mocks necessários
        DB::table('companies')->insert([
            'id' => 1,
            'name' => 'Test Company',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Mock do OllamaService
        $this->mock(\App\Services\OllamaService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'success' => true,
                    'response' => 'Resposta integrada da IA',
                    'model' => 'llama3'
                ]);
        });

        $result = $this->mcpServer->processMessage('Como você pode me ajudar?');

        $this->assertTrue($result['success']);
        $this->assertEquals('Resposta integrada da IA', $result['response']);
        
        // Verificar se foi logado
        $this->assertDatabaseHas('mcp_interactions', [
            'company_id' => 1,
            'user_id' => $this->user->id,
            'message' => 'Como você pode me ajudar?'
        ]);
    }
}
