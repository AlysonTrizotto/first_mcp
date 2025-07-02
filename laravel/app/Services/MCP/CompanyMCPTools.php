<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\DB;

class CompanyMCPTools
{
    protected $companyId;
    
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }
    
    public function getAvailableTools(): array
    {
        return [
            'get_users' => $this->getUsersTool(),
            'get_products' => $this->getProductsTool(),
            'get_orders' => $this->getOrdersTool(),
            'create_customer' => $this->getCreateCustomerTool(),
            'get_analytics' => $this->getAnalyticsTool(),
        ];
    }
    
    protected function getUsersTool(): array
    {
        return [
            'name' => 'get_users',
            'description' => 'Busca usuários da empresa',
            'parameters' => [
                'limit' => 'int|nullable|min:1|max:100',
                'search' => 'string|nullable|max:255'
            ],
            'execute' => function($params) {
                $query = DB::table('users')
                    ->where('company_id', $this->companyId)
                    ->select('id', 'name', 'email', 'created_at');
                
                if (!empty($params['search'])) {
                    $query->where('name', 'like', "%{$params['search']}%");
                }
                
                return $query->limit($params['limit'] ?? 10)->get();
            }
        ];
    }
    
    protected function getAnalyticsTool(): array
    {
        return [
            'name' => 'get_analytics',
            'description' => 'Obtém analytics da empresa',
            'parameters' => [
                'period' => 'string|in:today,week,month,year',
                'metric' => 'string|in:users,orders,revenue,products'
            ],
            'execute' => function($params) {
                return $this->getCompanyAnalytics($params['period'], $params['metric']);
            }
        ];
    }
    
    protected function getCompanyAnalytics(string $period, string $metric): array
    {
        $dateFilter = $this->getPeriodFilter($period);
        
        switch($metric) {
            case 'users':
                return [
                    'total' => DB::table('users')
                        ->where('company_id', $this->companyId)
                        ->where('created_at', '>=', $dateFilter)
                        ->count(),
                    'period' => $period
                ];
                
            case 'orders':
                return [
                    'total' => DB::table('orders')
                        ->where('company_id', $this->companyId)
                        ->where('created_at', '>=', $dateFilter)
                        ->count(),
                    'revenue' => DB::table('orders')
                        ->where('company_id', $this->companyId)
                        ->where('created_at', '>=', $dateFilter)
                        ->sum('total'),
                    'period' => $period
                ];
                
            case 'products':
                return [
                    'total' => DB::table('products')
                        ->where('company_id', $this->companyId)
                        ->count(),
                    'active' => DB::table('products')
                        ->where('company_id', $this->companyId)
                        ->where('active', true)
                        ->count(),
                    'period' => $period
                ];
                
            default:
                return ['error' => 'Métrica não encontrada'];
        }
    }
    
    protected function getProductsTool(): array
    {
        return [
            'name' => 'get_products',
            'description' => 'Lista produtos da empresa',
            'parameters' => [
                'limit' => 'int|nullable|min:1|max:100',
                'category' => 'string|nullable|max:255',
                'active_only' => 'boolean|nullable'
            ],
            'execute' => function($params) {
                $query = DB::table('products')
                    ->where('company_id', $this->companyId)
                    ->select('id', 'name', 'category', 'price', 'active');
                
                if (!empty($params['category'])) {
                    $query->where('category', $params['category']);
                }
                
                if (isset($params['active_only']) && $params['active_only']) {
                    $query->where('active', true);
                }
                
                return $query->limit($params['limit'] ?? 10)->get();
            }
        ];
    }
    
    protected function getOrdersTool(): array
    {
        return [
            'name' => 'get_orders',
            'description' => 'Lista pedidos da empresa',
            'parameters' => [
                'limit' => 'int|nullable|min:1|max:100',
                'status' => 'string|nullable|in:pending,processing,completed,cancelled'
            ],
            'execute' => function($params) {
                $query = DB::table('orders')
                    ->where('company_id', $this->companyId)
                    ->select('id', 'customer_id', 'status', 'total', 'created_at');
                
                if (!empty($params['status'])) {
                    $query->where('status', $params['status']);
                }
                
                return $query->limit($params['limit'] ?? 10)->get();
            }
        ];
    }
    
    protected function getCreateCustomerTool(): array
    {
        return [
            'name' => 'create_customer',
            'description' => 'Cria um novo cliente para a empresa',
            'parameters' => [
                'name' => 'string|required|max:255',
                'email' => 'email|required|max:255',
                'phone' => 'string|nullable|max:20'
            ],
            'execute' => function($params) {
                return DB::table('customers')->insertGetId([
                    'company_id' => $this->companyId,
                    'name' => $params['name'],
                    'email' => $params['email'],
                    'phone' => $params['phone'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        ];
    }
    
    protected function getPeriodFilter(string $period): string
    {
        switch($period) {
            case 'today':
                return now()->startOfDay()->toDateTimeString();
            case 'week':
                return now()->startOfWeek()->toDateTimeString();
            case 'month':
                return now()->startOfMonth()->toDateTimeString();
            case 'year':
                return now()->startOfYear()->toDateTimeString();
            default:
                return now()->startOfMonth()->toDateTimeString();
        }
    }
}