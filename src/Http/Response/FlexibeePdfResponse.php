<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeePdfResponse extends FlexibeeResponse
{

    public function __construct(string $pdfContent)
    {
        parent::__construct(200, null, true, null,0, [
            'pdfContent' => $pdfContent,
        ]);
    }

}
