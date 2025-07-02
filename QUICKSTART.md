# Laravel MCP - Quick Start Guide

## 🚀 Deploy Rápido

1. **Clone e configure:**
   ```bash
   git clone <repo-url>
   cd first_mcp
   
   # Configure modelo (opcional)
   echo "OLLAMA_MODEL=llama3.2" > .env.docker
   ```

2. **Subir aplicação:**
   ```bash
   docker-compose up -d --build
   # ou
   make up
   ```

3. **Testar:**
   ```bash
   ./test.sh
   ```

## 🎯 URLs

- **App:** http://localhost:8000
- **API:** http://localhost:8000/api
- **Ollama:** http://localhost:11434

## 🔧 Comandos Úteis

```bash
make up          # Subir
make down        # Parar  
make logs        # Ver logs
make status      # Status
make test        # Testes
make clean       # Limpar tudo
```

## 📱 Exemplo API

```bash
# Registrar usuário
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João",
    "email": "joao@empresa.com", 
    "password": "senha123",
    "company_name": "Minha Empresa"
  }'

# Chat com IA  
curl -X POST http://localhost:8000/api/mcp/chat \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message": "Olá!"}'
```

## 🔧 Modelos Disponíveis

- `llama3.2` (recomendado)
- `llama3.1` 
- `llama3`
- `codellama`
- `mistral`
- `phi3`
