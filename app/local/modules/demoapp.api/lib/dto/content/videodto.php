<?php

namespace NaturaSiberica\Api\DTO\Content;

use Spatie\DataTransferObject\DataTransferObject;

class VideoDTO extends DataTransferObject
{
    public int $id;
    public string $name;
    public string $code;
    public string $image;
    public string $description;
    public string $url;
    public ?string $duration = null;
}
