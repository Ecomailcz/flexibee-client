<?php declare(strict_types = 1);

namespace EcomailFlexibee;

use Consistence\ObjectPrototype;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestError;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\QueryBuilder;

class Client extends ObjectPrototype
{

    /**
     * REST API user
     *
     * @var string
     */
    private $user;

    /**
     * REST API password
     *
     * @var string
     */
    private $password;

    /**
     * Name of the evidence section (adresar, faktura-vydana ...)
     *
     * @var string
     */
    private $evidence;

    /**
     * Enable self signed certificates
     *
     * @var bool
     */
    private $selfSignedCertificate;

    /**
     * @var \EcomailFlexibee\Http\QueryBuilder
     */
    private $queryBuilder;

    public function __construct(string $url, string $company, string $user, string $password, string $evidence, bool $selfSignedCertificate = false)
    {
        $this->user = $user;
        $this->password = $password;
        $this->evidence = $evidence;
        $this->selfSignedCertificate = $selfSignedCertificate;
        $this->queryBuilder = new QueryBuilder($company, $evidence, $url);
    }

    public function deleteById(int $id): void
    {
        $this->makeRequest(Method::get(Method::DELETE), $this->queryBuilder->createUriByIdOnly($id, false), []);
    }

    /**
     * @param int $id
     * @return mixed[]
     */
    public function findById(int $id): array
    {
        try {
            return $this->getById($id);
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
            return [];
        }
    }

    /**
     * @param string $code
     * @return mixed[]
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function getByCode(string $code): array
    {
        $result = $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByCodeOnly(strtoupper($code)), []);
        return (!isset($result[0])) ? [] : $result[0] ;
    }

    /**
     * @param int $id
     * @return mixed[]
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function getById(int $id): array
    {
        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriByIdOnly($id), [])[0];
    }

    /**
     * @param string $code
     * @return mixed[]
     */
    public function findByCode(string $code): array
    {
        try {
            return $this->getByCode($code);
        } catch (EcomailFlexibeeNoEvidenceResult $exception) {
            return [];
        }
    }

    /**
     * @param mixed[] $evidenceData
     * @param int|null $id
     * @return int
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function save(array $evidenceData, ?int $id): int
    {
        if ($id) {
            $evidenceData['id'] = $id;
        }
        $postData[$this->evidence] = $evidenceData;
        $result = $this->makeRequest(Method::get(Method::PUT), $this->queryBuilder->createUriByEvidenceOnly(), $postData);
        return (int) $result[0]['id'];
    }

    public function getPdfById(int $id): string
    {
        return $this->makeRequest(Method::get(Method::GET), $this->queryBuilder->createUriPdf($id), [])['result'];
    }

    /**
     * @param \EcomailFlexibee\Http\Method $httpMethod
     * @param string $url
     * @param mixed[] $postFields
     * @return mixed[]
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function makeRequest(Method $httpMethod, string $url, array $postFields): array
    {
        /** @var resource $ch */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, TRUE);
        curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->user, $this->password));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod->getValue());

        if ($this->selfSignedCertificate) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $postData = [];
        if (count($postFields) !== 0) {
            $postData['winstrom'] = $postFields;
            (json_encode($postData));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/xmln',
            ));
        }
        $output = curl_exec($ch);
        $result = null;

        if (mb_strpos($url, '.pdf') !== false) {
            return ['result' => $output];
        }

        if (is_string($output)) {
            $result = json_decode($output, true)['winstrom'];
        }

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200 && curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 201) {

            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 404) {
                throw new EcomailFlexibeeNoEvidenceResult();
            }
            // Check authorization
            elseif (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 401) {
                throw new EcomailFlexibeeInvalidAuthorization($this->user, $this->password, $url);
            } elseif (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 400) {
                if ($result['success'] === 'false') {
                    foreach ($result['results'] as $response) {
                        foreach ($response['errors'] as $error) {
                            throw new EcomailFlexibeeRequestError($error['message']);
                        }
                    }

                }

            }
        }

        if (!$result) {
            return [];
        }

        if (isset($result['results'])) {
            return $result['results'];
        }

        unset($result['@version']);
        return array_values($result)[0];
    }

}
