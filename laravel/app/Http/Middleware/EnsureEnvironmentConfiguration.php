<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnvironmentConfiguration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Garantir que a URL da aplicação seja dinâmica em ambientes como GitHub Codespaces
        $this->ensureAppUrlIsCorrect();
        
        // Garantir que a URL do Ollama seja dinâmica
        $this->ensureOllamaUrlIsCorrect();
        
        return $next($request);
    }
    
    private function ensureAppUrlIsCorrect(): void
    {
        // Se estivermos no GitHub Codespaces, garantir que a URL seja correta
        if (isset($_SERVER['CODESPACE_NAME']) && isset($_SERVER['GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN'])) {
            $codespaceUrl = 'https://' . $_SERVER['CODESPACE_NAME'] . '-8000.' . $_SERVER['GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN'];
            
            // Atualizar a configuração da aplicação se necessário
            if (config('app.url') !== $codespaceUrl) {
                config(['app.url' => $codespaceUrl]);
            }
        }
    }

    private function ensureOllamaUrlIsCorrect(): void
    {
        // Se estivermos no GitHub Codespaces, configurar URL do Ollama dinamicamente
        if (isset($_SERVER['CODESPACE_NAME']) && isset($_SERVER['GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN'])) {
            $ollamaUrl = 'https://' . $_SERVER['CODESPACE_NAME'] . '-11434.' . $_SERVER['GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN'];
            
            // Atualizar a configuração do Ollama se necessário
            if (config('ollama.url') !== $ollamaUrl) {
                config(['ollama.url' => $ollamaUrl]);
            }
        }
    }
}
