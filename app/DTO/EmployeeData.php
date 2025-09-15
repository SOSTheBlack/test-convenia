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
        public readonly ?UserData $user = null,
        public ?bool $send_notification = false,
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
            user_id: $userId,
            send_notification: $data['send_notification'] ?? false
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
            send_notification: $employee->send_notification,
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
            'send_notification' => $this->send_notification,
            'user' => $this->user ? $this->user->toArray() : null
        ];
    }

    public function setSendNotification(bool $send): self
    {
        $this->send_notification = $send;

        return $this;
    }

    public function toModelArray(): array
    {
        $result = $this->toArray();

        unset($result['user']);

        return $result;
    }
}
