<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http;

final class UrlNormalizer
{

    public function normalize(string $url): string
    {
        return \urldecode($url);
    }

}
