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
}
