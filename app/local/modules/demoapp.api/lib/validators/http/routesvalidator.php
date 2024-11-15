<?php

namespace NaturaSiberica\Api\Validators\Http;

use Exception;
use NaturaSiberica\Api\Validators\User\UserValidator;
use Psr\Http\Message\ServerRequestInterface;

class RoutesValidator
{
    private ServerRequestInterface $request;

    private string $routeName;

    private ?int $userId = null;
    private ?int $fuserId = null;

    private array $validators = [
        'user' => [
            'logout',
            'profile.show',
            'profile.edit',
            'notifications.settings',
            'address.list',
            'address.show',
            'address.add',
            'address.edit',
            'address.delete',
            'payments.show',
            'order.list',
            'order.add',
            'order.show',
            'order.cancel',
            'favourites.show',
            'favourites.add',
            'favourites.delete',
            'favourites.clear',
            'email.change',
        ],
        'fuser' => [
            'register',
            'login',
            'order.add',
            'cart.show',
            'cart.update',
            'cart.delete',
            'shipment.show',
            'freeshipping.show'
        ]
    ];

    /**
     * @param ServerRequestInterface $request
     * @param int|null               $userId
     * @param int|null               $fuserId
     */
    public function __construct(ServerRequestInterface $request, ?int $userId, ?int $fuserId)
    {
        $this->extractRouteNameFromRequest($request);

        $this->request = $request;
        $this->userId  = $userId;
        $this->fuserId = $fuserId;
    }

    public function extractRouteNameFromRequest(ServerRequestInterface $request)
    {
        $this->routeName = $request->getAttribute('__route__')->getName();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateParameters()
    {
        if (in_array($this->routeName, $this->validators['user'])) {
            UserValidator::validateUser($this->userId);
        }

        if (in_array($this->routeName, $this->validators['fuser'])) {
            UserValidator::validateFuser($this->fuserId);
        }
    }

}
