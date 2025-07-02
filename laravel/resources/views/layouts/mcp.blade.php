<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MCP Dashboard') - {{ config('app.name') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), #1e40af);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 12px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .main-content {
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            transition: transform 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-online {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .status-offline {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .chat-container {
            height: 600px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
        }
        
        .message {
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message.user .message-content {
            background: var(--primary-color);
            color: white;
        }
        
        .message.ai .message-content {
            background: white;
            border: 1px solid #e2e8f0;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }
        
        .avatar.user {
            background: var(--primary-color);
            color: white;
        }
        
        .avatar.ai {
            background: var(--success-color);
            color: white;
        }
        
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--secondary-color);
            font-style: italic;
            padding: 8px 16px;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dots span {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--secondary-color);
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 80%, 100% { opacity: 0.3; }
            40% { opacity: 1; }
        }
        
        .navbar-brand {
            font-weight: bold;
            background: linear-gradient(45deg, var(--primary-color), #1e40af);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <div class="p-3 text-center border-bottom border-light border-opacity-25">
                        <h5 class="text-white mb-0">
                            <i class="fas fa-robot me-2"></i>
                            MCP Dashboard
                        </h5>
                        <small class="text-white-50">{{ auth()->check() ? (auth()->user()->company->name ?? 'Empresa') : 'Sistema' }}</small>
                    </div>
                    
                    <nav class="mt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('mcp.index') ? 'active' : '' }}" 
                                   href="{{ route('mcp.index') }}">
                                    <i class="fas fa-home me-2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('mcp.chat') ? 'active' : '' }}" 
                                   href="{{ route('mcp.chat') }}">
                                    <i class="fas fa-comments me-2"></i>
                                    Chat IA
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('mcp.analytics') ? 'active' : '' }}" 
                                   href="{{ route('mcp.analytics') }}">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Analytics
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('mcp.settings') ? 'active' : '' }}" 
                                   href="{{ route('mcp.settings') }}">
                                    <i class="fas fa-cog me-2"></i>
                                    Configurações
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <!-- User Info -->
                    @auth
                    <div class="mt-auto p-3 border-top border-light border-opacity-25">
                        <div class="d-flex align-items-center text-white-50">
                            <div class="avatar user me-2">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-white">{{ auth()->user()->name }}</div>
                                <small>{{ auth()->user()->email }}</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-sign-out-alt me-1"></i>
                                    Sair
                                </button>
                            </form>
                        </div>
                    </div>
                    @endauth
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <div class="main-content">
                    <!-- Top Navigation -->
                    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                        <div class="container-fluid">
                            <h4 class="navbar-brand mb-0">@yield('page-title', 'Dashboard')</h4>
                            
                            <div class="d-flex align-items-center">
                                @if(isset($ollamaStatus))
                                <span class="status-badge {{ $ollamaStatus['status'] === 'online' ? 'status-online' : 'status-offline' }} me-3">
                                    <i class="fas fa-circle"></i>
                                    Ollama {{ $ollamaStatus['status'] === 'online' ? 'Online' : 'Offline' }}
                                </span>
                                @endif
                                
                                <span class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ now()->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </div>
                    </nav>
                    
                    <!-- Page Content -->
                    <div class="p-4">
                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
                        
                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
                        
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // CSRF Token for AJAX requests
        window.csrfToken = '{{ csrf_token() }}';
        window.apiUrl = '{{ url("/api") }}';
        window.user = @json(auth()->check() ? auth()->user() : null);
    </script>
    
    @stack('scripts')
</body>
</html>
