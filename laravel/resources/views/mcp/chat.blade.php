@extends('layouts.mcp')

@section('title', 'Chat IA')
@section('page-title', 'Chat com IA')

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Chat Interface -->
        <div class="card chat-container">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-robot me-2"></i>
                    Assistente IA
                </h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="clearChat()">
                    <i class="fas fa-trash me-1"></i>
                    Limpar
                </button>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message ai">
                    <div class="avatar ai">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        Olá! Sou seu assistente IA personalizado para sua empresa. Como posso ajudá-lo hoje?
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <form id="chatForm" class="d-flex gap-2">
                    <input type="text" 
                           id="messageInput" 
                           class="form-control" 
                           placeholder="Digite sua mensagem..." 
                           maxlength="2000"
                           autocomplete="off">
                    <button type="submit" id="sendButton" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Máximo 2000 caracteres. Powered by {{ config('ollama.model') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Chat Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações da Sessão
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Usuário:</span>
                            <span>{{ auth()->user()->name }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Empresa:</span>
                            <span>{{ auth()->user()->company->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Modelo:</span>
                            <span>{{ config('ollama.model') }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Mensagens:</span>
                            <span id="messageCount">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tools Available -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Ferramentas Disponíveis
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-primary">get_users</span>
                    <span class="badge bg-success">get_analytics</span>
                    <span class="badge bg-info">get_products</span>
                    <span class="badge bg-warning">create_customer</span>
                    <span class="badge bg-secondary">company_reports</span>
                </div>
                <hr>
                <small class="text-muted">
                    <strong>Dica:</strong> Você pode pedir coisas como:
                    <ul class="mt-2 mb-0">
                        <li>"Mostre os usuários ativos"</li>
                        <li>"Quais são as vendas do mês?"</li>
                        <li>"Liste os produtos em estoque"</li>
                        <li>"Crie um relatório de performance"</li>
                    </ul>
                </small>
            </div>
        </div>
        
        <!-- Recent Messages -->
        @if($recentMessages->count() > 0)
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Conversas Recentes
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach($recentMessages->take(5) as $message)
                    <div class="list-group-item border-0 px-0 py-2">
                        <div class="small">
                            <strong>Você:</strong> {{ Str::limit($message->message, 50) }}
                        </div>
                        <div class="small text-muted">
                            {{ \Carbon\Carbon::parse($message->created_at)->diffForHumans() }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
let messageCount = 0;

document.getElementById('chatForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await sendMessage();
});

document.getElementById('messageInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

async function sendMessage() {
    const input = document.getElementById('messageInput');
    const button = document.getElementById('sendButton');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Disable input
    input.disabled = true;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Add user message to chat
    addMessage('user', message);
    input.value = '';
    
    // Show typing indicator
    showTyping(true);
    
    try {
        const response = await fetch('/api/mcp/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer {{ auth()->user()->createToken('web')->plainTextToken ?? '' }}`,
                'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify({
                message: message,
                context: {
                    timestamp: new Date().toISOString()
                }
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const aiResponse = data.response?.response || data.response || 'Resposta recebida com sucesso.';
            addMessage('ai', aiResponse);
        } else {
            addMessage('ai', 'Desculpe, ocorreu um erro: ' + (data.message || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro:', error);
        addMessage('ai', 'Desculpe, não foi possível processar sua mensagem. Verifique se o Ollama está rodando.');
    } finally {
        // Re-enable input
        input.disabled = false;
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-paper-plane"></i>';
        showTyping(false);
        input.focus();
    }
}

function addMessage(type, content) {
    const messagesContainer = document.getElementById('chatMessages');
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    
    const avatar = type === 'user' 
        ? `<div class="avatar user">${window.user.name.charAt(0).toUpperCase()}</div>`
        : `<div class="avatar ai"><i class="fas fa-robot"></i></div>`;
    
    messageDiv.innerHTML = `
        ${avatar}
        <div class="message-content">${content}</div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Update message count
    messageCount++;
    document.getElementById('messageCount').textContent = messageCount;
}

function showTyping(show) {
    const messagesContainer = document.getElementById('chatMessages');
    const existingTyping = document.getElementById('typingIndicator');
    
    if (show) {
        if (!existingTyping) {
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typingIndicator';
            typingDiv.className = 'typing-indicator';
            typingDiv.innerHTML = `
                <div class="avatar ai"><i class="fas fa-robot"></i></div>
                <div>
                    IA está digitando
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            `;
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    } else {
        if (existingTyping) {
            existingTyping.remove();
        }
    }
}

function clearChat() {
    if (confirm('Tem certeza que deseja limpar o chat?')) {
        const messagesContainer = document.getElementById('chatMessages');
        messagesContainer.innerHTML = `
            <div class="message ai">
                <div class="avatar ai">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    Chat limpo! Como posso ajudá-lo?
                </div>
            </div>
        `;
        messageCount = 0;
        document.getElementById('messageCount').textContent = messageCount;
    }
}

// Focus on input when page loads
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('messageInput').focus();
});
</script>
@endpush
