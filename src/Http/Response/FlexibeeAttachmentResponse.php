<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeeAttachmentResponse extends FlexibeeResponse
{

    public function __construct(string $attachmentContent)
    {
        parent::__construct(200, version: null, success: true, rowCount: 0, data: [
            'attachmentContent' => $attachmentContent,
        ]);
    }

}
