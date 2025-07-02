<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Http;

class MCPApiTest extends TestCase
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
                'response' => 'Esta é uma resposta de teste da IA para a empresa.'
            ], 200)
        ]);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected()
    {
        $response = $this->postJson('/api/mcp/chat', [
            'message' => 'Teste'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_send_chat_message()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/mcp/chat', [
            'message' => 'Olá, como você pode me ajudar?',
            'context' => ['test' => true]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'company_id' => $this->companyId
        ]);
        
        $response->assertJsonStructure([
            'success',
            'response',
            'company_id',
            'timestamp'
        ]);
    }

    /** @test */
    public function chat_message_validation_works()
    {
        Sanctum::actingAs($this->user);

        // Mensagem muito longa
        $longMessage = str_repeat('a', 2001);
        
        $response = $this->postJson('/api/mcp/chat', [
            'message' => $longMessage
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }

    /** @test */
    public function chat_message_is_logged_to_database()
    {
        Sanctum::actingAs($this->user);

        $message = 'Teste de mensagem para log';
        
        $response = $this->postJson('/api/mcp/chat', [
            'message' => $message,
            'context' => ['source' => 'test']
        ]);

        $response->assertStatus(200);
        
        // Verificar se foi logado no banco
        $this->assertDatabaseHas('mcp_interactions', [
            'company_id' => $this->companyId,
            'user_id' => $this->user->id,
            'message' => $message
        ]);
    }

    /** @test */
    public function empty_message_is_rejected()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/mcp/chat', [
            'message' => ''
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }

    /** @test */
    public function mcp_status_endpoint_works()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/mcp/status');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'active',
            'version' => '1.0.0'
        ]);
        
        $response->assertJsonStructure([
            'status',
            'model',
            'version',
            'timestamp'
        ]);
    }

    /** @test */
    public function ollama_health_check_works()
    {
        $response = $this->getJson('/api/health/ollama');

        $response->assertStatus(200);
        $response->assertJson([
            'ollama_status' => 'online'
        ]);
        
        $response->assertJsonStructure([
            'ollama_status',
            'url',
            'models_available',
            'timestamp'
        ]);
    }

    /** @test */
    public function ollama_health_check_handles_offline_status()
    {
        // Mock falha na conexão
        Http::fake([
            'http://localhost:11434/api/tags' => Http::response([], 500)
        ]);

        $response = $this->getJson('/api/health/ollama');

        $response->assertStatus(503);
        $response->assertJson([
            'ollama_status' => 'offline'
        ]);
    }

    /** @test */
    public function user_isolation_works_correctly()
    {
        // Criar usuário de outra empresa
        $otherUser = User::factory()->create([
            'company_id' => 999
        ]);

        // Autenticar como usuário da empresa 1
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/mcp/chat', [
            'message' => 'Mensagem da empresa 1'
        ]);

        $response->assertStatus(200);
        
        // Verificar se foi salvo com company_id correto
        $this->assertDatabaseHas('mcp_interactions', [
            'company_id' => $this->companyId,
            'user_id' => $this->user->id,
            'message' => 'Mensagem da empresa 1'
        ]);

        // Verificar que não foi salvo para outra empresa
        $this->assertDatabaseMissing('mcp_interactions', [
            'company_id' => 999,
            'message' => 'Mensagem da empresa 1'
        ]);
    }

    /** @test */
    public function context_is_properly_stored()
    {
        Sanctum::actingAs($this->user);

        $context = [
            'page' => 'dashboard',
            'action' => 'user_query',
            'timestamp' => now()->toISOString()
        ];

        $response = $this->postJson('/api/mcp/chat', [
            'message' => 'Teste com contexto',
            'context' => $context
        ]);

        $response->assertStatus(200);
        
        // Verificar se o contexto foi salvo corretamente
        $interaction = \DB::table('mcp_interactions')
            ->where('company_id', $this->companyId)
            ->where('message', 'Teste com contexto')
            ->first();

        $this->assertNotNull($interaction);
        
        $storedContext = json_decode($interaction->context, true);
        $this->assertEquals($context['page'], $storedContext['page']);
        $this->assertEquals($context['action'], $storedContext['action']);
    }

    /** @test */
    public function api_handles_ollama_connection_errors()
    {
        // Mock erro de conexão
        Http::fake([
            'http://localhost:11434/api/generate' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/mcp/chat', [
            'message' => 'Teste erro de conexão'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false
        ]);
    }
}
