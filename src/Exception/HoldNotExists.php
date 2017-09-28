<?php

namespace App\Exception;

class HoldNotExists extends AppException
{
    protected $errorCode = "HoldNotExists";

    public function __construct(string $holdUUID)
    {
        $this->args = [
            'holdUUID' => $holdUUID,
        ];
        $msg = 'Hold "'.$holdUUID.' not exists';
        parent::__construct($msg);
    }
}
