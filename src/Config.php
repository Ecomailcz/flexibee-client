<?php declare(strict_types = 1);

namespace EcomailFlexibee;

final class Config
{

    private string $url;

    private string $company;

    private string $user;

    private string $password;

    private string $evidence;

    private bool $selfSignedCertificate;

    private ?string $authSessionId;

    private ?string $logFilePath;

    public function __construct(
        string $url,
        string $company,
        string $user,
        string $password,
        string $evidence,
        bool $selfSignedCertificate,
        ?string $authSessionId = null,
        ?string $logFilePath = null
    )
    {
        $this->url = $url;
        $this->company = $company;
        $this->user = $user;
        $this->password = $password;
        $this->evidence = $evidence;
        $this->selfSignedCertificate = $selfSignedCertificate;
        $this->authSessionId = $authSessionId;
        $this->logFilePath = $logFilePath;
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

    public function isSelfSignedCertificate(): bool
    {
        return $this->selfSignedCertificate;
    }

    public function getAuthSessionId(): ?string
    {
        return $this->authSessionId;
    }

    public function getLogFilePath(): ?string
    {
        return $this->logFilePath;
    }

}
