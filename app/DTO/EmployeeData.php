<?php

declare(strict_types=1);

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
        public readonly string $startDate,
        public readonly int $userId,
        public readonly ?Carbon $updatedAt = null,
        public readonly ?UserData $user = null,
        public readonly bool $sendNotification = false,
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
            startDate: $data['start_date'] ?? '',
            userId: $userId,
            sendNotification: $data['send_notification'] ?? false
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
            startDate: $employee->start_date->format('Y-m-d'),
            updatedAt: $employee->updated_at,
            userId: $employee->user_id,
            sendNotification: $employee->send_notification,
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
            'start_date' => $this->startDate,
            'user_id' => $this->userId,
            'send_notification' => $this->sendNotification,
            'user' => $this->user?->toArray()
        ];
    }

    public function withSendNotification(bool $send): self
    {
        return new self(
            name: $this->name,
            email: $this->email,
            document: $this->document,
            city: $this->city,
            state: $this->state,
            startDate: $this->startDate,
            userId: $this->userId,
            updatedAt: $this->updatedAt,
            user: $this->user,
            sendNotification: $send
        );
    }

    public function toModelArray(): array
    {
        $result = $this->toArray();
        unset($result['user']);
        return $result;
    }
}
