<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use function urldecode;

final class UrlNormalizer
{

    public function normalize(string $url): string
    {
        return urldecode($url);
    }

}
