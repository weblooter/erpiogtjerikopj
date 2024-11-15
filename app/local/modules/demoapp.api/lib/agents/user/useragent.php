<?php

namespace NaturaSiberica\Api\Agents\User;

use CAgent;
use NaturaSiberica\Api\Entities\User\PhoneAuthTable;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Tools\Settings\Options;

class UserAgent
{
    public static function unblock(int $userId): string
    {
        $userRepository = new UserRepository();
        $userRepository->unblockUser($userId);
        $userRepository->resetLoginAttempts($userId);

        $agent = sprintf('%s(%d);', __METHOD__, $userId);

        CAgent::RemoveAgent($agent, ModuleInterface::MODULE_ID);

        return '';
    }

    public static function unblockPhone(string $phoneNumber): string
    {
        PhoneAuthTable::reset($phoneNumber);

        $agent = sprintf('%s(%d);', __METHOD__, $phoneNumber);
        CAgent::RemoveAgent($agent, ModuleInterface::MODULE_ID);

        return '';
    }
}
