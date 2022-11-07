<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\Response;

final class ApiChangesClient extends Client
{

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getChangesApiForEvidence(string $evidenceName): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createChangesUrl(['evidence' => $evidenceName]),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getAllApiChanges(?string $fromVersion): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createChangesUrl(['start' => $fromVersion]),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        );
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function isAllowedChangesApi(): bool
    {
        return $this->httpClient->request(
            $this->queryBuilder->createChangesStatusUrl(),
            Method::get(Method::GET),
            [],
            [],
            [],
            $this->config,
        )->isSuccess();
    }

}