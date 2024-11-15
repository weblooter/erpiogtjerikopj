<?php

namespace NaturaSiberica\Api\Interfaces\Services\Mindbox;

use Mindbox\Clients\AbstractMindboxClient;
use Mindbox\DTO\DTO;
use Mindbox\Mindbox;

interface MindboxServiceInterface
{
    const MINDBOX_API_VERSION = 3;
    const MINDBOX_SUBSCRIPTIONS_TOPIC_NAME = 'NaturaSibericaNewsletter';

    public function getMindbox(): Mindbox;

    public function getMindboxClient(): AbstractMindboxClient;

    public function getApiVersion(): string;

    public function getDto(): ?DTO;

    public function prepareDto();

    public function getRequestBody(): array;

    public function resetRequestBody();
}
