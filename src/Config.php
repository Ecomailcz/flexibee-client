<?php declare(strict_types = 1);

namespace EcomailFlexibee;

use Consistence\ObjectPrototype;

final class Config extends ObjectPrototype
{

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $company;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $evidence;

    /**
     * @var bool
     */
    private $selfSignedCertificate;

    /**
     * @var string|null
     */
    private $authSessionId;

    public function __construct(
        string $url,
        string $company,
        string $user,
        string $password,
        string $evidence,
        bool $selfSignedCertificate,
        ?string $authSessionId = null
    )
    {
        $this->url = $url;
        $this->company = $company;
        $this->user = $user;
        $this->password = $password;
        $this->evidence = $evidence;
        $this->selfSignedCertificate = $selfSignedCertificate;
        $this->authSessionId = $authSessionId;
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

}
