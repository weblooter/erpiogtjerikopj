<?php

namespace NaturaSiberica\Api\Repositories\Token;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Result;
use Exception;
use NaturaSiberica\Api\DTO\Token\RefreshTokenDTO;
use NaturaSiberica\Api\Entities\Tokens\TokensTable;
use NaturaSiberica\Api\Validators\ResultValidator;

class TokenRepository
{
    private ResultValidator $validator;
    private ?Result $result = null;
    private array $options = [
        'filter' => [],
        'select' => [
            'fuserId',
            'refreshToken',
            'created',
            'expires'
        ]
    ];

    public function __construct()
    {
        $this->validator = new ResultValidator();
    }

    /**
     * @return void
     *
     * @throws ObjectPropertyException
     * @throws Exception
     */
    private function prepareResult(): void
    {
        $this->result = TokensTable::getList($this->options);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return $this
     * @throws Exception
     */
    public function findBy($field, $value): TokenRepository
    {
        $this->options['filter'][$field] = $value;
        $this->prepareResult();
        return $this;
    }

    /**
     * @param $id
     *
     * @return $this
     * @throws Exception
     */
    public function findById($id): TokenRepository
    {
        return $this->findBy('=id', $id);
    }

    /**
     * @param int $fuserId
     *
     * @return $this
     * @throws Exception
     */
    public function findByFuserId(int $fuserId): TokenRepository
    {
        return $this->findBy('=fuserId', $fuserId);
    }

    /**
     * @param string $refreshToken
     *
     * @return $this
     * @throws Exception
     */
    public function findByRefreshToken(string $refreshToken): TokenRepository
    {
        return $this->findBy('=refreshToken', $refreshToken);
    }

    /**
     * @return RefreshTokenDTO|false
     *
     */
    public function get()
    {
        $row = $this->result->fetch();

        if (!empty($row)) {
            return RefreshTokenDTO::create($row);
        }

        return $row;
    }

    /**
     * @param RefreshTokenDTO $dto
     *
     * @return bool
     * @throws Exception
     */
    public function create(RefreshTokenDTO $dto): bool
    {
        $fields = $dto->except('type')->toArray(false);
        $this->prepareFields($fields);
        $addResult = TokensTable::add($fields);

        $this->validator->validate($addResult, 'db_error_on_create_refresh_token');

        return $addResult->isSuccess();
    }

    /**
     * @param RefreshTokenDTO $dto
     *
     * @return bool
     * @throws Exception
     */
    public function update(RefreshTokenDTO $dto): bool
    {
        $fields = $dto->except('fuserId', 'type')->toArray(false);

        $this->prepareFields($fields);

        $updateResult = TokensTable::update($dto->fuserId, $fields);
        $this->validator->validate($updateResult, 'db_error_on_update_refresh_token');

        return $updateResult->isSuccess();
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $result = TokensTable::delete($id);

        $this->validator->validate($result, 'db_error_on_delete_refresh_token');

        return $result->isSuccess();
    }

    private function prepareFields(array &$fields)
    {
        $fields['refreshToken'] = $fields['token'];
        unset($fields['token']);

        return $this;
    }
}
