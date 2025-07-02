# 🚀 Laravel MCP - Sistema Multi-Tenant com IA

Sistema completo de **Model Context Protocol (MCP)** integrado ao **Ollama/Llama3** com arquitetura multi-tenant, desenvolvido em Laravel.

## ⚡ Início Rápido

```bash
# Clone o repositório
git clone <seu-repo>
cd first_mcp

# Configure o modelo (opcional - edite .env)
# OLLAMA_MODEL=llama3.2 (padrão)

# Subir aplicação completa
docker-compose up -d --build

# Ou usando Makefile
make up
```

**🌐 Acesse:** http://localhost:8000  
**🤖 Ollama API:** http://localhost:11434

## 🛠️ Comandos Úteis

```bash
# Ver logs em tempo real
make logs

# Ver status dos serviços
make status

# Parar aplicação
make down

# Reiniciar
make restart

# Limpar tudo
make clean

# Executar testes
make test

# Acessar shell do Laravel
make shell-laravel
```

## 🏗️ Arquitetura

### **Services:**
- **Laravel** (PHP 8.3 + Framework)
- **Ollama** (IA Local + Modelos LLM)
- **SQLite** (Banco de dados)

### **Funcionalidades:**
✅ **Multi-Tenant** - Empresas isoladas  
✅ **Autenticação** - Laravel Sanctum  
✅ **Chat IA** - Interface web + API  
✅ **Analytics** - Relatórios de uso  
✅ **API REST** - Endpoints completos  
✅ **Testes** - Feature + Unit tests  
✅ **Docker** - Deploy simplificado  

## 📊 Modelos Disponíveis

Configure no arquivo `.env`:

```bash
# Modelos suportados:
OLLAMA_MODEL=llama3.2     # 🔥 Recomendado (menor/rápido)
OLLAMA_MODEL=llama3.1     # Mais recente
OLLAMA_MODEL=llama3       # Padrão
OLLAMA_MODEL=codellama    # Código
OLLAMA_MODEL=mistral      # Alternativo
OLLAMA_MODEL=phi3         # Compacto
```

## 🌐 API Endpoints

### **Autenticação:**
```bash
POST /api/register        # Registrar usuário + empresa
POST /api/login          # Login
POST /api/logout         # Logout
GET  /api/user          # Dados do usuário
```

### **Chat IA:**
```bash
POST /api/mcp/chat       # Conversar com IA
GET  /api/mcp/status     # Status do sistema
```

### **Empresa (Admin):**
```bash
GET  /api/company/users  # Listar usuários
POST /api/company/users  # Criar usuário
```

### **Monitoramento:**
```bash
GET /api/health/ollama   # Status Ollama
```

## 🔑 Exemplo de Uso

### **1. Registrar primeira empresa:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@empresa.com",
    "password": "senha123",
    "company_name": "Minha Empresa"
  }'
```

### **2. Chat com IA:**
```bash
curl -X POST http://localhost:8000/api/mcp/chat \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{
    "message": "Olá! Como você pode me ajudar?"
  }'
```

## 🧪 Desenvolvimento

### **Executar testes:**
```bash
make test
# ou
docker-compose exec laravel php artisan test
```

### **Migrations:**
```bash
make dev-migrate
# ou
docker-compose exec laravel php artisan migrate
```

### **Acessar containers:**
```bash
make shell-laravel  # Laravel
make shell-ollama   # Ollama
```

## 📁 Estrutura do Projeto

```
├── docker-compose.yml     # Orquestração Docker
├── .env                  # Configurações centralizadas
├── Makefile              # Comandos simplificados
├── docker/
│   ├── laravel/          # Container Laravel
│   └── ollama/           # Container Ollama
└── laravel/              # Aplicação Laravel
    ├── app/
    │   ├── Http/Controllers/  # API + Web controllers
    │   ├── Models/           # User, Company
    │   └── Services/         # MCP, Ollama
    ├── routes/
    │   ├── api.php          # Rotas API
    │   └── web.php          # Rotas web
    ├── resources/views/     # Interface Blade
    └── tests/               # Testes automatizados
```

## 🔧 Configurações

### **Variáveis principais (.env):**
```bash
# Modelo IA
OLLAMA_MODEL=llama3.2

# Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

# Banco
DB_CONNECTION=sqlite
```

## 🚨 Troubleshooting

### **Ollama não responde:**
```bash
# Ver logs
make logs-ollama

# Verificar status
curl http://localhost:11434/api/tags
```

### **Laravel com erro:**
```bash
# Ver logs
make logs-laravel

# Acessar container
make shell-laravel
```

### **Reset completo:**
```bash
make clean    # Remove tudo
make up       # Recria
```

## 📚 Documentação Técnica

- **Laravel:** https://laravel.com/docs
- **Ollama:** https://ollama.ai/docs
- **Docker:** https://docs.docker.com

## 🤝 Contribuição

1. Fork o projeto
2. Crie feature branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push branch (`git push origin feature/nova-funcionalidade`)
5. Abra Pull Request

---

**🎯 Projeto pronto para produção com deploy simplificado via Docker!**
