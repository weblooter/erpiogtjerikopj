<?php

namespace NaturaSiberica\Api\Services\Mindbox;

use Bitrix\Main\Loader;
use Exception;
use Mindbox\Clients\AbstractMindboxClient;
use Mindbox\Core;
use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxException;
use Mindbox\Helper;
use Mindbox\Mindbox;
use Mindbox\MindboxResponse;
use Mindbox\Options as MindboxOptions;
use NaturaSiberica\Api\DTO\User\UserDTO;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\MindboxServiceInterface;
use NaturaSiberica\Api\Logger\Logger;
use NaturaSiberica\Api\Tools\Settings\Options;

Loader::includeModule('mindbox.marketing');

class MindboxService implements MindboxServiceInterface
{
    use Core;

    protected Mindbox               $mindbox;
    protected AbstractMindboxClient $mindboxClient;
    protected ?DTO                  $dto = null;
    protected Logger $logger;

    protected array $requestBody = [];

    private array $operations = [
        'confirmPhone'          => 'ManualPhoneConfirmation',
        'calculatePriceProduct' => 'CalculatePriceProduct',
    ];

    /**
     * @throws MindboxException
     */
    public function __construct()
    {
        $this->mindbox       = self::mindbox();
        $this->mindboxClient = $this->mindbox->getClient($this->getApiVersion());
        $this->logger = Logger::getInstance('mindbox');
    }

    public function getMindbox(): Mindbox
    {
        return $this->mindbox;
    }

    public function getMindboxClient(): AbstractMindboxClient
    {
        return $this->mindboxClient;
    }

    public function getApiVersion(): string
    {
        $version = Options::getMindboxApiVersion() ? : self::MINDBOX_API_VERSION;
        return sprintf('v%d', $version);
    }

    /**
     * @return array
     */
    public function getRequestBody(): array
    {
        return $this->requestBody;
    }

    /**
     * @return DTO|null
     */
    public function getDto(): ?DTO
    {
        return $this->dto;
    }

    /**
     * @param UserDTO $userDto
     *
     * @return void
     */
    public function addCustomerToRequestBody(UserDTO $userDto): void
    {
        $this->requestBody['customer'] = [
            'mobilePhone' => $userDto->phone,
        ];

        if (!empty($userDto->id)) {
            $this->requestBody['customer']['ids']['websiteId'] = $userDto->id;
        }

        if (!empty($userDto->mindboxId)) {
            $this->requestBody['customer']['ids']['mindboxId'] = $userDto->mindboxId;
        }
    }

    /**
     * @param string $key
     * @param        $data
     *
     * @return void
     */
    public function addDataToRequestBody(string $key, $data): void
    {
        $this->requestBody[$key] = $data;
    }

    /**
     * @param string $dtoClass
     *
     * @return void
     */
    public function prepareDto(string $dtoClass = DTO::class): void
    {
        if (empty($this->requestBody)) {
            return;
        }

        $dto       = new $dtoClass($this->requestBody);
        $this->dto = Helper::iconvDTO($dto);
    }

    /**
     * @return void
     */
    public function resetRequestBody(): void
    {
        $this->requestBody = [];
    }

    /**
     * @param string $alias
     *
     * @return string|void
     */
    private function prepareOperationName(string $alias)
    {
        if (! empty($this->operations[$alias])) {
            return \Mindbox\Options::getPrefix() . '.' . $this->operations[$alias];
        }
    }

    /**
     * Возвращает название операции Mindbox
     *
     * @param string $name
     *
     * @return string
     *
     * @uses \Mindbox\Options::getOperationName()
     */
    public function getOperation(string $name): string
    {
        return $this->prepareOperationName($name) ?? MindboxOptions::getOperationName($name);
    }

    /**
     * @param AbstractMindboxClient $client
     *
     * @return MindboxResponse
     * @throws Exception
     */
    public function getResponse(AbstractMindboxClient $client): MindboxResponse
    {
        return $client->sendRequest();
    }
}
