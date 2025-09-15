<?php

namespace Tests\Unit\DTO;

use App\DTO\EmployeeData;
use App\DTO\UserData;
use App\Enums\BrazilianState;
use App\Models\Employee;
use App\Models\User;
use Tests\TestCase;

class EmployeeDataTest extends TestCase
{
    /** @test */
    public function it_creates_dto_with_valid_data()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '123.456.789-00',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2023-01-15',
            'send_notification' => true
        ];
        $userId = 1;

        $employeeData = EmployeeData::fromArray($data, $userId);

        $this->assertEquals('John Doe', $employeeData->name);
        $this->assertEquals('john@example.com', $employeeData->email);
        $this->assertEquals('12345678900', $employeeData->document); // Documento formatado
        $this->assertEquals('São Paulo', $employeeData->city);
        $this->assertEquals('SP', $employeeData->state);
        $this->assertEquals('2023-01-15', $employeeData->startDate);
        $this->assertEquals(1, $employeeData->userId);
        $this->assertTrue($employeeData->sendNotification);
    }

    /** @test */
    public function it_formats_document_removing_special_characters()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '123.456.789-00',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2023-01-15'
        ];
        $userId = 1;

        $employeeData = EmployeeData::fromArray($data, $userId);

        $this->assertEquals('12345678900', $employeeData->document);
    }

    /** @test */
    public function it_handles_empty_document()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2023-01-15'
        ];
        $userId = 1;

        $employeeData = EmployeeData::fromArray($data, $userId);

        $this->assertEquals('', $employeeData->document);
    }

    /** @test */
    public function it_trims_whitespace_from_fields()
    {
        $data = [
            'name' => '  John Doe  ',
            'email' => '  john@example.com  ',
            'document' => '12345678900',
            'city' => '  São Paulo  ',
            'state' => '  SP  ',
            'start_date' => '2023-01-15'
        ];
        $userId = 1;

        $employeeData = EmployeeData::fromArray($data, $userId);

        $this->assertEquals('John Doe', $employeeData->name);
        $this->assertEquals('john@example.com', $employeeData->email);
        $this->assertEquals('São Paulo', $employeeData->city);
        $this->assertEquals('SP', $employeeData->state);
    }

    /** @test */
    public function it_creates_from_employee_model()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->forUser($user)->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'document' => '98765432100',
            'city' => 'Rio de Janeiro',
            'state' => BrazilianState::RIO_DE_JANEIRO,
            'start_date' => '2022-06-15',
            'send_notification' => true
        ]);

        $employeeData = EmployeeData::fromModel($employee);

        $this->assertEquals('Jane Doe', $employeeData->name);
        $this->assertEquals('jane@example.com', $employeeData->email);
        $this->assertEquals('98765432100', $employeeData->document);
        $this->assertEquals('Rio de Janeiro', $employeeData->city);
        $this->assertEquals('RJ', $employeeData->state);
        $this->assertEquals('2022-06-15', $employeeData->startDate);
        $this->assertEquals($user->id, $employeeData->userId);
        $this->assertTrue($employeeData->sendNotification);
        $this->assertNotNull($employeeData->updatedAt);
        $this->assertInstanceOf(UserData::class, $employeeData->user);
    }

    /** @test */
    public function it_converts_to_array()
    {
        $employeeData = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678900',
            city: 'São Paulo',
            state: 'SP',
            startDate: '2023-01-15',
            userId: 1,
            sendNotification: true
        );

        $array = $employeeData->toArray();

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678900',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2023-01-15',
            'user_id' => 1,
            'send_notification' => true,
            'user' => null
        ], $array);
    }

    /** @test */
    public function it_converts_to_model_array()
    {
        $employeeData = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678900',
            city: 'São Paulo',
            state: 'SP',
            startDate: '2023-01-15',
            userId: 1,
            sendNotification: true
        );

        $modelArray = $employeeData->toModelArray();

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678900',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2023-01-15',
            'user_id' => 1,
            'send_notification' => true
        ], $modelArray);

        // Verifica que 'user' foi removido
        $this->assertArrayNotHasKey('user', $modelArray);
    }

    /** @test */
    public function it_creates_with_send_notification_flag()
    {
        $employeeData = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678900',
            city: 'São Paulo',
            state: 'SP',
            startDate: '2023-01-15',
            userId: 1,
            sendNotification: false
        );

        $withNotification = $employeeData->withSendNotification(true);
        $withoutNotification = $employeeData->withSendNotification(false);

        // Original não deve mudar
        $this->assertFalse($employeeData->sendNotification);

        // Novos objetos devem ter o valor correto
        $this->assertTrue($withNotification->sendNotification);
        $this->assertFalse($withoutNotification->sendNotification);

        // Outros valores devem permanecer iguais
        $this->assertEquals($employeeData->name, $withNotification->name);
        $this->assertEquals($employeeData->email, $withNotification->email);
    }

    /** @test */
    public function it_handles_missing_optional_fields()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678900'
            // missing city, state, start_date, send_notification
        ];
        $userId = 1;

        $employeeData = EmployeeData::fromArray($data, $userId);

        $this->assertEquals('John Doe', $employeeData->name);
        $this->assertEquals('john@example.com', $employeeData->email);
        $this->assertEquals('12345678900', $employeeData->document);
        $this->assertEquals('', $employeeData->city);
        $this->assertEquals('', $employeeData->state);
        $this->assertEquals('', $employeeData->startDate);
        $this->assertFalse($employeeData->sendNotification);
    }
}
