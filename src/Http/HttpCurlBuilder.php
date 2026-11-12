<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use CurlHandle;
use EcomailFlexibee\Config;

use function curl_init;
use function curl_setopt_array;

final readonly class HttpCurlBuilder
{

    private CurlOptionsFactory $curlOptionsFactory;

    public function __construct()
    {
        $this->curlOptionsFactory = new CurlOptionsFactory();
    }

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     */
    public function build(CurlRequest $request, Config $config): CurlHandle
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->curlOptionsFactory->create($request, $config));

        return $ch;
    }

}
