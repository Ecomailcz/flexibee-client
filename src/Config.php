<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

final class Config
{

    public function __construct(
        private readonly string $url,
        private readonly string $company,
        private readonly string $user,
        private readonly string $password,
        private readonly string $evidence,
        private readonly bool $verifySSLCertificate,
        private readonly ?string $authSessionId = null,
    ) {
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
