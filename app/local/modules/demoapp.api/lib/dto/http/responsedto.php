<?php

namespace NaturaSiberica\Api\DTO\Http;

use NaturaSiberica\Api\DTO\DTO;

class ResponseDTO extends DTO
{
    public bool $success = true;
    public ?array $data = null;
    public array $errors = [];
}
