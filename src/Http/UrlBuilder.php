<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Enum\SearchQueryOperator;
use EcomailFlexibee\Validator\ParameterValidator;
use Purl\ParserInterface;
use Purl\Path;
use Purl\Query;
use Purl\Url;

class UrlBuilder extends Url
{

    /**
     * @var string
     */
    private $company;

    /**
     * @var string
     */
    private $evidence;

    /**
     * @var \EcomailFlexibee\Validator\ParameterValidator
     */
    private $validator;

    public function __construct(Config $config, ?ParserInterface $parser = null)
    {
        parent::__construct($config->getUrl(), $parser);

        $this->company = $config->getCompany();
        $this->evidence = $config->getEvidence();
        $this->validator = new ParameterValidator();
    }

    public function createAuthTokenUrl(): string
    {
        $this->setPath(new Path('/login-logout/login.json'));

        return $this->getUrl();
    }

    /**
     * @param array<mixed> $uriParameters
     * @return string
     */
    public function createLoginFormUrl(array $uriParameters): string
    {
        $this->setPath(new Path('/login-logout/login.html'));
        $this->createQueryParams($uriParameters);

        return $this->getUrl();
    }

    /**
     * @param int $id
     * @param array<mixed> $uriParameters
     * @return string
     */
    public function createPdfUrl(int $id, array $uriParameters): string
    {
        $this->setPath($this->buildPathWithIdOrFilter($id, 'pdf'));
        $this->createQueryParams($uriParameters);

        return $this->getUrl();
    }

    public function createBackupUrl(): string
    {
        $this->setPath(new Path(\sprintf('/c/%s/backup', $this->company)));

        return $this->getUrl();
    }

    public function createRestoreUrl(string $companyName): string
    {
        $this->setPath(new Path(\sprintf('/c/%s/restore?name=%s', $this->company, $companyName)));

        return $this->getUrl();
    }

    /**
     * @param array<mixed> $uriParameters
     * @return string
     */
    public function createChangesUrl(array $uriParameters = []): string
    {
        $this->setPath(new Path(\sprintf('/c/%s/changes.json', $this->company)));
        $this->createQueryParams($uriParameters);

        return $this->getUrl();
    }

    public function createChangesStatusUrl(): string
    {
        $this->setPath(new Path(\sprintf('/c/%s/changes/status.json', $this->company)));

        return $this->getUrl();
    }

    /**
     * @param array<mixed> $uriParameters
     * @return string
     */
    public function createUriByEvidenceOnly(array $uriParameters): string
    {
        $this->setPath($this->buildPathForOnlyEvidence());
        $this->createQueryParams($uriParameters);

        return $this->getUrl();
    }

    /**
     * @param string $filterQuery
     * @param array<mixed> $uriParameters
     * @return string
     */
    public function createFilterQuery(string $filterQuery, array $uriParameters): string
    {
        $this->setPath($this->buildPathWithIdOrFilter(SearchQueryOperator::convertOperatorsInQuery($filterQuery)));
        $this->createQueryParams($uriParameters);

        return $this->getUrl();
    }

    /**
     * @param string       $code
     * @param array<mixed> $uriParameters
     * @return string
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     */
    public function createUriByCodeOnly(string $code, array $uriParameters): string
    {
        $this->validator->validateFlexibeeRequestCodeParameter($code);
        $this->setPath($this->buildPathWithIdOrFilter(\sprintf('(kod eq \'%s\')', $code)));
        $this->createQueryParams($uriParameters);

        return $this->getUrl();
    }

    public function getUrl(): string
    {
        $result =  parent::getUrl();
        $this->setQuery(new Query());
        $this->setPath(new Path());

        return $result;
    }

    /**
     * @param mixed $filterQueryOrId
     * @param array<mixed> $uriParams
     * @return string
     */
    public function createUri($filterQueryOrId, array $uriParams): string
    {
        if ($filterQueryOrId === null) {
            $this->setPath($this->buildPathForOnlyEvidence());
        } else {
            $this->setPath($this->buildPathWithIdOrFilter($filterQueryOrId));
        }

        $this->createQueryParams($uriParams);

        return $this->getUrl();
    }

    /**
     * @param string|int $filterQueryOrId
     * @param string $format
     * @return \Purl\Path
     */
    private function buildPathWithIdOrFilter($filterQueryOrId, string $format = 'json'): Path
    {
        return new Path(\sprintf('c/%s/%s/%s.%s', $this->company, $this->evidence, $filterQueryOrId, $format));
    }

    private function buildPathForOnlyEvidence(): Path
    {
        return new Path(\sprintf('c/%s/%s.json', $this->company, $this->evidence));
    }

    /**
     * @param array<mixed> $parameters
     */
    private function createQueryParams(array $parameters): void
    {
        $parameters = \array_merge(['limit' => '0'], $parameters);
        $this->setQuery(new Query(\http_build_query($parameters)));
    }

}
