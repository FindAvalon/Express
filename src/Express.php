<?php

namespace Longway\Express;

class Express
{
    const GET_SELLER_URL = 'https://www.kuaidi100.com/all/';

    const SELECT_URL = 'https://www.kuaidi100.com/query';

    protected $timeout;

    protected $sellers = [];
    protected $sellerLinks = [];

    public function __construct($timeout = 600)
    {
        $this->timeout = $timeout;
    }

    public function select($seller, $postId)
    {

        if ( count($this->sellers) == 0  ) {
            $this->getSeller();
        }

        $code = $this->getCompanyCode($seller);

        $data = [
            'type'      => $code,
            'postid'    => $postId,
            'id'        => 1,
            'valicode'  => '',
            'temp'      => mt_rand(0, time()) / time()
        ];

        $url = self::SELECT_URL.'?'.http_build_query($data);

        $result = $this->curl($url);

        $result = json_decode($result, true);

        if (isset($result['message']) && isset($result['data']) && $result['message'] == 'ok') {
            return $result['data'];
        }
        throw new \Exception($result['message']);

    }

    private function curl($url)
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $result = curl_exec($ch);

        return $result;
    }

    public function getSeller()
    {
        //获取原始数据
        $result = $this->curl(self::GET_SELLER_URL);

        $startSign = 'href="';
        $endSign = '"';


        //获取有效数据列表
        $startIndex = strpos($result, 'column-1 column-list');
        $endIndex = strpos($result, '<div class="clear"></div>');
        $result = substr($result, $startIndex, $endIndex - $startIndex);
        preg_match_all('/<a[\w\W]+<\/a>/U', $result, $typeList);

        $data = [];
        $links = [];
        foreach ($typeList[0] as $k => $v) {

            if ( $startIndex = strpos($v, $startSign) ) {
                $startIndex += strlen($startSign);

                $endIndex = strpos($v, $endSign, $startIndex);

                $href = substr($v, $startIndex, $endIndex - $startIndex);

                $keyStartIndex = strripos($href, '/') + 1;
                $keyEndIndex = strripos($href, '.');

                $key = substr($href, $keyStartIndex, $keyEndIndex - $keyStartIndex);

                $valueStartIndex = strpos($v, '>') + 1;
                $valueEndIndex = strpos($v, '<', $valueStartIndex);

                $value = substr($v, $valueStartIndex, $valueEndIndex - $valueStartIndex);

                $data[$key] = $value;
                $links[] = [
                    'name' => $value,
                    'link' => $href
                ];
            }
        }
        $this->sellerLinks = $links;

        return array_values($data);
    }

    private function sellerCodeExists($seller)
    {
        if ( $code = array_search($seller, $this->sellers) ) {
            return $code;
        }
        return false;
    }

    public function getCompanyCode($seller)
    {
        if ( $code = $this->sellerCodeExists($seller) ) {
            return $code;
        }


        $startSign = 'id="companyCode"';
        $endSign = "<font";

        $result = array_filter($this->sellerLinks, function ($item) use ($seller) {
            return $item['name'] == $seller;
        });

        if ( count($result) != 1 ) {
            throw new \Exception("不存在指定seller");
        }

        $link = array_pop($result)['link'];

        $result = $this->curl($link);

        preg_match("/{$startSign}[\w\W]+{$endSign}/", $result, $companyCodeHtml);

        if ( count($companyCodeHtml) == 0 ) {
            throw new \Exception("不存在指定seller");
        }
        $companyCodeHtml = $companyCodeHtml[0];
        // var_dump($result);
        if ( strlen($companyCodeHtml) == 0 ) {
            throw new \Exception("不存在指定seller");
        }

        if ( $startIndex = strpos($companyCodeHtml, 'value="') ) {
            $startIndex += strlen('value="');
            $endIndex = strpos($companyCodeHtml, '"', $startIndex);
            if ( $code = substr($companyCodeHtml, $startIndex, $endIndex - $startIndex) ) {
                $this->sellers[$code] = $seller;
                return $code;
            }

        }
        throw new \Exception("不存在指定seller");
    }
}