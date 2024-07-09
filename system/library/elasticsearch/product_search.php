<?php

namespace Opencart\System\Library\Elasticsearch;

class ProductSearch extends Client
{
    /**
     * Search from products in elastic search.
     * Return false if the call gone wrong
     * 
     * @param array $filter
     * @return array|false
     */
    public function search_products(array $filter): array|false
    {
        $result = $this->search($filter);

        if (isset($result['error'])) {
            echo 'Elasticsearch error: ' . $result['error']['reason'];
            return false;
        }

        return self::map_products($result['hits']['hits']);
    }
}
