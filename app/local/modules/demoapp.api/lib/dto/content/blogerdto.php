<?php

namespace NaturaSiberica\Api\DTO\Content;

use Spatie\DataTransferObject\DataTransferObject;

class BlogerDTO extends DataTransferObject
{
    public string $name;
    public string $position;
    public string $photo;
}
