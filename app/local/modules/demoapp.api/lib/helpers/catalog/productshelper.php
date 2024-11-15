<?php

namespace NaturaSiberica\Api\Helpers\Catalog;

use NaturaSiberica\Api\Helpers\Thumbnailer\ImageResizeHelper;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Tools\Settings\Options;

class ProductsHelper
{
    public static function prepareListImagesForVersion(int $iblockId, array &$products): array
    {
        foreach ($products as &$product) {
            $images = [];

            /**
             * Если $product['image'] является массивом, значит, в нём уже лежит картинка с сервиса ресайза, и, соответственно, заново получать ссылку нет смысла
             */
            if (is_array($product['image'])) {
                continue;
            }

            $img     = ! empty($product['image']) ? ImageResizeHelper::getResizedImages($product['image'], 'image', 'list', $iblockId) : null;

            if (! empty($product['images'])) {
                foreach ($product['images'] as $productImage) {

                    /**
                     * Если $productImage является массивом, значит, в нём уже лежит картинка с сервиса ресайза, и, соответственно, заново получать ссылку нет смысла
                     */
                    if (is_array($productImage)) {
                        continue;
                    }

                    $image = ImageResizeHelper::getResizedImages($productImage, 'images', 'list', $iblockId);

                    if (! empty($image)) {
                        $images[] = $image;
                    }
                }
            }

            $product['images'] = $images ? : null;
            $product['image']  = $img;
        }

        return $products;
    }

    public static function prepareDetailImagesForVersion(int $iblockId, array &$product): array
    {
        /**
         * Если $product['image'] является массивом, значит, в нём уже лежит картинка с сервиса ресайза, и, соответственно, заново получать ссылку нет смысла
         */
       if (!is_array($product['image'])) {
           $productImg = ! empty($product['image']) ? ImageResizeHelper::getResizedImages($product['image'], 'image', 'detail', $iblockId) : null;
           $images     = [];

           if (! empty($product['images'])) {
               foreach ($product['images'] as $productImage) {

                   /**
                    * Если $productImage является массивом, значит, в нём уже лежит картинка с сервиса ресайза, и, соответственно, заново получать ссылку нет смысла
                    */
                   if (is_array($productImage)) {
                       continue;
                   }

                   $image = ImageResizeHelper::getResizedImages($productImage, 'images', 'detail', $iblockId);

                   if (! empty($image)) {
                       $images[] = $image;
                   }
               }
           }

           $product['images'] = $images ? : null;
           $product['image']  = $productImg;
       }

        return $product;
    }
}
