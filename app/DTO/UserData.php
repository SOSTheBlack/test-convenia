<?php

namespace App\DTO;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password = null,
        public readonly ?\DateTime $email_verified_at = null
    ) {
    }

    /**
     * Create UserData from array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $emailVerifiedAt = isset($data['email_verified_at'])
            ? new \DateTime($data['email_verified_at'])
            : null;

        return new self(
            name: trim($data['name'] ?? ''),
            email: trim($data['email'] ?? ''),
            password: $data['password'] ?? null,
            email_verified_at: $emailVerifiedAt
        );
    }

    /**
     * Create UserData from User model
     *
     * @param User $user
     * @return self
     */
    public static function fromModel(User $user): self
    {
        $emailVerifiedAt = $user->email_verified_at
            ? new \DateTime($user->email_verified_at)
            : null;

        return new self(
            name: $user->name,
            email: $user->email,
            email_verified_at: $emailVerifiedAt
        );
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        if ($this->email_verified_at !== null) {
            $data['email_verified_at'] = $this->email_verified_at->format('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Convert to array for database
     *
     * @return array
     */
    public function toDatabase(): array
    {
        $data = $this->toArray();

        // Hash password if it exists
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    /**
     * Validate user data
     *
     * @return array Validation errors
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name) || strlen($this->name) < 2) {
            $errors['name'] = 'O nome é obrigatório e deve ter pelo menos 2 caracteres.';
        }

        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'O e-mail é obrigatório e deve ser válido.';
        }

        // Validate password only if it is being set/updated
        if ($this->password !== null) {
            if (strlen($this->password) < 8) {
                $errors['password'] = 'A senha deve ter pelo menos 8 caracteres.';
            }
        }

        return $errors;
    }
}
