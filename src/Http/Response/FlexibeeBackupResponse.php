<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeeBackupResponse extends FlexibeeResponse
{

    public function __construct(string $backupContent)
    {
        parent::__construct(200, version: null, success: true,rowCount: 0, data: [
            'backupContent' => $backupContent,
        ]);
    }

}
