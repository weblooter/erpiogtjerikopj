<?php

namespace NaturaSiberica\Api\Services\Mindbox\User;

use Bitrix\Main\Loader;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions\MindboxBadRequestException;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxConflictException;
use Mindbox\Exceptions\MindboxException;
use Mindbox\Exceptions\MindboxForbiddenException;
use Mindbox\Exceptions\MindboxNotFoundException;
use Mindbox\Exceptions\MindboxTooManyRequestsException;
use Mindbox\Exceptions\MindboxUnauthorizedException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Helper;
use NaturaSiberica\Api\DTO\User\UserDTO;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\User\ProfileServiceInterface;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Services\Mindbox\MindboxService;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

Loader::includeModule('catalog');

class ProfileService extends MindboxService implements ProfileServiceInterface, ConstantEntityInterface
{
    use HighloadBlockTrait;

    const POINT_OF_CONTACT_SMS   = 'SMS';
    const POINT_OF_CONTACT_PUSH  = 'Mobilepush';
    const POINT_OF_CONTACT_EMAIL = 'Email';

    private UserRepository $userRepository;

    private array $profileFields = [
        'firstName'  => 'name',
        'middleName' => 'secondName',
        'lastName'   => 'lastName',
        'birthDate'  => 'birthdate',
        'email'      => 'email',
        'sex'        => 'gender',
    ];

    private array $profileCustomFields = [
        'City'          => 'city',
        'maritalStatus' => 'maritalStatusValue',
        'favoriteStore' => 'favoriteStoreAddress',
    ];

    private array $notificationFields = [
        'subscribedToPush'  => self::POINT_OF_CONTACT_PUSH,
        'subscribedToSms'   => self::POINT_OF_CONTACT_SMS,
        'subscribedToEmail' => self::POINT_OF_CONTACT_EMAIL,
    ];

    /**
     * @param UserRepository $userRepository
     *
     * @throws MindboxException
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;

        parent::__construct();
    }

    public function findCustomer(UserDTO $userDto): array
    {
        $this->resetRequestBody();
        parent::addCustomerToRequestBody($userDto);
        $this->prepareDto(CustomerRequestDTO::class);
        $this->mindboxClient->prepareRequest('POST', $this->getOperation('getCustomerInfo'), $this->dto);
        $response = $this->getResponse($this->mindboxClient);

        return $response->getBody();
    }

    public function isMindboxCustomer(UserDTO $userDto): bool
    {
        $customer = $this->findCustomer($userDto);
        return $customer['customer']['processingStatus'] === self::RESPONSE_PROCESSING_STATUS_CUSTOMER_FOUND;
    }

    public function registerCustomer(UserDTO $userDto)
    {
        $this->resetRequestBody();
        parent::addCustomerToRequestBody($userDto);
        $this->prepareDto(CustomerRequestDTO::class);

        $this->mindboxClient->prepareRequest('POST', $this->getOperation('register'), $this->dto);
        $response = $this->getResponse($this->mindboxClient);

        return $response->getBody();
    }

    public function confirmPhone(UserDTO $userDto): array
    {
        $this->resetRequestBody();
        parent::addCustomerToRequestBody($userDto);
        $this->prepareDto(CustomerRequestDTO::class);

        $this->mindboxClient->prepareRequest('POST', $this->getOperation('confirmPhone'), $this->dto);
        $response = $this->getResponse($this->mindboxClient);

        return $response->getBody();
    }

    /**
     * @param UserDTO $userDto
     *
     * @return void
     * @throws MindboxUnauthorizedException
     * @throws MindboxUnavailableException
     * @throws MindboxBadRequestException
     * @throws MindboxClientException
     * @throws MindboxConflictException
     * @throws MindboxForbiddenException
     * @throws MindboxNotFoundException
     * @throws MindboxTooManyRequestsException
     */
    public function editProfile(UserDTO $userDto): array
    {
        $this->resetRequestBody();
        $this->addCustomerToRequestBody($userDto, true, true);
        $this->prepareDto(CustomerRequestDTO::class);

        $response = $this->mindbox->customer()->edit($this->dto, $this->getOperation('edit'), false)->sendRequest();
        return $response->getBody();
    }

    public function editNotifications(UserDTO $userDto): array
    {
        $this->resetRequestBody();
        $this->addCustomerToRequestBody($userDto, false, false, true);
        $this->prepareDto(CustomerRequestDTO::class);

        $response = $this->mindbox->customer()->edit($this->dto, $this->getOperation('edit'), false)->sendRequest();
        return $response->getBody();
    }

    /**
     * @param UserDTO $userDto
     * @param bool    $useProfileFields
     * @param bool    $useCustomFields
     * @param bool    $useSubscriptions
     *
     * @return void
     */
    public function addCustomerToRequestBody(UserDTO $userDto, bool $useProfileFields = false, bool $useCustomFields = false, bool $useSubscriptions = false): void
    {
        if ($userDto->mindboxId !== null) {
            $this->requestBody['ids'] = [
                'mindboxId' => $userDto->mindboxId,
            ];
        }

        $this->requestBody['mobilePhone'] = $userDto->phone;

        if ($useProfileFields) {
            foreach ($this->profileFields as $mbField => $bxField) {
                switch ($mbField) {
                    case 'birthDate':
                        $this->requestBody[$mbField] = Helper::formatDate($userDto->{$bxField});
                        break;
                    default:
                        if($bxField === 'secondName' && !$userDto->{$bxField}) {
                            $this->requestBody[$mbField] = '';
                        } else {
                            $this->requestBody[$mbField] = $userDto->{$bxField};
                        }
                        break;
                }
            }
        }

        if ($useCustomFields) {
            foreach ($this->profileCustomFields as $mbCustomField => $bxCustomField) {
                $this->requestBody['customFields'][$mbCustomField] = $userDto->{$bxCustomField};
            }
        }

        if ($useSubscriptions) {
            $subscribeSettings = $userDto->only('subscribedToEmail', 'subscribedToPush', 'subscribedToSms')->toArray();

            foreach ($this->notificationFields as $dtoField => $pointOfContact) {
                if ($subscribeSettings[$dtoField] === null) {
                    continue;
                }

                $this->requestBody['subscriptions'][] = [
                    'pointOfContact' => $pointOfContact,
                    'topic'          => Options::getMindboxSubscriptionsTopicName(),
                    'isSubscribed'   => $subscribeSettings[$dtoField],
                ];
            }
        }
    }

}
