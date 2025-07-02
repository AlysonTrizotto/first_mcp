@extends('layouts.mcp')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard MCP')

@section('content')
<div class="row">
    <!-- Estatísticas -->
    <div class="col-md-4 mb-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <i class="fas fa-comments fa-2x mb-3"></i>
                <h3 class="mb-1">{{ number_format($stats['total_interactions']) }}</h3>
                <p class="mb-0 opacity-75">Total de Interações</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <i class="fas fa-calendar-day fa-2x mb-3"></i>
                <h3 class="mb-1">{{ number_format($stats['interactions_today']) }}</h3>
                <p class="mb-0 opacity-75">Interações Hoje</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x mb-3"></i>
                <h3 class="mb-1">{{ number_format($stats['active_users']) }}</h3>
                <p class="mb-0 opacity-75">Usuários Ativos (7 dias)</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Status do Sistema -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-server me-2"></i>
                    Status do Sistema
                </h5>
                <span class="status-badge {{ $ollamaStatus['status'] === 'online' ? 'status-online' : 'status-offline' }}">
                    <i class="fas fa-circle"></i>
                    {{ $ollamaStatus['status'] === 'online' ? 'Online' : 'Offline' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-robot text-primary me-2"></i>
                            <div>
                                <div class="fw-bold">Ollama Server</div>
                                <small class="text-muted">{{ config('ollama.url') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-brain text-success me-2"></i>
                            <div>
                                <div class="fw-bold">Modelo Ativo</div>
                                <small class="text-muted">{{ $companyConfig->ai_model ?? config('ollama.model') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-memory text-info me-2"></i>
                            <div>
                                <div class="fw-bold">Contexto Máximo</div>
                                <small class="text-muted">{{ $companyConfig->max_context_length ?? config('ollama.max_context') }} tokens</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt text-warning me-2"></i>
                            <div>
                                <div class="fw-bold">Rate Limit</div>
                                <small class="text-muted">{{ config('ollama.rate_limit') }}/min</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if($ollamaStatus['status'] === 'online' && isset($ollamaStatus['models']))
                <hr>
                <h6>Modelos Disponíveis:</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($ollamaStatus['models'] as $model)
                    <span class="badge bg-light text-dark">{{ $model['name'] ?? $model }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Configuração da Empresa -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    Configuração da Empresa
                </h5>
            </div>
            <div class="card-body">
                @if($companyConfig)
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Status:</span>
                            <span class="badge {{ $companyConfig->active ? 'bg-success' : 'bg-danger' }}">
                                {{ $companyConfig->active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Modelo:</span>
                            <span>{{ $companyConfig->ai_model }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Contexto:</span>
                            <span>{{ number_format($companyConfig->max_context_length) }} tokens</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Ferramentas:</span>
                            <span>{{ count(json_decode($companyConfig->allowed_tools ?? '[]', true)) }} ativas</span>
                        </div>
                    </div>
                    @if($companyConfig->custom_instructions)
                    <div class="col-12">
                        <strong>Instruções Personalizadas:</strong>
                        <p class="text-muted mb-0 mt-1">{{ Str::limit($companyConfig->custom_instructions, 100) }}</p>
                    </div>
                    @endif
                </div>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-cog fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">Nenhuma configuração encontrada</h6>
                    <a href="{{ route('mcp.settings') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>
                        Configurar Agora
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Ações Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="{{ route('mcp.chat') }}" class="btn btn-primary w-100">
                            <i class="fas fa-comments me-2"></i>
                            Iniciar Chat
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('mcp.analytics') }}" class="btn btn-info w-100">
                            <i class="fas fa-chart-bar me-2"></i>
                            Ver Analytics
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('mcp.settings') }}" class="btn btn-warning w-100">
                            <i class="fas fa-cog me-2"></i>
                            Configurações
                        </a>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" onclick="testConnection()">
                            <i class="fas fa-wifi me-2"></i>
                            Testar Conexão
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function testConnection() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('/api/health/ollama');
        const data = await response.json();
        
        if (data.ollama_status === 'online') {
            alert('✅ Conexão com Ollama estabelecida com sucesso!');
        } else {
            alert('❌ Falha na conexão com Ollama: ' + (data.error || 'Status offline'));
        }
    } catch (error) {
        alert('❌ Erro ao testar conexão: ' + error.message);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>
@endpush
