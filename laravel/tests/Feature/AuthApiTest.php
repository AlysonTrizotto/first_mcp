<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class AuthApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function user_can_register_with_company()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@empresa.com',
            'password' => 'senha123456',
            'password_confirmation' => 'senha123456',
            'company_name' => 'Empresa Teste LTDA',
            'company_cnpj' => '12.345.678/0001-90',
            'company_phone' => '(11) 9999-9999',
            'company_address' => 'Rua Teste, 123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'company_id', 'is_admin'],
                'company' => ['id', 'name'],
                'token',
                'token_type'
            ]
        ]);

        // Verificar se usuário foi criado
        $this->assertDatabaseHas('users', [
            'email' => 'joao@empresa.com',
            'is_admin' => true
        ]);

        // Verificar se empresa foi criada
        $this->assertDatabaseHas('companies', [
            'name' => 'Empresa Teste LTDA',
            'cnpj' => '12.345.678/0001-90'
        ]);

        // Verificar se configuração MCP foi criada
        $user = User::where('email', 'joao@empresa.com')->first();
        $this->assertDatabaseHas('company_mcp_configs', [
            'company_id' => $user->company_id,
            'active' => true
        ]);
    }

    /** @test */
    public function registration_validates_required_fields()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'invalid-email',
            'password' => '123', // muito curto
            // faltando campos obrigatórios
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'email',
            'password',
            'company_name'
        ]);
    }

    /** @test */
    public function registration_prevents_duplicate_emails()
    {
        // Criar usuário existente
        User::factory()->create(['email' => 'existente@empresa.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Teste',
            'email' => 'existente@empresa.com',
            'password' => 'senha123456',
            'password_confirmation' => 'senha123456',
            'company_name' => 'Nova Empresa'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $company = Company::create([
            'name' => 'Empresa Teste',
            'active' => true
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@empresa.com',
            'password' => Hash::make('senha123456'),
            'company_id' => $company->id,
            'is_admin' => true
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@empresa.com',
            'password' => 'senha123456'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'company_id', 'is_admin'],
                'company' => ['id', 'name'],
                'token',
                'token_type'
            ]
        ]);

        $response->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'email' => 'test@empresa.com',
                    'is_admin' => true
                ]
            ]
        ]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'inexistente@empresa.com',
            'password' => 'senhaerrada'
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Credenciais inválidas'
        ]);
    }

    /** @test */
    public function login_fails_when_company_is_inactive()
    {
        $company = Company::create([
            'name' => 'Empresa Inativa',
            'active' => false
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@empresainativa.com',
            'password' => Hash::make('senha123456'),
            'company_id' => $company->id
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@empresainativa.com',
            'password' => 'senha123456'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Empresa inativa ou inexistente'
        ]);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logout realizado com sucesso'
        ]);

        // Verificar se token foi revogado
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class
        ]);
    }

    /** @test */
    public function admin_can_list_company_users()
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'is_admin' => true
        ]);
        
        // Criar outros usuários da mesma empresa
        User::factory()->count(3)->create([
            'company_id' => $company->id,
            'is_admin' => false
        ]);

        $token = $admin->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/company/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'email', 'is_admin', 'created_at']
            ]
        ]);

        // Deve retornar 4 usuários (admin + 3 criados)
        $this->assertCount(4, $response->json('data'));
    }

    /** @test */
    public function non_admin_cannot_list_company_users()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'is_admin' => false
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/company/users');

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Acesso negado. Apenas administradores podem visualizar usuários.'
        ]);
    }

    /** @test */
    public function admin_can_create_company_user()
    {
        $company = Company::factory()->create();
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'is_admin' => true
        ]);

        $token = $admin->createToken('test_token')->plainTextToken;

        $userData = [
            'name' => 'Novo Usuário',
            'email' => 'novo@empresa.com',
            'password' => 'senha123456',
            'is_admin' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/company/users', $userData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'email', 'company_id', 'is_admin']
        ]);

        // Verificar se usuário foi criado na empresa correta
        $this->assertDatabaseHas('users', [
            'email' => 'novo@empresa.com',
            'company_id' => $company->id,
            'is_admin' => false
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_company_user()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'is_admin' => false
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/company/users', [
            'name' => 'Novo Usuário',
            'email' => 'novo@empresa.com',
            'password' => 'senha123456'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Acesso negado. Apenas administradores podem criar usuários.'
        ]);
    }
}
