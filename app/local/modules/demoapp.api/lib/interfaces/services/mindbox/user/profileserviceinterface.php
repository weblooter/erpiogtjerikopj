<?php

namespace NaturaSiberica\Api\Interfaces\Services\Mindbox\User;

use NaturaSiberica\Api\DTO\User\UserDTO;

interface ProfileServiceInterface
{
    const OPERATION_NAME_PHONE_CONFIRMATION             = 'Website.ManualPhoneConfirmation';
    const RESPONSE_PROCESSING_STATUS_PHONE_CONFIRMED    = 'MobilePhoneConfirmed';
    const RESPONSE_PROCESSING_STATUS_CUSTOMER_CREATED   = 'Created';
    const RESPONSE_PROCESSING_STATUS_CUSTOMER_FOUND     = 'Found';
    const RESPONSE_PROCESSING_STATUS_CUSTOMER_NOT_FOUND = 'NotFound';

    public function confirmPhone(UserDTO $userDto);

    public function editProfile(UserDTO $userDto): array;

    public function editNotifications(UserDTO $userDto): array;
}
