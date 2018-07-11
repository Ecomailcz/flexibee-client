<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

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

    /**
     * @param int $id
     * @param mixed[] $queryParams
     * @return string
     */
    public function createUriByIdOnly(int $id, array $queryParams = []): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s/%d.json', $this->company, $this->evidence, $id)));
        if (count($queryParams) !== 0) {
            $this->createQueryParams($queryParams);
        }

        return $this->getUrl();
    }

    /**
     * @param int $id
     * @param mixed[] $queryParams
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

    public function createUriByEvidenceOnly(): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s.json', $this->company, $this->evidence)));

        return $this->getUrl();
    }

    /**
     * @param string $code
     * @param mixed[] $queryParams
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

    public function getUrl(): string
    {
        $result =  parent::getUrl();
        $this->setQuery(new Query());
        $this->setPath(new Path());
        return $result;
    }

    /**
     * @param mixed[] $params
     */
    private function createQueryParams(array $params): void
    {
        $this->setQuery(new Query(http_build_query($params)));
    }

}
