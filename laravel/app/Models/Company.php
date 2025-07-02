<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cnpj',
        'phone',
        'address',
        'active',
        'settings',
    ];

    protected $casts = [
        'active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Usuários da empresa
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Usuários administradores da empresa
     */
    public function admins()
    {
        return $this->hasMany(User::class)->where('is_admin', true);
    }

    /**
     * Configurações MCP da empresa
     */
    public function mcpConfig()
    {
        return $this->hasOne(CompanyMcpConfig::class);
    }

    /**
     * Interações MCP da empresa
     */
    public function mcpInteractions()
    {
        return $this->hasMany(McpInteraction::class);
    }

    /**
     * Scope para empresas ativas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Formatar CNPJ
     */
    public function getFormattedCnpjAttribute()
    {
        if (!$this->cnpj) return null;
        
        $cnpj = preg_replace('/\D/', '', $this->cnpj);
        
        if (strlen($cnpj) === 14) {
            return substr($cnpj, 0, 2) . '.' . 
                   substr($cnpj, 2, 3) . '.' . 
                   substr($cnpj, 5, 3) . '/' . 
                   substr($cnpj, 8, 4) . '-' . 
                   substr($cnpj, 12, 2);
        }
        
        return $this->cnpj;
    }
}
