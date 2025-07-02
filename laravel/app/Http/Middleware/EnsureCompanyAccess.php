<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanyAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Usuário não associado a uma empresa'], 403);
        }
        
        // Verifica se a empresa está ativa
        $company = \DB::table('companies')
            ->where('id', $user->company_id)
            ->where('active', true)
            ->first();
            
        if (!$company) {
            return response()->json(['error' => 'Empresa inativa ou inexistente'], 403);
        }
        
        return $next($request);
    }
}