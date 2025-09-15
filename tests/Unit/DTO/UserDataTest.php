<?php

namespace Tests\Unit\DTO;

use App\DTO\UserData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserDataTest extends TestCase
{
    #[Test]
    public function it_creates_from_array()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'email_verified_at' => '2023-01-01 00:00:00'
        ];

        $userData = UserData::fromArray($data);

        $this->assertEquals('John Doe', $userData->name);
        $this->assertEquals('john@example.com', $userData->email);
        $this->assertEquals('password123', $userData->password);
        $this->assertInstanceOf(\DateTime::class, $userData->email_verified_at);
        $this->assertEquals('2023-01-01 00:00:00', $userData->email_verified_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_trims_input_data()
    {
        $data = [
            'name' => '  John Doe  ',
            'email' => '  john@example.com  ',
            'password' => 'password123'
        ];

        $userData = UserData::fromArray($data);

        $this->assertEquals('John Doe', $userData->name);
        $this->assertEquals('john@example.com', $userData->email);
    }

    #[Test]
    public function it_creates_from_model()
    {
        $user = $this->createMock(User::class);
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->email_verified_at = '2023-01-01 00:00:00';

        $userData = UserData::fromModel($user);

        $this->assertEquals('John Doe', $userData->name);
        $this->assertEquals('john@example.com', $userData->email);
        $this->assertNull($userData->password);
        $this->assertInstanceOf(\DateTime::class, $userData->email_verified_at);
    }

    #[Test]
    public function it_converts_to_array()
    {
        $userData = new UserData(
            'John Doe',
            'john@example.com',
            'password123',
            new \DateTime('2023-01-01 00:00:00')
        );

        $array = $userData->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('John Doe', $array['name']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertEquals('password123', $array['password']);
        $this->assertEquals('2023-01-01 00:00:00', $array['email_verified_at']);
    }

    #[Test]
    public function it_converts_to_database_array_with_hashed_password()
    {
        $userData = new UserData(
            'John Doe',
            'john@example.com',
            'password123'
        );

        $array = $userData->toDatabase();

        // Verify the password was hashed
        $this->assertNotEquals('password123', $array['password']);
        $this->assertTrue(strlen($array['password']) > 20); // Hashed passwords are longer
    }

    #[Test]
    public function it_validates_correctly()
    {
        $validData = new UserData(
            'John Doe',
            'john@example.com',
            'password123'
        );

        $this->assertEmpty($validData->validate());

        $invalidData = new UserData(
            'J',
            'invalid-email',
            'short'
        );

        $errors = $invalidData->validate();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    #[Test]
    public function it_handles_null_values_correctly()
    {
        $userData = new UserData(
            'John Doe',
            'john@example.com'
        );

        $this->assertNull($userData->password);
        $this->assertNull($userData->email_verified_at);

        $array = $userData->toArray();
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('email_verified_at', $array);
    }
}
