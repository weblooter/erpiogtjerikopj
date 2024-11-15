<?php

namespace NaturaSiberica\Api\Validators;

use Bitrix\Main\Result;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Error\ErrorDTO;

class ResultValidator extends Validator
{
    /**
     * @param Result $result
     * @param string $errorType
     *
     * @return void
     * @throws Exception
     */
    public function validate(Result $result, string $errorType = 'database_error')
    {
        if (! empty($result->getErrors())) {
            foreach ($result->getErrors() as $error) {
                $errorDto = ErrorDTO::createFromParameters(
                    $errorType,
                    $error->getCode() ? : StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
                    $error->getMessage()
                );
                $this->addCustomError($errorDto);
            }

            $this->throwErrors();
        }
    }
}
