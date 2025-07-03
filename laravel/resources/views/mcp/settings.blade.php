@extends('layouts.mcp')

@section('title', 'Configurações')
@section('page-title', 'Configurações MCP')

@section('content')
<div class="row">
    <div class="col-md-8">
        <form action="{{ route('mcp.settings.update') }}" method="POST">
            @csrf
            
            <!-- Configurações Básicas -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Configurações Básicas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="ai_model" class="form-label">Modelo de IA</label>
                            <select class="form-select @error('ai_model') is-invalid @enderror" 
                                    id="ai_model" name="ai_model" required>
                                @foreach($availableModels as $key => $model)
                                <option value="{{ $key }}" 
                                        {{ ($config->ai_model ?? config('ollama.model')) === $key ? 'selected' : '' }}>
                                    {{ $model['name'] }} - {{ $model['description'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('ai_model')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Modelo que será usado para processar as mensagens</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="max_context_length" class="form-label">Contexto Máximo (tokens)</label>
                            <input type="number" 
                                   class="form-control @error('max_context_length') is-invalid @enderror" 
                                   id="max_context_length" 
                                   name="max_context_length" 
                                   value="{{ $config->max_context_length ?? config('ollama.max_context', 4000) }}"
                                   min="1000" 
                                   max="8000" 
                                   required>
                            @error('max_context_length')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Quantidade máxima de tokens para contexto (1000-8000)</small>
                        </div>
                        
                        <div class="col-12">
                            <label for="custom_instructions" class="form-label">Instruções Personalizadas</label>
                            <textarea class="form-control @error('custom_instructions') is-invalid @enderror" 
                                      id="custom_instructions" 
                                      name="custom_instructions" 
                                      rows="4" 
                                      maxlength="1000"
                                      placeholder="Ex: Você é um assistente especializado em vendas para nossa empresa de tecnologia...">{{ $config->custom_instructions ?? '' }}</textarea>
                            @error('custom_instructions')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Instruções específicas para personalizar o comportamento da IA (máx. 1000 caracteres)</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ferramentas MCP -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Ferramentas MCP Disponíveis
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Selecione quais ferramentas a IA pode usar para sua empresa:</p>
                    
                    @php
                    $allowedTools = $config ? json_decode($config->allowed_tools, true) : ['get_users', 'get_analytics', 'get_products', 'create_customer'];
                    $tools = [
                        'get_users' => [
                            'name' => 'Buscar Usuários',
                            'description' => 'Permite que a IA busque e liste usuários da empresa',
                            'icon' => 'fas fa-users',
                            'color' => 'primary'
                        ],
                        'get_analytics' => [
                            'name' => 'Analytics',
                            'description' => 'Acesso a métricas e analytics da empresa',
                            'icon' => 'fas fa-chart-bar',
                            'color' => 'success'
                        ],
                        'get_products' => [
                            'name' => 'Listar Produtos',
                            'description' => 'Buscar e filtrar produtos do catálogo',
                            'icon' => 'fas fa-box',
                            'color' => 'info'
                        ],
                        'create_customer' => [
                            'name' => 'Criar Cliente',
                            'description' => 'Cadastrar novos clientes no sistema',
                            'icon' => 'fas fa-user-plus',
                            'color' => 'warning'
                        ],
                        'company_reports' => [
                            'name' => 'Relatórios da Empresa',
                            'description' => 'Gerar relatórios personalizados',
                            'icon' => 'fas fa-file-alt',
                            'color' => 'secondary'
                        ]
                    ];
                    @endphp
                    
                    <div class="row g-3">
                        @foreach($tools as $key => $tool)
                        <div class="col-md-6">
                            <div class="card h-100 {{ in_array($key, $allowedTools) ? 'border-' . $tool['color'] : '' }}">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="allowed_tools[]" 
                                               value="{{ $key }}" 
                                               id="tool_{{ $key }}"
                                               {{ in_array($key, $allowedTools) ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="tool_{{ $key }}">
                                            <div class="d-flex align-items-start">
                                                <i class="{{ $tool['icon'] }} text-{{ $tool['color'] }} me-3 mt-1"></i>
                                                <div>
                                                    <h6 class="mb-1">{{ $tool['name'] }}</h6>
                                                    <small class="text-muted">{{ $tool['description'] }}</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Configurações de Segurança -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Configurações de Segurança
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="log_interactions" checked disabled>
                                <label class="form-check-label" for="log_interactions">
                                    Registrar todas as interações
                                </label>
                                <small class="text-muted d-block">Obrigatório para auditoria e compliance</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="company_isolation" checked disabled>
                                <label class="form-check-label" for="company_isolation">
                                    Isolamento automático por empresa
                                </label>
                                <small class="text-muted d-block">Garante que dados não sejam compartilhados</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="rate_limiting" checked disabled>
                                <label class="form-check-label" for="rate_limiting">
                                    Rate limiting ativo
                                </label>
                                <small class="text-muted d-block">Previne abuso do sistema</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="data_validation" checked disabled>
                                <label class="form-check-label" for="data_validation">
                                    Validação de dados sensíveis
                                </label>
                                <small class="text-muted d-block">Proteção contra vazamento de informações</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" onclick="testConfiguration()">
                    <i class="fas fa-vial me-2"></i>
                    Testar Configuração
                </button>
                
                <div>
                    <a href="{{ route('mcp.index') }}" class="btn btn-secondary me-2">
                        <i class="fas fa-times me-2"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Salvar Configurações
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <!-- Informações do Sistema -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações do Sistema
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Versão MCP:</span>
                            <span>1.0.0</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Laravel:</span>
                            <span>{{ app()->version() }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">PHP:</span>
                            <span>{{ PHP_VERSION }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Ollama URL:</span>
                            <span class="text-break">{{ config('ollama.url') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Backup e Restauração -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-download me-2"></i>
                    Backup & Restauração
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Faça backup das suas configurações ou restaure de um backup anterior.</p>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="exportConfig()">
                        <i class="fas fa-download me-2"></i>
                        Exportar Configurações
                    </button>
                    
                    <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('configFile').click()">
                        <i class="fas fa-upload me-2"></i>
                        Importar Configurações
                    </button>
                    
                    <input type="file" id="configFile" style="display: none" accept=".json" onchange="importConfig(this)">
                </div>
            </div>
        </div>
        
        <!-- Logs Recentes -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Atividade Recente
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <i class="fas fa-cog text-primary"></i>
                        <div>
                            <small class="text-muted">Há 2 horas</small>
                            <div>Configurações atualizadas</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <i class="fas fa-comments text-success"></i>
                        <div>
                            <small class="text-muted">Há 5 horas</small>
                            <div>15 novas interações registradas</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <i class="fas fa-robot text-info"></i>
                        <div>
                            <small class="text-muted">Ontem</small>
                            <div>Modelo Llama3 atualizado</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.timeline {
    position: relative;
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-left: 2rem;
    position: relative;
}

.timeline-item i {
    position: absolute;
    left: 0;
    top: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 9px;
    top: 20px;
    width: 2px;
    height: calc(100% + 1rem);
    background: #e2e8f0;
}
</style>
@endpush

@push('scripts')
<script>
async function testConfiguration() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testando...';
    btn.disabled = true;
    
    try {
        // Testa a conexão com Ollama
        const response = await window.AppHelper.request('GET', '/api/health/ollama');
        const data = await response.json();
        
        if (data.ollama_status === 'online') {
            alert('✅ Configuração válida!\n\n' +
                  'Ollama: Online\n' +
                  `Modelos disponíveis: ${data.models_available.length}\n` +
                  'Sistema funcionando corretamente.');
        } else {
            alert('⚠️ Problemas encontrados:\n\n' +
                  'Ollama: Offline\n' +
                  'Verifique se o Docker está rodando e o Ollama foi inicializado.');
        }
    } catch (error) {
        alert('❌ Erro ao testar configuração:\n' + error.message);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

function exportConfig() {
    const config = {
        ai_model: document.getElementById('ai_model').value,
        max_context_length: document.getElementById('max_context_length').value,
        custom_instructions: document.getElementById('custom_instructions').value,
        allowed_tools: Array.from(document.querySelectorAll('input[name="allowed_tools[]"]:checked')).map(cb => cb.value),
        exported_at: new Date().toISOString(),
        version: '1.0.0'
    };
    
    const blob = new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `mcp-config-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function importConfig(input) {
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const config = JSON.parse(e.target.result);
            
            if (config.ai_model) document.getElementById('ai_model').value = config.ai_model;
            if (config.max_context_length) document.getElementById('max_context_length').value = config.max_context_length;
            if (config.custom_instructions) document.getElementById('custom_instructions').value = config.custom_instructions;
            
            // Desmarcar todos os checkboxes
            document.querySelectorAll('input[name="allowed_tools[]"]').forEach(cb => cb.checked = false);
            
            // Marcar os tools importados
            if (config.allowed_tools) {
                config.allowed_tools.forEach(tool => {
                    const checkbox = document.querySelector(`input[value="${tool}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }
            
            alert('✅ Configurações importadas com sucesso!\nLembre-se de salvar as alterações.');
        } catch (error) {
            alert('❌ Erro ao importar configurações:\n' + error.message);
        }
    };
    reader.readAsText(file);
}

// Character counter for custom instructions
document.getElementById('custom_instructions').addEventListener('input', function() {
    const maxLength = 1000;
    const currentLength = this.value.length;
    const remaining = maxLength - currentLength;
    
    let counter = document.getElementById('char-counter');
    if (!counter) {
        counter = document.createElement('small');
        counter.id = 'char-counter';
        counter.className = 'text-muted';
        this.parentNode.appendChild(counter);
    }
    
    counter.textContent = `${currentLength}/${maxLength} caracteres`;
    counter.className = remaining < 100 ? 'text-warning' : remaining < 50 ? 'text-danger' : 'text-muted';
});
</script>
@endpush
