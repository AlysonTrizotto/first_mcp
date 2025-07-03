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
        // Tentar diferentes URLs para conexão robusta
        $this->baseUrl = $this->getOllamaUrl();
        $this->model = config('ollama.model', 'llama3.2:latest');
    }
    
    protected function getOllamaUrl(): string
    {
        // Tentar diferentes URLs na ordem de preferência
        $urls = [
            'http://ollama-mcp:11434',
            'http://172.18.0.2:11434',
            'http://localhost:11434'
        ];
        
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(2)->get($url . '/api/tags');
                if ($response->successful()) {
                    Log::info('Ollama URL selecionada: ' . $url);
                    return $url;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Se nenhuma URL funcionar, usar a primeira como fallback
        Log::warning('Nenhuma URL do Ollama está respondendo, usando fallback');
        return $urls[0];
    }
    
    public function chat(string $message, int $companyId): array
    {
        try {
            Log::info('Ollama Request', [
                'url' => $this->baseUrl,
                'model' => $this->model,
                'message' => $message,
                'company_id' => $companyId,
                'constructed_url' => $this->baseUrl
            ]);
            
            $startTime = microtime(true);
            
            $response = Http::timeout(15)
                ->post($this->baseUrl . '/api/generate', [
                    'model' => $this->model,
                    'prompt' => $this->buildPrompt($message, $companyId),
                    'stream' => false
                ]);
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            Log::info('Ollama Response', [
                'status' => $response->status(),
                'duration_ms' => $duration,
                'body_preview' => substr($response->body(), 0, 200),
                'headers' => $response->headers()
            ]);
            
            if ($response->failed()) {
                throw new \Exception('Erro na API Ollama: ' . $response->status());
            }
            
            return [
                'success' => true,
                'response' => $response->json()['response'] ?? '',
                'model' => $this->model,
                'company_id' => $companyId
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro Ollama', [
                'message' => $e->getMessage(),
                'company_id' => $companyId,
                'url' => $this->baseUrl,
                'model' => $this->model
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    protected function buildPrompt(string $message, int $companyId): string
    {
        $context = "Você é um assistente IA para a empresa ID: $companyId. ";
        $context .= "Responda sempre em português e seja útil. ";
        $context .= "Pergunta do usuário: $message";
        
        return $context;
    }
}