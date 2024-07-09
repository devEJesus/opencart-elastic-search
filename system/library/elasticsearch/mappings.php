<?php

namespace Opencart\System\Library\Elasticsearch;

trait Mappings
{
    protected $sort_order_maps =  [
        "p.sort_order"  => "product_id",
        "pd.name"       => "name.enum",
        "p.price"       => "price",
        "p.model"       => "model.enum",
    ];

    public static function map_products(array $products)
    {
        $map = [];
        foreach ($products as $product) {
            $map[] = self::map_product($product);
        }
        return $map;
    }

    public static function map_product(array $product): array
    {
        return [
            'product_id'        => $product['_id'],
            'thumb'             => $product['_source']['image'],
            'name'              => $product['_source']['name'],
            'description'       => $product['_source']['description'] ?? '',
            'price'             => $product['_source']['price'],
            'image'             => $product['_source']['image'] ?? false,
            'tax_class_id'      => $product['_source']['tax_class_id'] ?? 1,
            'special'           => $product['_source']['special'] ?? false,
            'tax'               => $product['_source']['tax'] ?? false,
            'minimum'           => $product['_source']['minimum'] ?? 1,
            'rating'            => $product['_source']['rating'] ?? false,
            'href'              => $product['_source']['href'] ?? ''
        ];
    }
}
