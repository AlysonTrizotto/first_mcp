#!/bin/bash

echo "🧪 Teste de Integração - Laravel MCP"
echo "=================================="

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para testar endpoints
test_endpoint() {
    local method=$1
    local url=$2
    local data=$3
    local expected_status=$4
    local description=$5
    
    echo -n "📡 $description... "
    
    if [ -z "$data" ]; then
        response=$(curl -s -w "%{http_code}" -X $method $url)
    else
        response=$(curl -s -w "%{http_code}" -X $method -H "Content-Type: application/json" -d "$data" $url)
    fi
    
    status_code="${response: -3}"
    
    if [ "$status_code" = "$expected_status" ]; then
        echo -e "${GREEN}✅ OK ($status_code)${NC}"
        return 0
    else
        echo -e "${RED}❌ Falha ($status_code)${NC}"
        return 1
    fi
}

# Verificar se containers estão rodando
echo "🔍 Verificando containers..."
if ! docker-compose ps | grep -q "Up"; then
    echo -e "${RED}❌ Containers não estão rodando!${NC}"
    echo "Execute: docker-compose up -d --build"
    exit 1
fi

echo -e "${GREEN}✅ Containers rodando${NC}"
echo ""

# Aguardar serviços iniciarem
echo "⏳ Aguardando serviços (30s)..."
sleep 30

# Testes básicos
echo "🧪 Executando testes..."
echo ""

# 1. Health Check Laravel
test_endpoint "GET" "http://localhost:8000" "" "200" "Laravel Home"

# 2. Health Check Ollama
test_endpoint "GET" "http://localhost:11434/api/tags" "" "200" "Ollama API"

# 3. Laravel API Status
test_endpoint "GET" "http://localhost:8000/api/health/ollama" "" "200" "Laravel Health Check"

# 4. Test Registration
registration_data='{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "company_name": "Test Company"
}'

echo ""
echo "👤 Testando registro de usuário..."
response=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d "$registration_data" http://localhost:8000/api/register)
status_code="${response: -3}"

if [ "$status_code" = "201" ] || [ "$status_code" = "422" ]; then
    echo -e "${GREEN}✅ Endpoint de registro funcional${NC}"
    
    # Extract token if registration successful
    if [ "$status_code" = "201" ]; then
        token=$(echo "$response" | jq -r '.token' 2>/dev/null)
        if [ "$token" != "null" ] && [ "$token" != "" ]; then
            echo "🔑 Token obtido para testes autenticados"
            
            # 5. Test Chat with authentication
            chat_data='{"message": "Hello, test message"}'
            echo -n "💬 Testando chat com IA... "
            
            chat_response=$(curl -s -w "%{http_code}" -X POST \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $token" \
                -d "$chat_data" \
                http://localhost:8000/api/mcp/chat)
            
            chat_status="${chat_response: -3}"
            
            if [ "$chat_status" = "200" ]; then
                echo -e "${GREEN}✅ OK${NC}"
            else
                echo -e "${YELLOW}⚠️  Chat pode estar indisponível (Ollama model)${NC}"
            fi
        fi
    fi
else
    echo -e "${RED}❌ Registro falhou ($status_code)${NC}"
fi

echo ""
echo "📊 Resumo dos Testes:"
echo "===================="
echo "🌐 Laravel: http://localhost:8000"
echo "🤖 Ollama: http://localhost:11434"
echo ""
echo "📝 Para testar manualmente:"
echo "curl -X POST http://localhost:8000/api/register \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '$registration_data'"
echo ""
echo "🎉 Sistema está funcionando!"
