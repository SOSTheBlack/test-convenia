<?php

namespace Tests\Unit\DTO;

use App\DTO\UserData;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserDataTest extends TestCase
{
    /** @test */
    public function it_creates_dto_with_valid_data()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            emailVerifiedAt: Carbon::parse('2023-01-15 10:00:00')
        );

        $this->assertEquals('John Doe', $userData->name);
        $this->assertEquals('john@example.com', $userData->email);
        $this->assertEquals('password123', $userData->password);
        $this->assertEquals('2023-01-15 10:00:00', $userData->emailVerifiedAt->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_creates_from_array()
    {
        $data = [
            'name' => '  John Doe  ',
            'email' => '  john@example.com  ',
            'password' => 'password123',
            'email_verified_at' => '2023-01-15 10:00:00'
        ];

        $userData = UserData::fromArray($data);

        $this->assertEquals('John Doe', $userData->name);
        $this->assertEquals('john@example.com', $userData->email);
        $this->assertEquals('password123', $userData->password);
        $this->assertEquals('2023-01-15 10:00:00', $userData->emailVerifiedAt->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_creates_from_array_with_missing_fields()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
            // password and email_verified_at missing
        ];

        $userData = UserData::fromArray($data);

        $this->assertEquals('John Doe', $userData->name);
        $this->assertEquals('john@example.com', $userData->email);
        $this->assertNull($userData->password);
        $this->assertNull($userData->emailVerifiedAt);
    }

    /** @test */
    public function it_creates_from_user_model()
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'email_verified_at' => Carbon::parse('2023-01-15 10:00:00')
        ]);

        $userData = UserData::fromModel($user);

        $this->assertEquals('Jane Doe', $userData->name);
        $this->assertEquals('jane@example.com', $userData->email);
        $this->assertNull($userData->password); // Password não é incluído do model
        $this->assertEquals('2023-01-15 10:00:00', $userData->emailVerifiedAt->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_converts_to_array()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            emailVerifiedAt: Carbon::parse('2023-01-15 10:00:00')
        );

        $array = $userData->toArray();

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'email_verified_at' => '2023-01-15 10:00:00'
        ], $array);
    }

    /** @test */
    public function it_converts_to_array_without_optional_fields()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: 'john@example.com'
        );

        $array = $userData->toArray();

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ], $array);

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('email_verified_at', $array);
    }

    /** @test */
    public function it_converts_to_database_array_with_hashed_password()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123'
        );

        $databaseArray = $userData->toDatabase();

        $this->assertEquals('John Doe', $databaseArray['name']);
        $this->assertEquals('john@example.com', $databaseArray['email']);
        $this->assertNotEquals('password123', $databaseArray['password']);
        $this->assertTrue(Hash::check('password123', $databaseArray['password']));
    }

    /** @test */
    public function it_validates_valid_data()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123'
        );

        $errors = $userData->validate();

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_validates_missing_name()
    {
        $userData = new UserData(
            name: '',
            email: 'john@example.com',
            password: 'password123'
        );

        $errors = $userData->validate();

        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('O nome é obrigatório e deve ter pelo menos 2 caracteres.', $errors['name']);
    }

    /** @test */
    public function it_validates_short_name()
    {
        $userData = new UserData(
            name: 'J',
            email: 'john@example.com',
            password: 'password123'
        );

        $errors = $userData->validate();

        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('O nome é obrigatório e deve ter pelo menos 2 caracteres.', $errors['name']);
    }

    /** @test */
    public function it_validates_invalid_email()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: 'invalid-email',
            password: 'password123'
        );

        $errors = $userData->validate();

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('O e-mail é obrigatório e deve ser válido.', $errors['email']);
    }

    /** @test */
    public function it_validates_missing_email()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: '',
            password: 'password123'
        );

        $errors = $userData->validate();

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('O e-mail é obrigatório e deve ser válido.', $errors['email']);
    }

    /** @test */
    public function it_validates_short_password()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: '123'
        );

        $errors = $userData->validate();

        $this->assertArrayHasKey('password', $errors);
        $this->assertEquals('A senha deve ter pelo menos 8 caracteres.', $errors['password']);
    }

    /** @test */
    public function it_validates_null_password()
    {
        $userData = new UserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: null
        );

        $errors = $userData->validate();

        $this->assertArrayNotHasKey('password', $errors);
    }

    /** @test */
    public function it_validates_multiple_errors()
    {
        $userData = new UserData(
            name: '',
            email: 'invalid-email',
            password: '123'
        );

        $errors = $userData->validate();

        $this->assertCount(3, $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }
}
