<?php

namespace NaturaSiberica\Api\DTO\Catalog;

use Spatie\DataTransferObject\DataTransferObject;

class FastFilterDTO extends DataTransferObject
{
    public int     $id;
    public string  $categoryUrl;
    public string  $fullUrl;
    public string  $fastUrl;
    public ?string $title;
    public ?string $description;
    public ?string $keywords;
    public ?string $h1;
    public string  $name;
    public ?string $text;
    public ?int    $sort;
    public bool    $active;
}
