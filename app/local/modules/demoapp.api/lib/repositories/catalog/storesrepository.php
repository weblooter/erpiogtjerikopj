<?php

namespace NaturaSiberica\Api\Repositories\Catalog;


use Bitrix\Catalog\StoreTable;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Collection;

class StoresRepository
{

    protected array $select = [
        'PRODUCT_ID',
        'AMOUNT',
        'STORE_TABLE.ID',
        'STORE_TABLE.TITLE',
        'STORE_TABLE.ISSUING_CENTER',
        'STORE_TABLE.SHIPPING_CENTER',
        'STORE_TABLE.UF_CITY'
    ];

    public function getStroesList(array $productIds = []): array
    {
        $result = [];
        $data = (new Query(StoreProductTable::getEntity()))
            ->setSelect($this->select)
            ->setFilter(['PRODUCT_ID' => $productIds, '!STORE_TABLE.UF_CITY' => false, 'STORE_TABLE.ACTIVE' => 'Y'])
            ->registerRuntimeField(new Reference('STORE_TABLE', StoreTable::getEntity(), Join::on('this.STORE_ID', 'ref.ID')))
            ->fetchCollection();
        foreach ($data as $item) {
            if($item->get('STORE_TABLE')->get('ISSUING_CENTER')) {
                if(!in_array($item->get('STORE_TABLE')->get('UF_CITY'), $result[$item->get('PRODUCT_ID')]['city_id_list'])) {
                    $result[$item->get('PRODUCT_ID')]['city_id_list'][] = $item->get('STORE_TABLE')->get('UF_CITY');
                }
                $result[$item->get('PRODUCT_ID')]['city_'.$item->get('STORE_TABLE')->get('UF_CITY')][] = [
                    'id' => $item->get('STORE_TABLE')->get('ID'),
                    'name' => $item->get('STORE_TABLE')->get('TITLE'),
                    'quantity' => $item->get('AMOUNT'),
                    'is_shop' => $item->get('STORE_TABLE')->get('ISSUING_CENTER'),
                    'is_warehouse' => $item->get('STORE_TABLE')->get('SHIPPING_CENTER'),
                ];
            }
            if($item->get('STORE_TABLE')->get('SHIPPING_CENTER')) {
                $result[$item->get('PRODUCT_ID')]['warehouses'][] = [
                    'id' => $item->get('STORE_TABLE')->get('ID'),
                    'name' => $item->get('STORE_TABLE')->get('TITLE'),
                    'quantity' => (int)$item->get('AMOUNT'),
                    'is_shop' => $item->get('STORE_TABLE')->get('ISSUING_CENTER'),
                    'is_warehouse' => $item->get('STORE_TABLE')->get('SHIPPING_CENTER'),
                ];
            }
        }
        return $result;
    }

}
