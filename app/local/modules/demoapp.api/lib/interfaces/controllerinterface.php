<?php

namespace NaturaSiberica\Api\Interfaces;

use Psr\Container\ContainerInterface;

interface ControllerInterface
{
    public function __construct(ContainerInterface $container);
}
