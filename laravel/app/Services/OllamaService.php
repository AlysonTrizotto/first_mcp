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
        // Forçar uso da URL interna do Docker para evitar problemas com URLs externas
        $this->baseUrl = 'http://ollama:11434';
        $this->model = config('ollama.model', 'llama3.2:latest');
    }
    
    public function chat(string $message, int $companyId): array
    {
        try {
            Log::info('Ollama Request', [
                'url' => $this->baseUrl,
                'model' => $this->model,
                'message' => $message,
                'company_id' => $companyId
            ]);
            
            $response = Http::timeout(10)
                ->post($this->baseUrl . '/api/generate', [
                    'model' => $this->model,
                    'prompt' => $this->buildPrompt($message, $companyId),
                    'stream' => false
                ]);
            
            Log::info('Ollama Response', [
                'status' => $response->status(),
                'body' => $response->body(),
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