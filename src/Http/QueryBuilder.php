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

    public function createUriByIdOnly(int $id, bool $withFullDetail = true): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s/%d.json', $this->company, $this->evidence, $id)));
        if ($withFullDetail) {
            $this->createFullDetailQuery();
        }

        return $this->getUrl();
    }

    public function createUriPdf(int $id): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s/%d.pdf', $this->company, $this->evidence, $id)));

        return $this->getUrl();
    }

    public function createUriByEvidenceOnly(): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s.json', $this->company, $this->evidence)));

        return $this->getUrl();
    }


    public function createUriByCodeOnly(string $code, bool $withFullDetail = true): string
    {
        $this->setPath(new Path(sprintf('c/%s/%s/(kod=\'%s\').json', $this->company, $this->evidence, $code)));
        if ($withFullDetail) {
            $this->createFullDetailQuery();
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

    private function createFullDetailQuery(): void
    {
        $this->setQuery(new Query('detail=full'));
    }

}
