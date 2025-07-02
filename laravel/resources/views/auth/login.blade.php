<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #1d4ed8, #1e3a8a);
        }
        
        .feature-list {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-item i {
            color: #10b981;
            margin-right: 0.5rem;
            width: 16px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="mb-3">
                <i class="fas fa-robot fa-3x"></i>
            </div>
            <h3 class="mb-2">MCP Dashboard</h3>
            <p class="mb-0 opacity-75">Sistema de IA Conversacional</p>
        </div>
        
        <div class="login-body">
            @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ $errors->first() }}
            </div>
            @endif
            
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>
                        E-mail
                    </label>
                    <input type="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autocomplete="email"
                           placeholder="seu@email.com">
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>
                        Senha
                    </label>
                    <input type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           placeholder="Sua senha">
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Entrar no Sistema
                    </button>
                </div>
            </form>
            
            <!-- Features -->
            <div class="feature-list">
                <h6 class="mb-3">
                    <i class="fas fa-star me-2"></i>
                    Recursos Disponíveis:
                </h6>
                
                <div class="feature-item">
                    <i class="fas fa-check"></i>
                    <small>Chat inteligente com IA</small>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-check"></i>
                    <small>Analytics em tempo real</small>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-check"></i>
                    <small>Isolamento multi-tenant</small>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-check"></i>
                    <small>Configurações personalizáveis</small>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center py-3 border-top">
            <small class="text-muted">
                <i class="fas fa-code me-1"></i>
                Powered by Ollama + Laravel MCP
            </small>
        </div>
    </div>
    
    <!-- Demo Data -->
    <div class="position-fixed bottom-0 start-0 p-3">
        <div class="card" style="max-width: 300px;">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Dados de Teste
                </h6>
                <p class="card-text small mb-2">Para testar o sistema:</p>
                <div class="small">
                    <strong>Email:</strong> admin@empresa.com<br>
                    <strong>Senha:</strong> password123
                </div>
                <hr class="my-2">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Sistema seguro com autenticação
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Focus no primeiro campo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
        
        // Quick fill para demo
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                document.getElementById('email').value = 'admin@empresa.com';
                document.getElementById('password').value = 'password123';
            }
        });
    </script>
</body>
</html>
