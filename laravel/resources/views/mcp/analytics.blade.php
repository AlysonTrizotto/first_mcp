@extends('layouts.mcp')

@section('title', 'Analytics')
@section('page-title', 'Analytics e Relatórios')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Interações por Dia (Últimos 30 dias)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="interactionsChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>
                    Top Usuários (30 dias)
                </h5>
            </div>
            <div class="card-body">
                @if($topUsers->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($topUsers as $index => $user)
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                        <div class="d-flex align-items-center">
                            <div class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $user->name }}</div>
                                <small class="text-muted">{{ $user->interaction_count }} interações</small>
                            </div>
                        </div>
                        <div class="progress" style="width: 100px; height: 8px;">
                            <div class="progress-bar" 
                                 style="width: {{ ($user->interaction_count / $topUsers->first()->interaction_count) * 100 }}%">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">Nenhum dado encontrado</h6>
                    <p class="text-muted mb-0">Faça algumas interações para ver os analytics</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Horários de Maior Uso
                </h5>
            </div>
            <div class="card-body">
                <canvas id="hourlyChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                <h3 class="text-primary">{{ $interactionsByDay->sum('count') }}</h3>
                <p class="text-muted mb-0">Total de Interações (30 dias)</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-calendar-day fa-3x text-success mb-3"></i>
                <h3 class="text-success">{{ number_format($interactionsByDay->avg('count'), 1) }}</h3>
                <p class="text-muted mb-0">Média por Dia</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-user-friends fa-3x text-warning mb-3"></i>
                <h3 class="text-warning">{{ $topUsers->count() }}</h3>
                <p class="text-muted mb-0">Usuários Únicos</p>
            </div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-download me-2"></i>
                    Exportar Dados
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-outline-primary w-100" onclick="exportData('csv')">
                            <i class="fas fa-file-csv me-2"></i>
                            Exportar CSV
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-success w-100" onclick="exportData('excel')">
                            <i class="fas fa-file-excel me-2"></i>
                            Exportar Excel
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-danger w-100" onclick="exportData('pdf')">
                            <i class="fas fa-file-pdf me-2"></i>
                            Relatório PDF
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-info w-100" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>
                            Imprimir
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
// Dados do backend
const interactionsData = @json($interactionsByDay);
const topUsersData = @json($topUsers);

// Gráfico de Interações por Dia
const ctx1 = document.getElementById('interactionsChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: interactionsData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
        }),
        datasets: [{
            label: 'Interações',
            data: interactionsData.map(item => item.count),
            borderColor: 'rgb(37, 99, 235)',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfico de Horários (simulado)
const hourlyData = generateHourlyData();
const ctx2 = document.getElementById('hourlyChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: ['00', '04', '08', '12', '16', '20'],
        datasets: [{
            label: 'Interações por Horário',
            data: hourlyData,
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(6, 182, 212, 0.8)'
            ],
            borderWidth: 0,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

function generateHourlyData() {
    // Simula dados de horário baseado nos dados reais
    const total = interactionsData.reduce((sum, item) => sum + item.count, 0);
    return [
        Math.floor(total * 0.05), // 00-04h
        Math.floor(total * 0.10), // 04-08h
        Math.floor(total * 0.25), // 08-12h
        Math.floor(total * 0.35), // 12-16h
        Math.floor(total * 0.20), // 16-20h
        Math.floor(total * 0.05)  // 20-24h
    ];
}

function exportData(format) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exportando...';
    btn.disabled = true;
    
    // Simular export (aqui você implementaria a funcionalidade real)
    setTimeout(() => {
        alert(`Funcionalidade de export ${format.toUpperCase()} será implementada em breve!`);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 2000);
}

// Print styles
const printStyles = `
    @media print {
        .sidebar, .navbar, .card-header { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; }
        .card { border: 1px solid #ddd !important; margin-bottom: 20px; }
        body { font-size: 12px; }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = printStyles;
document.head.appendChild(styleSheet);
</script>
@endpush
