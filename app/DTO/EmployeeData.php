<?php

namespace App\DTO;

use Illuminate\Support\Carbon;

class EmployeeData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $document,
        public readonly string $city,
        public readonly string $state,
        public readonly string $start_date,
        public readonly int $user_id,
        public readonly ?Carbon $updated_at = null,
        public readonly ?UserData $user = null
    ) {
    }

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            name: trim($data['name'] ?? ''),
            email: trim($data['email'] ?? ''),
            document: preg_replace('/[^0-9]/', '', $data['document'] ?? ''),
            city: trim($data['city'] ?? ''),
            state: trim($data['state'] ?? ''),
            start_date: $data['start_date'] ?? '',
            user_id: $userId
        );
    }

    public static function fromModel(\App\Models\Employee $employee): self
    {
        return new self(
            name: $employee->name,
            email: $employee->email,
            document: $employee->document,
            city: $employee->city,
            state: $employee->state->value,
            start_date: $employee->start_date,
            updated_at: $employee->updated_at,
            user_id: $employee->user_id,
            user: $employee->user ? UserData::fromModel($employee->user) : null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'document' => $this->document,
            'city' => $this->city,
            'state' => $this->state,
            'start_date' => $this->start_date,
            'user_id' => $this->user_id,
            'user' => $this->user ? $this->user->toArray() : null
        ];
    }

    public function toModelArray(): array
    {
        $result = $this->toArray();

        unset($result['user']);

        return $result;
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

        if (empty($this->document) || !$this->isValidCpf($this->document)) {
            $errors['document'] = 'O documento (CPF) é obrigatório e deve ser válido.';
        }

        if (empty($this->city)) {
            $errors['city'] = 'A cidade é obrigatória.';
        }

        if (empty($this->state) || strlen($this->state) !== 2) {
            $errors['state'] = 'O estado é obrigatório e deve ter 2 caracteres (ex: SP).';
        }

        if (empty($this->start_date) || !$this->isValidDate($this->start_date)) {
            $errors['start_date'] = 'A data de início é obrigatória e deve estar no formato Y-m-d.';
        }

        return $errors;
    }

    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Calculate first verification digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        // Calculate second verification digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return intval($cpf[9]) === $digit1 && intval($cpf[10]) === $digit2;
    }

    private function isValidDate(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }
}
