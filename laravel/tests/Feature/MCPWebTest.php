<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class MCPWebTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar usuário de teste
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_id' => 1,
        ]);
        
        $this->companyId = $this->user->company_id;
        
        // Mock das respostas do Ollama
        Http::fake([
            'http://localhost:11434/api/tags' => Http::response([
                'models' => [
                    ['name' => 'llama3'],
                    ['name' => 'mistral']
                ]
            ], 200),
            'http://localhost:11434/api/generate' => Http::response([
                'response' => 'Esta é uma resposta de teste da IA.'
            ], 200)
        ]);
    }

    /** @test */
    public function guest_cannot_access_mcp_dashboard()
    {
        $response = $this->get('/mcp');
        
        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        $response = $this->actingAs($this->user)->get('/mcp');
        
        $response->assertStatus(200);
        $response->assertViewIs('mcp.dashboard');
        $response->assertSee('Dashboard MCP');
    }

    /** @test */
    public function dashboard_displays_correct_stats()
    {
        // Criar algumas interações de teste
        \DB::table('mcp_interactions')->insert([
            [
                'company_id' => $this->companyId,
                'user_id' => $this->user->id,
                'message' => 'Teste 1',
                'response' => json_encode(['response' => 'Resposta 1']),
                'context' => json_encode(['test' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $this->companyId,
                'user_id' => $this->user->id,
                'message' => 'Teste 2',
                'response' => json_encode(['response' => 'Resposta 2']),
                'context' => json_encode(['test' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $response = $this->actingAs($this->user)->get('/mcp');
        
        $response->assertStatus(200);
        $response->assertSee('2'); // Total de interações
        $response->assertSee('Ollama'); // Status do sistema
    }

    /** @test */
    public function user_can_access_chat_interface()
    {
        $response = $this->actingAs($this->user)->get('/mcp/chat');
        
        $response->assertStatus(200);
        $response->assertViewIs('mcp.chat');
        $response->assertSee('Chat com IA');
        $response->assertSee('Digite sua mensagem');
    }

    /** @test */
    public function user_can_access_analytics()
    {
        $response = $this->actingAs($this->user)->get('/mcp/analytics');
        
        $response->assertStatus(200);
        $response->assertViewIs('mcp.analytics');
        $response->assertSee('Analytics e Relatórios');
    }

    /** @test */
    public function user_can_access_settings()
    {
        $response = $this->actingAs($this->user)->get('/mcp/settings');
        
        $response->assertStatus(200);
        $response->assertViewIs('mcp.settings');
        $response->assertSee('Configurações MCP');
    }

    /** @test */
    public function user_can_update_settings()
    {
        $response = $this->actingAs($this->user)
            ->post('/mcp/settings', [
                'ai_model' => 'llama3',
                'max_context_length' => 4000,
                'custom_instructions' => 'Teste de instruções personalizadas',
                'allowed_tools' => ['get_users', 'get_analytics']
            ]);

        $response->assertRedirect('/mcp/settings');
        $response->assertSessionHas('success');

        // Verificar se as configurações foram salvas
        $this->assertDatabaseHas('company_mcp_configs', [
            'company_id' => $this->companyId,
            'ai_model' => 'llama3',
            'max_context_length' => 4000,
            'custom_instructions' => 'Teste de instruções personalizadas'
        ]);
    }

    /** @test */
    public function settings_validation_works()
    {
        $response = $this->actingAs($this->user)
            ->post('/mcp/settings', [
                'ai_model' => '', // Campo obrigatório
                'max_context_length' => 500, // Muito baixo
                'custom_instructions' => str_repeat('a', 1001), // Muito longo
            ]);

        $response->assertSessionHasErrors([
            'ai_model',
            'max_context_length',
            'custom_instructions'
        ]);
    }

    /** @test */
    public function user_cannot_access_other_company_data()
    {
        // Criar outro usuário de outra empresa
        $otherUser = User::factory()->create([
            'company_id' => 999
        ]);

        // Criar interação para outra empresa
        \DB::table('mcp_interactions')->insert([
            'company_id' => 999,
            'user_id' => $otherUser->id,
            'message' => 'Mensagem secreta',
            'response' => json_encode(['response' => 'Resposta secreta']),
            'context' => json_encode(['secret' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tentar acessar analytics (não deve ver dados da outra empresa)
        $response = $this->actingAs($this->user)->get('/mcp/analytics');
        
        $response->assertStatus(200);
        $response->assertDontSee('Mensagem secreta');
        $response->assertDontSee('Resposta secreta');
    }

    /** @test */
    public function login_page_is_accessible()
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertSee('MCP Dashboard');
    }

    /** @test */
    public function user_can_authenticate()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/mcp');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function invalid_credentials_are_rejected()
    {
        $response = $this->post('/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
