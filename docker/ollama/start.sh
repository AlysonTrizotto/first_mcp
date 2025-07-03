#!/bin/bash

echo "🤖 Iniciando Ollama..."

# Iniciar Ollama em background
ollama serve &

# Aguardar o serviço inicializar
echo "⏳ Aguardando Ollama inicializar..."
sleep 10

# Auto-download do modelo especificado
MODEL=${OLLAMA_MODEL:-gemma2:2b}
echo "📦 Verificando modelo: $MODEL"

if ! ollama list | grep -q "$MODEL"; then
    echo "⬇️  Baixando modelo $MODEL pela primeira vez..."
    echo "⚠️  Este processo pode demorar alguns minutos..."
    ollama pull "$MODEL"
    echo "✅ Modelo $MODEL baixado com sucesso!"
else
    echo "✅ Modelo $MODEL já está disponível!"
fi

# Certificar que o modelo padrão esteja disponível
echo "🔧 Configurando modelo padrão para gemma2:2b..."
if ! ollama list | grep -q "gemma2:2b"; then
    echo "⬇️  Baixando modelo gemma2:2b (padrão da aplicação)..."
    ollama pull gemma2:2b
    echo "✅ Modelo gemma2:2b configurado!"
fi

echo "🎉 Ollama está pronto!"
echo "🔗 API disponível em: http://localhost:11434"
echo "🤖 Modelo ativo: $MODEL"

# Manter container rodando
wait
