<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forçar URL correta em ambientes como Codespaces
        if (config('app.url')) {
            $appUrl = config('app.url');
            URL::forceRootUrl($appUrl);
            
            // Se a URL usa HTTPS, forçar esquema HTTPS
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Configurar URL dinâmica do Ollama
        $this->configureOllamaUrl();
    }

    /**
     * Configura URL do Ollama dinamicamente baseada no ambiente
     */
    private function configureOllamaUrl(): void
    {
        $appUrl = config('app.url');
        
        // Se estivermos em Codespaces/ambiente externo
        if ($appUrl && str_contains($appUrl, '.app.github.dev')) {
            // Extrair o identificador único do Codespaces para construir URL do Ollama
            $pattern = '/https:\/\/([^-]+)-([^-]+)-([^\.]+)-(\d+)\.app\.github\.dev/';
            if (preg_match($pattern, $appUrl, $matches)) {
                $prefix = $matches[1];
                $middle = $matches[2]; 
                $suffix = $matches[3];
                
                // Construir URL do Ollama (porta 11434)
                $ollamaUrl = "https://{$prefix}-{$middle}-{$suffix}-11434.app.github.dev";
                
                // Definir dinamicamente no config
                config(['ollama.url' => $ollamaUrl]);
            } else {
                // Fallback: tentar usar URL interna se externa não estiver disponível
                $this->fallbackToInternalOllama();
            }
        } else {
            // Se estiver em ambiente local/Docker, usar configuração interna
            $this->configureLocalOllama();
        }
    }

    /**
     * Configura Ollama para ambiente local/Docker
     */
    private function configureLocalOllama(): void
    {
        // Em ambiente local, manter configuração do .env
        // mas garantir que use URL interna se necessário
        if (app()->environment('local', 'testing')) {
            config(['ollama.url' => env('OLLAMA_URL', 'http://ollama:11434')]);
        }
    }

    /**
     * Fallback para URL interna do Ollama
     */
    private function fallbackToInternalOllama(): void
    {
        config(['ollama.url' => env('OLLAMA_URL', 'http://ollama:11434')]);
    }
}
