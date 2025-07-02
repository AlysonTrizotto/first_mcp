#!/bin/bash

echo "🚀 Iniciando Laravel MCP..."

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

# Executar migrations
php artisan migrate --force

# Limpar caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Laravel configurado com sucesso!"
echo "🌐 Aplicação disponível em: http://localhost:8000"
echo "🤖 Ollama disponível em: http://localhost:11434"

# Iniciar servidor PHP
php artisan serve --host=0.0.0.0 --port=8000
