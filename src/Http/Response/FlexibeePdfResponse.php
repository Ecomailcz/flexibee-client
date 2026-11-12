<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeePdfResponse extends FlexibeeResponse
{

    public function __construct(string $pdfContent)
    {
        parent::__construct(200, version: null, success: true,rowCount: 0, data: [
            'pdfContent' => $pdfContent,
        ]);
    }

}
