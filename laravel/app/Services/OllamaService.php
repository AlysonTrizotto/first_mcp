<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected $baseUrl;
    protected $model;
    
    public function __construct()
    {
        // Usar localhost que está funcionando
        $this->baseUrl = $this->getOllamaUrl();
        $this->model = config('ollama.model', 'gemma2:2b');
        
        Log::info('OllamaService initialized', [
            'baseUrl' => $this->baseUrl,
            'model' => $this->model
        ]);
    }
    
    protected function getOllamaUrl(): string
    {
        // Tentar diferentes URLs na ordem de preferência
        $urls = [
            'http://localhost:11434',
            'http://ollama:11434',
            'http://ollama-mcp:11434'
        ];
        
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(3)->get($url . '/api/tags');
                if ($response->successful()) {
                    Log::info('Ollama URL selecionada: ' . $url);
                    return $url;
                }
            } catch (\Exception $e) {
                Log::debug('Ollama URL falhou: ' . $url . ' - ' . $e->getMessage());
                continue;
            }
        }
        
        // Se nenhuma URL funcionar, usar localhost como fallback
        Log::warning('Nenhuma URL do Ollama está respondendo, usando localhost como fallback');
        return 'http://localhost:11434';
    }
    
    public function chat(string $message, int $companyId): array
    {
        try {
            Log::info('Ollama Chat Request', [
                'url' => $this->baseUrl,
                'model' => $this->model,
                'message' => substr($message, 0, 100) . '...',
                'company_id' => $companyId
            ]);
            
            $startTime = microtime(true);
            
            $response = Http::timeout(30)
                ->post($this->baseUrl . '/api/generate', [
                    'model' => $this->model,
                    'prompt' => $this->buildPrompt($message, $companyId),
                    'stream' => false
                ]);
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            Log::info('Ollama Response Success', [
                'status' => $response->status(),
                'duration_ms' => $duration,
                'response_length' => strlen($response->body()),
                'model' => $this->model
            ]);
            
            if ($response->failed()) {
                Log::error('Ollama API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $this->baseUrl
                ]);
                throw new \Exception('Erro na API Ollama: HTTP ' . $response->status());
            }
            
            $responseData = $response->json();
            
            return [
                'success' => true,
                'response' => $responseData['response'] ?? 'Sem resposta',
                'model' => $this->model,
                'company_id' => $companyId,
                'duration_ms' => $duration
            ];
            
        } catch (\Exception $e) {
            Log::error('Ollama Error', [
                'message' => $e->getMessage(),
                'company_id' => $companyId,
                'url' => $this->baseUrl,
                'model' => $this->model,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $this->baseUrl,
                'model' => $this->model
            ];
        }
    }
    
    protected function buildPrompt(string $message, int $companyId): string
    {
        $prompt = "Você é um assistente IA inteligente e útil para a empresa (ID: $companyId). ";
        $prompt .= "Responda sempre em português de forma clara e concisa. ";
        $prompt .= "Se a pergunta for sobre matemática, responda com precisão. ";
        $prompt .= "Se for uma saudação, seja amigável e profissional. ";
        $prompt .= "\n\nPergunta: $message\n\nResposta:";
        
        return $prompt;
    }
}