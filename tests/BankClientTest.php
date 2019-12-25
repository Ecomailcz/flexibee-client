<?php declare(strict_types=1);


namespace EcomailFlexibeeTest;

use EcomailFlexibee\BankClient;
use Mockery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class BankClientTest extends TestCase
{

    /**
     * @var \EcomailFlexibee\Config
     */
    private $config;

    public function setUp(): void
    {
        parent::setUp();
        $this->config = new \EcomailFlexibee\Config(
            Config::HOST,
            Config::COMPANY,
            Config::USERNAME,
            Config::PASSWORD,
            'banka',
            false,
            null,
        );
    }

    public function testDownloadOnlineListingsMock(): void
    {
        /** @var \EcomailFlexibee\BankClient|\Mockery\MockInterface $bankClientMock */
        $bankClientMock = Mockery::mock(BankClient::class, [$this->config])->makePartial();
        $bankClientMock->shouldReceive('downloadOnlineListings')->andReturnTrue();
        Assert::assertTrue($bankClientMock->downloadOnlineListings());
    }

    public function testAutomaticPairing(): void
    {
        /** @var \EcomailFlexibee\BankClient|\Mockery\MockInterface $bankClientMock */
        $bankClientMock = Mockery::mock(BankClient::class, [$this->config])->makePartial();
        $bankClientMock->shouldReceive('automaticPairing')->andReturnTrue();
        Assert::assertTrue($bankClientMock->automaticPairing());
    }

}
