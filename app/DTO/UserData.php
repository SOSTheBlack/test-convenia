<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

readonly class UserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?Carbon $emailVerifiedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $emailVerifiedAt = isset($data['email_verified_at'])
            ? Carbon::parse($data['email_verified_at'])
            : null;

        return new self(
            name: trim($data['name'] ?? ''),
            email: trim($data['email'] ?? ''),
            password: $data['password'] ?? null,
            emailVerifiedAt: $emailVerifiedAt
        );
    }

    public static function fromModel(User $user): self
    {
        return new self(
            name: $user->name,
            email: $user->email,
            emailVerifiedAt: $user->email_verified_at
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        if ($this->emailVerifiedAt !== null) {
            $data['email_verified_at'] = $this->emailVerifiedAt->format('Y-m-d H:i:s');
        }

        return $data;
    }

    public function toDatabase(): array
    {
        $data = $this->toArray();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->name) || strlen($this->name) < 2) {
            $errors['name'] = 'O nome é obrigatório e deve ter pelo menos 2 caracteres.';
        }

        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'O e-mail é obrigatório e deve ser válido.';
        }

        if ($this->password !== null && strlen($this->password) < 8) {
            $errors['password'] = 'A senha deve ter pelo menos 8 caracteres.';
        }

        return $errors;
    }
}
