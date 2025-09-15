<?php

namespace Tests\Unit;

use App\DTO\EmployeeData;
use Tests\TestCase;

class EmployeeDataTest extends TestCase
{
    public function test_from_array_creates_dto_correctly()
    {
        $data = [
            'name' => '  John Doe  ',
            'email' => '  john@example.com  ',
            'document' => '123.456.789-01',
            'city' => '  São Paulo  ',
            'state' => '  SP  ',
            'start_date' => '2024-01-01',
        ];

        $dto = EmployeeData::fromArray($data, 1);

        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals('12345678901', $dto->document);
        $this->assertEquals('São Paulo', $dto->city);
        $this->assertEquals('SP', $dto->state);
        $this->assertEquals('2024-01-01', $dto->start_date);
        $this->assertEquals(1, $dto->user_id);
    }

    public function test_to_array_returns_correct_array()
    {
        $dto = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678901',
            city: 'São Paulo',
            state: 'SP',
            start_date: '2024-01-01',
            user_id: 1
        );

        $array = $dto->toArray();

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'document' => '12345678901',
            'city' => 'São Paulo',
            'state' => 'SP',
            'start_date' => '2024-01-01',
            'user_id' => 1,
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_validate_returns_empty_array_for_valid_data()
    {
        $dto = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '11144477735', // Valid CPF
            city: 'São Paulo',
            state: 'SP',
            start_date: '2024-01-01',
            user_id: 1
        );

        $errors = $dto->validate();

        $this->assertEmpty($errors);
    }

    public function test_validate_returns_errors_for_invalid_name()
    {
        $dto = new EmployeeData(
            name: 'A', // Too short
            email: 'john@example.com',
            document: '11144477735',
            city: 'São Paulo',
            state: 'SP',
            start_date: '2024-01-01',
            user_id: 1
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('O nome é obrigatório e deve ter pelo menos 2 caracteres.', $errors['name']);
    }

    public function test_validate_returns_errors_for_invalid_email()
    {
        $dto = new EmployeeData(
            name: 'John Doe',
            email: 'invalid-email',
            document: '11144477735',
            city: 'São Paulo',
            state: 'SP',
            start_date: '2024-01-01',
            user_id: 1
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('O e-mail é obrigatório e deve ser válido.', $errors['email']);
    }

    public function test_validate_returns_errors_for_invalid_cpf()
    {
        $dto = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '12345678901', // Invalid CPF
            city: 'São Paulo',
            state: 'SP',
            start_date: '2024-01-01',
            user_id: 1
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('document', $errors);
        $this->assertEquals('O documento (CPF) é obrigatório e deve ser válido.', $errors['document']);
    }

    public function test_validate_returns_errors_for_invalid_state()
    {
        $dto = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '11144477735',
            city: 'São Paulo',
            state: 'São Paulo', // Should be 2 characters
            start_date: '2024-01-01',
            user_id: 1
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('state', $errors);
        $this->assertEquals('O estado é obrigatório e deve ter 2 caracteres (ex: SP).', $errors['state']);
    }

    public function test_validate_returns_errors_for_invalid_date()
    {
        $dto = new EmployeeData(
            name: 'John Doe',
            email: 'john@example.com',
            document: '11144477735',
            city: 'São Paulo',
            state: 'SP',
            start_date: '2024-13-01', // Invalid date
            user_id: 1
        );

        $errors = $dto->validate();

        $this->assertArrayHasKey('start_date', $errors);
        $this->assertEquals('A data de início é obrigatória e deve estar no formato Y-m-d.', $errors['start_date']);
    }

    public function test_cpf_validation_with_valid_cpfs()
    {
        $validCpfs = [
            '11144477735',
            '12345678909',
            '98765432100'
        ];

        foreach ($validCpfs as $cpf) {
            $dto = new EmployeeData(
                name: 'John Doe',
                email: 'john@example.com',
                document: $cpf,
                city: 'São Paulo',
                state: 'SP',
                start_date: '2024-01-01',
                user_id: 1
            );

            $errors = $dto->validate();
            $this->assertArrayNotHasKey('document', $errors, "CPF {$cpf} should be valid");
        }
    }

    public function test_cpf_validation_with_invalid_cpfs()
    {
        $invalidCpfs = [
            '11111111111', // Same digits
            '12345678901', // Invalid checksum
            '123456789',   // Too short
            '123456789012', // Too long
        ];

        foreach ($invalidCpfs as $cpf) {
            $dto = new EmployeeData(
                name: 'John Doe',
                email: 'john@example.com',
                document: $cpf,
                city: 'São Paulo',
                state: 'SP',
                start_date: '2024-01-01',
                user_id: 1
            );

            $errors = $dto->validate();
            $this->assertArrayHasKey('document', $errors, "CPF {$cpf} should be invalid");
        }
    }
}