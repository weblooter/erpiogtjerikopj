<?php

namespace NaturaSiberica\Api\Interfaces\DTO;

interface DTOInterface
{
    public function modify(array $fields): DTOInterface;
}
