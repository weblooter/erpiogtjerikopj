<?php

namespace NaturaSiberica\Api\Events\Handlers\Sale;

use Exception;
use Mindbox\Helper;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\MindboxServiceInterface;
use NaturaSiberica\Api\Services\Mindbox\MindboxService;
use NaturaSiberica\Api\Tools\Settings\Options;

class OrderHandler
{
    private MindboxServiceInterface $mindboxService;

    public function __construct()
    {
        $this->mindboxService = new MindboxService();
    }

    /**
     * @param int $orderId
     * @param     $statusId
     *
     * @return bool
     * @throws Exception
     */
    public function updateOrderStatus(int $orderId, $statusId): bool
    {
        if (! Helper::isMindboxOrder($orderId) || ! Options::isEnabledSendDataInMindbox()) {
            return false;
        }

        $order = [
            'ids' => [
                'websiteId' => $orderId,
            ],
        ];

        $this->mindboxService->addDataToRequestBody('orderLinesStatus', $statusId);
        $this->mindboxService->addDataToRequestBody('order', $order);
        $this->mindboxService->prepareDto();

        $client = $this->mindboxService->getMindboxClient();
        $client->prepareRequest('POST', $this->mindboxService->getOperation('updateOrderStatus'), $this->mindboxService->getDto());
        $response = $client->sendRequest()->getBody();

        return $response['status'] === 'Success';
    }
}
