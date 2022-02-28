<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

final class Config
{

    public function __construct(
        private string $url,
        private string $company,
        private string $user,
        private string $password,
        private string $evidence,
        private bool $verifySSLCertificate,
        private ?string $authSessionId = null,
    )
    {
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getEvidence(): string
    {
        return $this->evidence;
    }

    public function verifySSLCertificate(): bool
    {
        return $this->verifySSLCertificate;
    }

    public function getAuthSessionId(): ?string
    {
        return $this->authSessionId;
    }

}
