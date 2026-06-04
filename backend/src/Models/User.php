<?php

namespace UpApp\Models;

class User
{
    private string $id;
    private string $email;
    private string $passwordHash;
    private ?string $fullName;
    private bool $emailVerified;
    private ?string $verificationToken;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(
        string $id,
        string $email,
        string $passwordHash,
        ?string $fullName = null,
        bool $emailVerified = false,
        ?string $verificationToken = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->fullName = $fullName;
        $this->emailVerified = $emailVerified;
        $this->verificationToken = $verificationToken;
        $this->createdAt = $createdAt ?? date('Y-m-d\TH:i:s\Z');
        $this->updatedAt = $updatedAt ?? date('Y-m-d\TH:i:s\Z');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function markEmailAsVerified(): void
    {
        $this->emailVerified = true;
        $this->verificationToken = null;
        $this->updatedAt = date('Y-m-d\TH:i:s\Z');
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function toArray(bool $includePassword = false): array
    {
        $data = [
            'id' => $this->id,
            'email' => $this->email,
            'full_name' => $this->fullName,
            'email_verified' => $this->emailVerified,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($includePassword) {
            $data['password_hash'] = $this->passwordHash;
        }

        return $data;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
