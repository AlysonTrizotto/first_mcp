#!/bin/bash

echo "🚀 Iniciando Laravel MCP..."

# Detectar ambiente Codespaces e configurar URL dinamicamente
if [ ! -z "$CODESPACE_NAME" ]; then
    echo "🔗 Detectado GitHub Codespaces: $CODESPACE_NAME"
    APP_URL="https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
    echo "🌐 Configurando APP_URL: $APP_URL"
    
    # Atualizar .env com a URL correta
    sed -i "s|APP_URL=.*|APP_URL=$APP_URL|g" .env
    
    # Exportar variável para o ambiente atual
    export APP_URL="$APP_URL"
    
    # Tornar portas públicas no Codespaces
    echo "🌐 Configurando portas públicas no Codespaces..."
    gh codespace ports visibility 8000:public -c $CODESPACE_NAME 2>/dev/null || echo "⚠️  Não foi possível configurar porta 8000 (pode já estar configurada)"
    gh codespace ports visibility 11434:public -c $CODESPACE_NAME 2>/dev/null || echo "⚠️  Não foi possível configurar porta 11434 (pode já estar configurada ou gh CLI não disponível)"
else
    echo "🏠 Ambiente local detectado"
    APP_URL="http://localhost:8000"
    export APP_URL="$APP_URL"
fi

# Aguardar Ollama estar disponível
echo "⏳ Aguardando Ollama..."
while ! curl -s http://ollama:11434/api/tags > /dev/null; do
    sleep 2
done
echo "✅ Ollama está online!"

# Configurar Laravel
echo "🔧 Configurando Laravel..."

# As variáveis de ambiente já vêm do docker-compose via env_file
# Apenas garantir que o banco SQLite existe
touch database/database.sqlite

# Limpar caches primeiro (importante para recarregar configurações)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Executar migrations
php artisan migrate --force

# Otimizar para produção (depois de limpar)
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Laravel configurado com sucesso!"
echo "🌐 Aplicação disponível em: http://localhost:8000"
echo "🤖 Ollama disponível em: http://localhost:11434"

# Iniciar servidor PHP
php artisan serve --host=0.0.0.0 --port=8000
