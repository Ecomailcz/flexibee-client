<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use EcomailFlexibee\Enum\SearchQueryOperator;
use Purl\ParserInterface;
use Purl\Path;
use Purl\Query;
use Purl\Url;

class QueryBuilder extends Url
{

    /**
     * @var string
     */
    private $company;

    /**
     * @var string
     */
    private $evidence;

    public function __construct(string $company, string $evidence, string $host, ?ParserInterface $parser = null)
    {
        parent::__construct($host, $parser);
        $this->company = $company;
        $this->evidence = $evidence;
    }

    public function createAuthTokenUrl(): string
    {
        $this->setPath(new Path('/login-logout/login.json'));

        return $this->getUrl();
    }

    /**
     * @param int $id
     * @param array<mixed> $queryParams
     * @return string
     */
    public function createUriByIdOnly(int $id, array $queryParams = []): string
    {
        return $this->createUriByAnyoneId($id, $queryParams);
    }

    /**
     * @param int $id
     * @param array<mixed> $queryParams
     * @return string
     */
    public function createUriPdf(int $id, array $queryParams = []): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s/%d.pdf', $this->company, $this->evidence, $id)));

        if (count($queryParams) !== 0) {
            $this->createQueryParams($queryParams);
        }

        return $this->getUrl();
    }

    /**
     * @param array<string> $queryParams
     * @return string
     */
    public function createUriByEvidenceOnly(array $queryParams): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s.json', $this->company, $this->evidence)));
        $this->createQueryParams($queryParams);

        return $this->getUrl();
    }

    public function createUriByDomainOnly(string $uri): string
    {
        $this->setPath(new Path($uri));

        return $this->getUrl();
    }

    public function createBaseUrl(string $uri): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s/%s', $this->company, $this->evidence, $uri)));

        return $this->getUrl();
    }

    /**
     * @param array<mixed> $queryParameters
     * @return string
     */
    public function createUriByEvidenceWithQueryParameters(array $queryParameters): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s.json', $this->company, $this->evidence)));
        $this->createQueryParams($queryParameters);

        return $this->getUrl();
    }

    /**
     * @param string $query
     * @param array<mixed> $queryParameters
     * @return string
     */
    public function createUriByEvidenceForSearchQuery(string $query, array $queryParameters): string
    {
        if (mb_strlen(trim($query)) === 0) {
            return $this->createUriByEvidenceOnly($queryParameters);
        }

        $this->setPath(new Path(sprintf('c/%s/%s/(%s).json', $this->company, $this->evidence, $this->normalizeSearchQuery($query))));
        $this->createQueryParams($queryParameters);

        return $this->getUrl();
    }

    /**
     * @param string $code
     * @param array<mixed> $queryParams
     * @return string
     */
    public function createUriByCodeOnly(string $code, array $queryParams = []): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s/(kod=\'%s\').json', $this->company, $this->evidence, $code)));

        if (count($queryParams) !== 0) {
            $this->createQueryParams($queryParams);
        }

        return $this->getUrl();
    }

    /**
     * @param string $id
     * @param array<mixed> $queryParams
     * @return string
     */
    public function createUriByCustomId(string $id, array $queryParams = []): string
    {
        return $this->createUriByAnyoneId($id, $queryParams);
    }

    public function getUrl(): string
    {
        $result =  parent::getUrl();
        $this->setQuery(new Query());
        $this->setPath(new Path());

        return $result;
    }

    /**
     * @param mixed $id
     * @param array<mixed> $queryParams
     * @return string
     */
    private function createUriByAnyoneId($id, array $queryParams = []): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s/%s.json', $this->company, $this->evidence, $id)));

        if (count($queryParams) !== 0) {
            $this->createQueryParams($queryParams);
        }

        return $this->getUrl();
    }

    /**
     * @param array<mixed> $params
     */
    private function createQueryParams(array $params): void
    {
        $this->setQuery(new Query(http_build_query($params)));
    }

    private function normalizeSearchQuery(string $query): string
    {
        return SearchQueryOperator::convertOperatorsInQuery($query);
    }

}
