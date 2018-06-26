<?php declare(strict_types = 1);

use EcomailFlexibee\Client;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

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
        $client = new Client('https://demo.flexibee.eu', 'demo', 'winstrom', 'winstrom', $evidence);
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
                'adresar',
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
                    'cisDosle' => '1234'
                ],
                [
                    'cisDosle' => '1234',
                ],
            ],
        ];
    }

}