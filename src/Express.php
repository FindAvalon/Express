<?php

namespace Longway\Express;

class Express
{
    const GET_TYPE_URL = 'https://www.kuaidi100.com/all/';

    protected $timeout;

    public function __construct($timeout = 600)
    {
        $this->timeout = $timeout;
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

    public function getType()
    {
        //获取原始数据
        $result = $this->curl(self::GET_TYPE_URL);

        $startSign = 'href="';
        $endSign = '"';


        //获取有效数据列表
        $startIndex = strpos($result, 'column-1 column-list');
        $endIndex = strpos($result, '<div class="clear"></div>');
        $result = substr($result, $startIndex, $endIndex - $startIndex);
        preg_match_all('/<a[\w\W]+<\/a>/U', $result, $typeList);

        $data = [];
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
            }
        }

        return $data;
    }
}