<?php

namespace App\Exception;

abstract class AppException extends \LogicException implements \JsonSerializable
{
    /**
     * Код ошибки
     */
    protected $errorCode = "AppException";

    /**
     * Аргументы ошибки, описывающие ее
     */
    protected $args = [];

    function jsonSerialize() {
        return [
            'code' => $this->errorCode,
            'args' => $this->args
        ];
    }
}
