<?php

namespace NaturaSiberica\Api\Collections;

use Spatie\DataTransferObject\DataTransferObjectCollection;

abstract class Collection extends DataTransferObjectCollection
{
    public static function create(array $items = []): Collection
    {
        return new static($items);
    }

    public function toJson()
    {
        return json_encode($this->collection, JSON_UNESCAPED_UNICODE);
    }

    public function reset()
    {
        $this->collection = [];
    }
}
