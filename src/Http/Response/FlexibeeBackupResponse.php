<?php declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

class FlexibeeBackupResponse extends FlexibeeResponse
{

    public function __construct(string $backupContent)
    {
        parent::__construct(200, null, true, null,0,null, [
            'backupContent' => $backupContent,
        ]);
    }

}
