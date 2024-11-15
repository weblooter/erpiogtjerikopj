<?php

namespace Userstory\ItsIntegrator\Settings;

class Store
{
    private array         $store  = [];
    private static ?Store $_instance = null;

    private function __construct() {}

    protected function __clone() {}
    protected function __wakeup() {}

    static public function getInstance(): ?Store
    {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function setOption(array $data)
    {
        $this->store = $data;
    }

    public function setFileName(string $name, string $key)
    {
        $this->store[$key][] = $name;
    }

    public function get(): array
    {
        return $this->store;
    }

}
