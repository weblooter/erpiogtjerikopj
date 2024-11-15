<?php

namespace NaturaSiberica\Api\Validators;

use Bitrix\Main\Localization\Loc;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Collections\Error\ErrorCollection;
use NaturaSiberica\Api\DTO\Error\ErrorDTO;

Loc::loadMessages(__FILE__);

abstract class Validator
{
    protected ErrorCollection $errorCollection;
    protected int             $errorCode      = StatusCodeInterface::STATUS_BAD_REQUEST;
    public array              $requiredFields = [];
    protected ?array          $requestBody    = null;
    protected array           $errors         = [];

    public function __construct()
    {
        $this->errorCollection = new ErrorCollection();
    }

    /**
     * @param array|null $requestBody
     *
     * @return Validator
     */
    public function setRequestBody(?array $requestBody): Validator
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    /**
     * @param int $errorCode
     */
    public function setErrorCode(int $errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    public function getPropertyTitles(): array
    {
        return [];
    }

    public function getPropertyTitle(string $parameter)
    {
        $properties = $this->getPropertyTitles();
        return $properties[$parameter] ?? $parameter;
    }

    public function validateRequiredFields()
    {
        if (empty($this->requiredFields)) {
            return $this;
        }

        foreach ($this->requiredFields as $field) {
            if (! key_exists($field, $this->requestBody)) {
                $type = sprintf('%s_is_not_defined', $field);
                $this->addError($field, $type);
            }
        }
    }

    protected function addError(string $field, string $type = 'error', int $code = StatusCodeInterface::STATUS_BAD_REQUEST)
    {
        $dto = ErrorDTO::createFromParameters(
            $type,
            $code,
            Loc::getMessage('error_empty_required_field', [
                '#field#' => $this->getPropertyTitle($field) ?? $field,
            ])
        );
        $this->errorCollection->offsetSet(null, $dto);
    }

    protected function addCustomError($error)
    {
        $this->errorCollection->offsetSet(null, $error);
    }

    /**
     * @param string|null $message
     *
     * @throws Exception
     */
    public function throwErrors(string $message = null)
    {
        if (! empty($this->errorCollection->toArray())) {
            throw new Exception($message ?? $this->errorCollection->toJson());
        }
    }
}
