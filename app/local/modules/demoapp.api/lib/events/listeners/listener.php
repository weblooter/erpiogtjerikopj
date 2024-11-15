<?php

namespace NaturaSiberica\Api\Events\Listeners;

abstract class Listener
{
    protected static bool $isHandlerDisallowed = false;

    protected static function allowHandler()
    {
        self::$isHandlerDisallowed = true;
    }

    protected static function disallowHandler()
    {
        self::$isHandlerDisallowed = false;
    }
}
