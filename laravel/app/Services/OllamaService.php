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
        $this->baseUrl = config('ollama.url', 'http://localhost:11434');
        $this->model = config('ollama.model', 'llama3');
    }
    
    public function chat(string $message, int $companyId): array
    {
        try {
            $response = Http::timeout(60)
                ->post($this->baseUrl . '/api/generate', [
                    'model' => $this->model,
                    'prompt' => $this->buildPrompt($message, $companyId),
                    'stream' => false
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
                'company_id' => $companyId
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