<?php

namespace NaturaSiberica\Api\Interfaces\Controllers;

use Psr\Container\ContainerInterface;

interface ControllerInterface
{
    public function __construct(ContainerInterface $container);
}
