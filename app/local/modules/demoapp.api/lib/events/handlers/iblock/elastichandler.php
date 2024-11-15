<?php

namespace NaturaSiberica\Api\Events\Handlers\Iblock;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
use NaturaSiberica\Api\ElasticSearch\ElasticSearchService;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class ElasticHandler implements ConstantEntityInterface
{
    use InfoBlockTrait;
    private ElasticSearchService $service;
    private Client               $client;

    public function __construct()
    {
        $this->service = new ElasticSearchService();
        $this->client  = $this->service->getClient();
    }

    public function deleteProduct(array &$fields): bool
    {
        $index = $this->getIblockCodeById($fields['IBLOCK_ID']);
        $id = (int)$fields['ID'];

        if ($this->hasElement($index, $id)) {
            $result = $this->client->delete([
                'index' => $index,
                'id' => $id
            ])->asObject();

            return $result->result === 'deleted';
        }

        return false;
    }

    /**
     * @param string $index
     * @param int    $id
     *
     * @return Elasticsearch|Promise
     *
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function raw(string $index, int $id)
    {
        return $this->client->search([
            'index' => $index,
            'body'  => [
                'query' => [
                    'term' => ['id' => $id]
                ],
            ],
        ]);
    }

    public function hasElement(string $index, int $id): bool
    {
        $element = $this->raw($index, $id)->asObject();
        return (bool)$element->hits->hits;
    }
}
