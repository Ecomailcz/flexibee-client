<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Enum\SearchQueryOperator;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Validator\ParameterValidator;

use function array_merge;
use function http_build_query;
use function ltrim;
use function parse_url;
use function sprintf;
use function str_replace;

class UrlBuilder
{

    private readonly string $baseUrl;

    private readonly string $company;

    private readonly string $evidence;

    private readonly ParameterValidator $validator;

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function __construct(Config $config)
    {
        $this->baseUrl = $this->createBaseUrl($config->getUrl());
        $this->company = $config->getCompany();
        $this->evidence = $config->getEvidence();
        $this->validator = new ParameterValidator();
    }

    public function createAuthTokenUrl(): string
    {
        return $this->createUrl('/login-logout/login.json');
    }

    /**
     * @param array<mixed> $uriParameters
     */
    public function createLoginFormUrl(array $uriParameters): string
    {
        return $this->createUrl('/login-logout/login.html', $uriParameters);
    }

    /**
     * @param array<mixed> $uriParameters
     */
    public function createPdfUrl(int $id, array $uriParameters): string
    {
        return $this->createUrl($this->buildPathWithIdOrFilter($id, 'pdf'), $uriParameters);
    }

    public function createBackupUrl(): string
    {
        return $this->createUrl(sprintf('/c/%s/backup', $this->company));
    }

    public function createCompanyUrl(): string
    {
        return $this->createUrl(sprintf('/c/%s.json', $this->company));
    }

    public function createRestoreUrl(string $companyName): string
    {
        return $this->createUrl(sprintf('/c/%s/restore?name=%s', $this->company, $companyName));
    }

    /**
     * @param array<mixed> $uriParameters
     */
    public function createChangesUrl(array $uriParameters = []): string
    {
        return $this->createUrl(sprintf('/c/%s/changes.json', $this->company), $uriParameters);
    }

    public function createChangesStatusUrl(): string
    {
        return $this->createUrl(sprintf('/c/%s/changes/status.json', $this->company));
    }

    /**
     * @param array<mixed> $uriParameters
     */
    public function createUriByEvidenceOnly(array $uriParameters): string
    {
        return $this->createUrl($this->buildPathForOnlyEvidence(), $uriParameters);
    }

    /**
     * @param array<mixed> $uriParameters
     */
    public function createFilterQuery(string $filterQuery, array $uriParameters): string
    {
        return $this->createUrl($this->buildPathWithIdOrFilter(SearchQueryOperator::convertOperatorsInQuery($filterQuery)), $uriParameters);
    }

    /**
     * @param array<mixed> $uriParameters
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     */
    public function createUriByCodeOnly(string $code, array $uriParameters): string
    {
        $this->validator->validateFlexibeeRequestCodeParameter($code);

        return $this->createUrl($this->buildPathWithIdOrFilter(sprintf('(kod eq \'%s\')', $code)), $uriParameters);
    }

    /**
     * @param array<mixed> $uriParams
     */
    public function createUri(null|int|string $filterQueryOrId, array $uriParams): string
    {
        $path = $filterQueryOrId === null
            ? $this->buildPathForOnlyEvidence()
            : $this->buildPathWithIdOrFilter($filterQueryOrId);

        return $this->createUrl($path, $uriParams);
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    private function createBaseUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new EcomailFlexibeeRequestFail(sprintf('FlexiBee URL "%s" must contain a scheme and a host.', $url));
        }

        $userInfo = isset($parts['user'])
            ? $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '') . '@'
            : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return sprintf('%s://%s%s%s', $parts['scheme'], $userInfo, $parts['host'], $port);
    }

    /**
     * @param array<mixed>|null $uriParameters
     */
    private function createUrl(string $path, ?array $uriParameters = null): string
    {
        $url = sprintf('%s/%s', $this->baseUrl, str_replace(' ', '%20', ltrim($path, '/')));

        if ($uriParameters === null) {
            return $url;
        }

        return sprintf('%s?%s', $url, http_build_query(array_merge(['limit' => '0'], $uriParameters)));
    }

    private function buildPathWithIdOrFilter(string|int $filterQueryOrId, string $format = 'json'): string
    {
        return sprintf('c/%s/%s/%s.%s', $this->company, $this->evidence, $filterQueryOrId, $format);
    }

    private function buildPathForOnlyEvidence(): string
    {
        return sprintf('c/%s/%s.json', $this->company, $this->evidence);
    }

}
