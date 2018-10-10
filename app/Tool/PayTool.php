<?php

namespace App\Tool;

use App\Tool\WxPay\WxPayConfig;
use Carbon\Carbon;

class PayTool
{

    public static function makeOutTradeNo()
    {
        $num = ((string)Carbon::parse(Carbon::now())->timestamp) . str_random(8);
        return $num;
    }

    public static function MakeSign(array $data)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);

        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");

        $string = $buff;
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . WxPayConfig::KEY;
        //签名步骤三：MD5加密
        //dd($string);
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    // XML To Array
    public static function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
}