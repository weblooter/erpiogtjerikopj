<?php

namespace NaturaSiberica\Api\Interfaces\Services\User;

use NaturaSiberica\Api\Interfaces\Services\ServiceInterface;

interface ProfileServiceInterface extends ServiceInterface
{
    /**
     * Получение данных профиля пользователя
     *
     * @param int $userId
     *
     * @return array
     */
    public function getProfile(int $userId): array;

    /**
     * Редактирование профиля
     *
     * @param int   $userId
     * @param array $body
     *
     * @return array
     */
    public function editProfile(int $userId, array $body): array;

    /**
     * Настройки уведомлений
     *
     * @param int   $userId
     * @param array $body
     *
     * @return array
     */
    public function editNotifications(int $userId, array $body): array;

    /**
     * Редактирование email
     *
     * @param int   $userId
     * @param array $body
     *
     * @return array
     */
    public function changeEmail(int $userId, array $body): array;
}
