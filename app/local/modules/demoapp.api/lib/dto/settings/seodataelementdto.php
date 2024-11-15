<?php

namespace NaturaSiberica\Api\DTO\Settings;

use NaturaSiberica\Api\DTO\AbstractDTO;

class SeoDataElementDTO extends AbstractDTO
{
    private string $title = '';
    private string $description = '';
    private string $pageName = '';

    public function setTitle(string $title)
    {
        $this->title = $title;
    }
    public function setDescription(string $description)
    {
        $this->description = $description;
    }
    public function setPageName(string $pageName)
    {
        $this->pageName = $pageName;
    }

    public function getTitle()
    {
        return $this->title;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getPageName()
    {
        return $this->pageName;
    }

    protected function requiredParameters(): array
    {
        // TODO: Implement requiredParameters() method.
        return [];
    }
}
