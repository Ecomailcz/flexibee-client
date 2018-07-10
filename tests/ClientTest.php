<?php declare(strict_types = 1);

use EcomailFlexibee\Client;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

    private const URL = 'https://demo.flexibee.eu:5434';
    private const COMPANY = 'demo';
    private const USER = 'winstrom';
    private const PASSWORD = 'winstrom';
    private const DEFAULT_EVIDENCE_FOR_TESTING = 'adresar';

    /**
     * @dataProvider getEvidences
     * @param string $evidence
     * @param mixed[] $evidenceData
     * @param mixed[] $expectedDataAfterUpdate
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     */
    public function testCRUDOperations(string $evidence, array $evidenceData, array $expectedDataAfterUpdate): void
    {
        $client = new Client(self::URL, self::COMPANY, self::USER, self::PASSWORD, $evidence);
        $addressBookId = $client->save($evidenceData, null);
        $client->save($expectedDataAfterUpdate, $addressBookId);
        $addressBookRefreshed = $client->getById($addressBookId);
        foreach ($expectedDataAfterUpdate as $key => $value) {
            $this->assertEquals($value, $addressBookRefreshed[$key]);
        }
        $this->assertNotEmpty($client->getPdfById($addressBookId));
        $client->deleteById($addressBookId);
        $this->assertCount(0, $client->findById($addressBookId));
        $this->expectException(EcomailFlexibeeNoEvidenceResult::class);
        $client->getById($addressBookId);
    }

    public function testSessionAuthToken(): void
    {
        $client = new Client(self::URL, self::COMPANY, self::USER, self::PASSWORD, self::DEFAULT_EVIDENCE_FOR_TESTING);
        $sessionAuthToken = $client->generateSessionAuthToken();
        var_dump($sessionAuthToken);
    }

    /**
     * @return mixed[][]
     */
    public function getEvidences(): array
    {
        $faker = Factory::create();
        $code = mb_substr($faker->uuid, 0, 20);
        $name = $faker->userName;

        return [
            [
                self::DEFAULT_EVIDENCE_FOR_TESTING,
                [
                    'kod' => $code,
                    'nazev' => $name,
                ],
                [
                    'nazev' => 'Adresar edited',
                ],
            ],
            [
                'faktura-vydana',
                [
                    'kod' => $code,
                    'typDokl' => 'code:FAKTURA',
                    'cisDosle' => '1234',
                ],
                [
                    'cisDosle' => '1234',
                ],
            ],
        ];
    }

}