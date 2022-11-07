<?php

declare(strict_types = 1);

namespace EcomailFlexibee;

use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\Response;

final class AuthClient extends Client
{

    /**
     * @param array $parameters
     */
    public function getLoginFormUrl(array $parameters): string
    {
        return $this->queryBuilder->createLoginFormUrl($parameters);
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function getAuthAndRefreshToken(): Response
    {
        return $this->httpClient->request(
            $this->queryBuilder->createAuthTokenUrl(),
            Method::get(Method::POST),
            [],
            [
                'username' => $this->config->getUser(),
                'password' => $this->config->getPassword(),
            ],
            [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            $this->config,
        );
    }

}