<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa listagem de funcionários com autenticação
     */
    public function test_authenticated_user_can_list_employees(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $employees = Employee::factory()->count(3)->forUser($user)->create();

        // Act
        $response = $this->getJson('/api/employees');

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'cpf',
                        'department',
                        'salary',
                        'admission_date',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Testa listagem vazia de funcionários
     */
    public function test_authenticated_user_can_list_empty_employees(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Act
        $response = $this->getJson('/api/employees');

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertCount(0, $response->json('data'));
        $this->assertEquals(0, $response->json('total'));
    }

    /**
     * Testa filtro por departamento
     */
    public function test_can_filter_employees_by_department(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        Employee::factory()->forUser($user)->create(['department' => 'TI']);
        Employee::factory()->forUser($user)->create(['department' => 'RH']);
        Employee::factory()->forUser($user)->create(['department' => 'TI']);

        // Act
        $response = $this->getJson('/api/employees?department=TI');

        // Assert
        $response->assertStatus(Response::HTTP_OK);

        $employees = $response->json('data');
        $this->assertCount(2, $employees);

        foreach ($employees as $employee) {
            $this->assertEquals('TI', $employee['department']);
        }
    }

    /**
     * Testa paginação personalizada
     */
    public function test_can_customize_pagination(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        Employee::factory()->count(20)->forUser($user)->create();

        // Act
        $response = $this->getJson('/api/employees?per_page=5');

        // Assert
        $response->assertStatus(Response::HTTP_OK);

        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(5, $response->json('per_page'));
        $this->assertEquals(20, $response->json('total'));
    }

    /**
     * Testa que usuário só vê seus próprios funcionários
     */
    public function test_user_only_sees_their_own_employees(): void
    {
        // Arrange
        $user1 = $this->authenticatedUser();
        $user2 = User::factory()->create();

        Employee::factory()->count(2)->forUser($user1)->create();
        Employee::factory()->count(3)->forUser($user2)->create();

        // Act
        $response = $this->getJson('/api/employees');

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Testa visualização de funcionário específico
     */
    public function test_authenticated_user_can_view_specific_employee(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $employee = Employee::factory()->forUser($user)->create();

        // Act
        $response = $this->getJson("/api/employees/{$employee->id}");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'cpf',
                'department',
                'salary',
                'admission_date',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'cpf' => $employee->cpf,
            ]);
    }

    /**
     * Testa que usuário não pode ver funcionário de outro usuário
     */
    public function test_user_cannot_view_employee_from_different_user(): void
    {
        // Arrange
        $user1 = $this->authenticatedUser();
        $user2 = User::factory()->create();
        $employee = Employee::factory()->forUser($user2)->create();

        // Act
        $response = $this->getJson("/api/employees/{$employee->id}");

        // Assert
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * Testa visualização de funcionário inexistente
     */
    public function test_viewing_non_existent_employee_returns_404(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Act
        $response = $this->getJson('/api/employees/999999');

        // Assert
        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Testa exclusão de funcionário
     */
    public function test_authenticated_user_can_delete_employee(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        $employee = Employee::factory()->forUser($user)->create();

        // Act
        $response = $this->deleteJson("/api/employees/{$employee->id}");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
        ]);
    }

    /**
     * Testa que usuário não pode excluir funcionário de outro usuário
     */
    public function test_user_cannot_delete_employee_from_different_user(): void
    {
        // Arrange
        $user1 = $this->authenticatedUser();
        $user2 = User::factory()->create();
        $employee = Employee::factory()->forUser($user2)->create();

        // Act
        $response = $this->deleteJson("/api/employees/{$employee->id}");

        // Assert
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
        ]);
    }

    /**
     * Testa exclusão de funcionário inexistente
     */
    public function test_deleting_non_existent_employee_returns_404(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        // Act
        $response = $this->deleteJson('/api/employees/999999');

        // Assert
        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Testa acesso às rotas sem autenticação
     */
    public function test_unauthenticated_access_to_employee_routes(): void
    {
        // Arrange
        $employee = Employee::factory()->create();

        $routes = [
            ['method' => 'GET', 'uri' => '/api/employees'],
            ['method' => 'GET', 'uri' => "/api/employees/{$employee->id}"],
            ['method' => 'DELETE', 'uri' => "/api/employees/{$employee->id}"],
        ];

        // Act & Assert
        foreach ($routes as $route) {
            $response = $this->json($route['method'], $route['uri']);
            $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Testa filtro com departamento inexistente
     */
    public function test_filter_by_non_existent_department_returns_empty(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        Employee::factory()->forUser($user)->create(['department' => 'TI']);

        // Act
        $response = $this->getJson('/api/employees?department=Vendas');

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(0, $response->json('data'));
    }

    /**
     * Testa múltiplos filtros simultâneos
     */
    public function test_multiple_filters_applied_correctly(): void
    {
        // Arrange
        $user = $this->authenticatedUser();

        Employee::factory()->forUser($user)->create(['department' => 'TI']);
        Employee::factory()->forUser($user)->create(['department' => 'RH']);

        // Act
        $response = $this->getJson('/api/employees?department=TI&per_page=10');

        // Assert
        $response->assertStatus(Response::HTTP_OK);

        $employees = $response->json('data');
        $this->assertCount(1, $employees);
        $this->assertEquals('TI', $employees[0]['department']);
        $this->assertEquals(10, $response->json('per_page'));
    }

    /**
     * Testa estrutura de dados da paginação
     */
    public function test_pagination_structure_is_correct(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        Employee::factory()->count(25)->forUser($user)->create();

        // Act
        $response = $this->getJson('/api/employees?per_page=10');

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'current_page',
                'data',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        $this->assertEquals(1, $response->json('current_page'));
        $this->assertEquals(10, $response->json('per_page'));
        $this->assertEquals(25, $response->json('total'));
        $this->assertEquals(3, $response->json('last_page'));
    }

    /**
     * Testa navegação entre páginas
     */
    public function test_pagination_navigation_works(): void
    {
        // Arrange
        $user = $this->authenticatedUser();
        Employee::factory()->count(25)->forUser($user)->create();

        // Act - Primeira página
        $firstPage = $this->getJson('/api/employees?per_page=10&page=1');

        // Act - Segunda página
        $secondPage = $this->getJson('/api/employees?per_page=10&page=2');

        // Assert
        $firstPage->assertStatus(Response::HTTP_OK);
        $secondPage->assertStatus(Response::HTTP_OK);

        $this->assertCount(10, $firstPage->json('data'));
        $this->assertCount(10, $secondPage->json('data'));

        $this->assertEquals(1, $firstPage->json('current_page'));
        $this->assertEquals(2, $secondPage->json('current_page'));

        // Verifica que são funcionários diferentes
        $firstPageIds = collect($firstPage->json('data'))->pluck('id')->toArray();
        $secondPageIds = collect($secondPage->json('data'))->pluck('id')->toArray();

        $this->assertEmpty(array_intersect($firstPageIds, $secondPageIds));
    }
}
