<?php

namespace NaturaSiberica\Api\Interfaces\Services\User;

use NaturaSiberica\Api\Interfaces\Services\ServiceInterface;

interface AddressServiceInterface extends ServiceInterface
{
    /**
     * Получение списка адресов / отдельного адреса
     *
     * @param int      $userId
     * @param int|null $id
     *
     * @return array
     */
    public function getAddress(int $userId, int $id = null): array;

    /**
     * Добавление нового адреса
     *
     * @param int   $userId
     * @param array $body
     *
     * @return array
     */
    public function addAddress(int $userId, array $body): array;

    /**
     * Редактирование адреса
     *
     * @param int   $userId
     * @param array $body
     * @param int   $id
     *
     * @return array
     */
    public function editAddress(int $userId, array $body, int $id): array;

    /**
     * Удаление адреса
     *
     * @param int $userId
     * @param int $id
     *
     * @return array
     */
    public function deleteAddress(int $userId, int $id): array;
}
