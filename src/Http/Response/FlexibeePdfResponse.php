<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

final class FlexibeePdfResponse extends GenericResponse
{

    public function __construct(string $pdfContent)
    {
        parent::__construct(200, null, true, null,0,null, [
            'pdfContent' => $pdfContent,
        ]);
    }

}
