<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $ollamaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ollamaService = new OllamaService();
    }

    /** @test */
    public function can_send_message_to_ollama()
    {
        Http::fake([
            'http://localhost:11434/api/generate' => Http::response([
                'response' => 'Esta é uma resposta de teste do Ollama.'
            ], 200)
        ]);

        $result = $this->ollamaService->chat('Olá, como você está?', 1);

        $this->assertTrue($result['success']);
        $this->assertEquals('Esta é uma resposta de teste do Ollama.', $result['response']);
        $this->assertEquals(1, $result['company_id']);
        $this->assertEquals(config('ollama.model'), $result['model']);
    }

    /** @test */
    public function handles_ollama_api_errors()
    {
        Http::fake([
            'http://localhost:11434/api/generate' => Http::response([], 500)
        ]);

        $result = $this->ollamaService->chat('Teste erro', 1);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function builds_prompt_with_company_context()
    {
        Http::fake([
            'http://localhost:11434/api/generate' => function ($request) {
                $body = json_decode($request->body(), true);
                $this->assertStringContainsString('empresa ID: 1', $body['prompt']);
                $this->assertStringContainsString('Teste de mensagem', $body['prompt']);
                
                return Http::response(['response' => 'OK'], 200);
            }
        ]);

        $this->ollamaService->chat('Teste de mensagem', 1);
    }

    /** @test */
    public function uses_correct_model_from_config()
    {
        config(['ollama.model' => 'llama3:70b']);
        
        Http::fake([
            'http://localhost:11434/api/generate' => function ($request) {
                $body = json_decode($request->body(), true);
                $this->assertEquals('llama3:70b', $body['model']);
                
                return Http::response(['response' => 'OK'], 200);
            }
        ]);

        $this->ollamaService->chat('Teste', 1);
    }

    /** @test */
    public function logs_errors_correctly()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Erro Ollama', \Mockery::type('array'));

        Http::fake([
            'http://localhost:11434/api/generate' => function () {
                throw new \Exception('Timeout error');
            }
        ]);

        $result = $this->ollamaService->chat('Teste', 1);
        
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function sends_stream_false_parameter()
    {
        Http::fake([
            'http://localhost:11434/api/generate' => function ($request) {
                $body = json_decode($request->body(), true);
                $this->assertFalse($body['stream']);
                
                return Http::response(['response' => 'OK'], 200);
            }
        ]);

        $this->ollamaService->chat('Teste', 1);
    }

    /** @test */
    public function handles_timeout_correctly()
    {
        Http::fake([
            'http://localhost:11434/api/generate' => function () {
                sleep(2); // Simular delay
                return Http::response(['response' => 'Delayed response'], 200);
            }
        ]);

        // Configurar timeout baixo para testar
        config(['ollama.timeout' => 1]);
        
        $start = microtime(true);
        $result = $this->ollamaService->chat('Teste timeout', 1);
        $end = microtime(true);
        
        // Deve falhar rapidamente devido ao timeout
        $this->assertLessThan(2, $end - $start);
    }

    /** @test */
    public function properly_formats_response_structure()
    {
        Http::fake([
            'http://localhost:11434/api/generate' => Http::response([
                'response' => 'Teste de resposta',
                'model' => 'llama3',
                'created_at' => '2025-07-02T12:00:00Z'
            ], 200)
        ]);

        $result = $this->ollamaService->chat('Teste', 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('company_id', $result);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Teste de resposta', $result['response']);
        $this->assertEquals(1, $result['company_id']);
    }
}
