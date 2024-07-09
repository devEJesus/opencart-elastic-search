<?php

namespace Opencart\System\Library\Elasticsearch;

class Client
{
    use Mappings;

    protected string $_host;
    protected string $_port;
    protected string $_key;
    protected string $_index;

    public function __construct()
    {
        //TODO: GET INFORMATION FROM SETTINGS TABLE
        $this->_host = "http://localhost";
        $this->_port = "9200";
        $this->_key = "";
        $this->_index = "opencart";
    }

    public function set_host(string $host): void
    {
        $this->_host = $host;
    }

    public function set_key(string $key): void
    {
        $this->_key = $key;
    }

    public function get_key(): string
    {
        return $this->_key;
    }

    public function set_index(string $index): void
    {
        $this->_index = $index;
    }

    private function get_conn_string(): string
    {
        return $this->_host . ":" . $this->_port;
    }


    /**
     * Perform a request to the Elasticsearch server.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array|bool
     * @throws \RuntimeException
     */
    protected function perform_request(string $method, string $endpoint, array $data = [])
    {
        $url = $this->get_conn_string() . '/' . $endpoint;

        $ch = curl_init($url);

        $jsonData = json_encode($data);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
            'Authorization: ApiKey ' . $this->_key
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
            return false;
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Search for products in Elasticsearch.
     *
     * @param array $filter
     * @return array|bool
     */
    protected function search(array $filter)
    {
        $endpoint = $this->_index . '/_search';

        $data = [
            'query' => [
                'bool' => [
                    'must'  => [
                        [
                            'range' => [
                                'stock_quantity' => [
                                    'gt' => 0
                                ]
                            ]
                        ]
                    ],

                ]
            ],
        ];

        // Add search term
        if (isset($filter['filter_search']))
            $data['query']['bool'] += $this->filter_search($filter['filter_search']);

        // Add category filter
        if (isset($filter['filter_category_id']))
            $data['query']['bool'] += $this->filter_category($filter['filter_category_id']);

        // Add pagination
        if (isset($filter['start']))
            $data += $this->filter_from($filter['start']);

        // Add limit
        if (isset($filter['limit']))
            $data += $this->filter_limit($filter['limit']);

        // Add sort search order
        if (isset($filter['sort']) && isset($filter['order']))
            $data += $this->sort_search($filter['sort'], $filter['order']);

        // Perform request and return 
        return $this->perform_request('POST', $endpoint, $data);
    }

    /**
     * Add category filter to the search
     * 
     * @param int $categoryId
     * @return array
     */
    private function filter_category(int $categoryId): array
    {
        return [
            'filter' => [
                [
                    'term' => [
                        'categories.id' => $categoryId
                    ]
                ]
            ]
        ];
    }

    /**
     * Add limit to the search
     * 
     * @param int $limit
     * @return array
     */
    private function filter_limit(int $limit): array
    {
        return ['size' => $limit];
    }

    /**
     * Add offset to the search
     * 
     * @param int $from
     * @return array 
     */
    private function filter_from(int $from): array
    {
        return ["from" => $from];
    }

    /**
     * Add a term to search to the request
     * 
     * @param string $searchTerm
     * @return array
     */
    private function filter_search(string $searchTerm): array
    {
        return [
            'should' => [
                [
                    'wildcard' => [
                        'name' => [
                            'value' => '*' . $searchTerm . '*',
                            'boost' => 2.0
                        ]
                    ]
                ],
                [
                    'wildcard' => [
                        'categories.name' => [
                            'value' =>  '*' . $searchTerm . '*',
                            'boost' => 1.0
                        ]
                    ]
                ],
                [
                    'wildcard' => [
                        'model' => [
                            'value' =>  '*' . $searchTerm . '*',
                            'boost' => 2.0
                        ]
                    ]
                ],
                [
                    'wildcard' => [
                        'sku' => [
                            'value' =>  '*' . $searchTerm . '*',
                            'boost' => 2.0
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * Add sort to the search
     * 
     * @param string $sort
     * @param string $order
     * @return array
     */
    private function sort_search(string $sort, string $order): array
    {
        return [
            "sort" => [
                [
                    $this->sort_order_maps[$sort] => [
                        "order" => $order
                    ]
                ]
            ]
        ];
    }
}
