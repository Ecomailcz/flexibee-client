<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Enum\SearchQueryOperator;
use EcomailFlexibee\Validator\ParameterValidator;
use Nette\Http\Url;

use function array_merge;
use function sprintf;

final class UrlBuilder
{

    private string $company;

    private string $evidence;

    private ParameterValidator $validator;
    private Url $url;

    public function __construct(Config $config)
    {
        $this->url = new Url($config->getUrl());
        $this->company = $config->getCompany();
        $this->evidence = $config->getEvidence();
        $this->validator = new ParameterValidator();
    }

    public function createAuthTokenUrl(): string
    {
        $url = $this->url;

        return (string) $url->setPath('/login-logout/login.json');
    }

    /**
     * @param array $uriParameters
     */
    public function createLoginFormUrl(array $uriParameters): string
    {
        $url = $this->url;

        return (string) $this->createQueryParams($uriParameters, $url->setPath('/login-logout/login.html'));
    }

    /**
     * @param array $uriParameters
     */
    public function createPdfUrl(int $id, array $uriParameters): string
    {
        return (string) $this->createQueryParams($uriParameters, $this->buildPathWithIdOrFilter($id, 'pdf'));
    }

    public function createBackupUrl(): string
    {
        $url = $this->url;

        return (string) $url->setPath(sprintf('/c/%s/backup', $this->company));
    }

    public function createCompanyUrl(): string
    {
        $url = $this->url;

        return (string) $url->setPath(sprintf('/c/%s.json', $this->company));
    }

    public function createRestoreUrl(string $companyName): string
    {
        $url = $this->url;

        return (string) $url->setPath(sprintf('/c/%s/restore?name=%s', $this->company, $companyName));
    }

    /**
     * @param array $uriParameters
     */
    public function createChangesUrl(array $uriParameters = []): string
    {
        $url = $this->url;

        return (string) $this->createQueryParams($uriParameters, $url->setPath(sprintf('/c/%s/changes.json', $this->company)));
    }

    public function createChangesStatusUrl(): string
    {
        $url = $this->url;

        return (string) $url->setPath(sprintf('/c/%s/changes/status.json', $this->company));
    }

    /**
     * @param array $uriParameters
     */
    public function createUriByEvidenceOnly(array $uriParameters): string
    {
        return (string) $this->createQueryParams($uriParameters, $this->buildPathForOnlyEvidence());
    }

    /**
     * @param array $uriParameters
     */
    public function createFilterQuery(string $filterQuery, array $uriParameters): string
    {
        return (string) $this->createQueryParams($uriParameters, $this->buildPathWithIdOrFilter(SearchQueryOperator::convertOperatorsInQuery($filterQuery)));
    }

    /**
     * @param array $uriParameters
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     */
    public function createUriByCodeOnly(string $code, array $uriParameters): string
    {
        $this->validator->validateFlexibeeRequestCodeParameter($code);

        return (string) $this->createQueryParams($uriParameters, $this->buildPathWithIdOrFilter(sprintf('(kod eq \'%s\')', $code)));
    }

    /**
     * @param array $uriParams
     */
    public function createUri(int|string|null $filterQueryOrId, array $uriParams): string
    {
        $url = $filterQueryOrId === null
            ? $this->buildPathForOnlyEvidence()
            : $this->buildPathWithIdOrFilter($filterQueryOrId);

        return (string) $this->createQueryParams($uriParams, $url);
    }

    private function buildPathWithIdOrFilter(string|int $filterQueryOrId, string $format = 'json'): Url
    {
        $url = $this->url;

        return $url->setPath(sprintf('c/%s/%s/%s.%s', $this->company, $this->evidence, $filterQueryOrId, $format));
    }

    private function buildPathForOnlyEvidence(): Url
    {
        $url = $this->url;

        return $url->setPath(sprintf('c/%s/%s.json', $this->company, $this->evidence));
    }

    /**
     * @param array $parameters
     */
    private function createQueryParams(array $parameters, Url $url): Url
    {
        return $url->setQuery(array_merge(['limit' => '0'], $parameters));
    }

}
