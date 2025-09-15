<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa login com credenciais válidas
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Arrange
        $password = 'password123';
        $user = User::factory()->withPassword($password)->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => $password,
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'access_token',
                'token_type',
            ])
            ->assertJsonFragment([
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);

        $this->assertNotEmpty($response->json('access_token'));
    }

    /**
     * Testa login com credenciais inválidas
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonFragment([
                'message' => 'Credenciais inválidas',
            ]);
    }

    /**
     * Testa login sem email
     */
    public function test_login_requires_email(): void
    {
        // Act
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Testa login sem senha
     */
    public function test_login_requires_password(): void
    {
        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Testa login com email inválido
     */
    public function test_login_requires_valid_email(): void
    {
        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Testa login com usuário inexistente
     */
    public function test_login_with_non_existent_user(): void
    {
        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonFragment([
                'message' => 'Credenciais inválidas',
            ]);
    }

    /**
     * Testa acesso a rota protegida sem token
     */
    public function test_protected_route_requires_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Testa acesso a rota protegida com token válido
     */
    public function test_protected_route_with_valid_token(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);
    }

    /**
     * Testa acesso a rota protegida com token inválido
     */
    public function test_protected_route_with_invalid_token(): void
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/user');

        // Assert
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Testa acesso a múltiplas rotas protegidas
     */
    public function test_multiple_protected_routes_with_authentication(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Act & Assert
        $routes = [
            '/api/user',
            '/api/employees',
            '/api/teste',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $response->assertStatus(Response::HTTP_OK);
        }
    }

    /**
     * Testa middleware auth:api em todas as rotas protegidas
     */
    public function test_auth_middleware_on_all_protected_routes(): void
    {
        // Arrange
        $protectedRoutes = [
            ['method' => 'GET', 'uri' => '/api/user'],
            ['method' => 'GET', 'uri' => '/api/employees'],
            ['method' => 'GET', 'uri' => '/api/teste'],
            ['method' => 'POST', 'uri' => '/api/employees'],
        ];

        // Act & Assert
        foreach ($protectedRoutes as $route) {
            $response = $this->json($route['method'], $route['uri']);

            $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Testa token expirado
     */
    public function test_expired_token_access(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Simula token expirado usando Passport
        Passport::actingAs($user);
        $token = $user->createToken('test-token');

        // Simula token expirado alterando a data
        $tokenModel = $token->token;
        $tokenModel->expires_at = now()->subDay();
        $tokenModel->save();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->accessToken,
        ])->getJson('/api/user');

        // Assert
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Testa revogação de token
     */
    public function test_revoked_token_access(): void
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        $token = $user->createToken('test-token');

        // Revoga o token
        $token->token->revoke();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->accessToken,
        ])->getJson('/api/user');

        // Assert
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Testa estrutura de resposta do endpoint /api/user
     */
    public function test_user_endpoint_response_structure(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Testa login com diferentes tipos de usuários
     */
    public function test_login_with_different_user_types(): void
    {
        // Arrange
        $users = [
            User::factory()->create(['name' => 'Admin User']),
            User::factory()->create(['name' => 'Regular User']),
            User::factory()->create(['name' => 'Test User']),
        ];

        foreach ($users as $user) {
            // Act
            $response = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'password', // Factory default
            ]);

            // Assert
            $response->assertStatus(Response::HTTP_OK)
                ->assertJsonFragment([
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ]);
        }
    }
}
