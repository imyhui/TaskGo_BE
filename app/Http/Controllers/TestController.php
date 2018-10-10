<?php

namespace App\Http\Controllers;

use App\Services\PayService;
use App\Tool\PayTool;
use App\Tool\WxPay\WxPayApi;
use App\Tool\WxPay\WxPayConfig;
use App\Tool\WxPay\WxPayGetRsa;
use App\Tool\WxPay\WxPayToBank;
use Illuminate\Http\Request;
class TestController extends Controller
{
    //
//    private $wxData;
//    public function __construct(WxPayGetRsa $wxPayGetRsa)
//    {
//        $this->wxData=$wxPayGetRsa;
//    }

    public function testGetRsa(){
        $wx =new WxPayGetRsa();
        $res = WxPayApi::getRsa($wx);
        dd($res);
    }
    public function testToBank(){
        $str='123456789';
        dd((int) ((int)$str * 0.999));
        dd((int)substr($str,0,strlen($str)-3));
        $wx=new WxPayToBank();
        $pub_key =openssl_get_publickey(file_get_contents(WxPayConfig::PUBLIC_PEM_PATH));
        $name='邢鼎威';
        $bankId='6217855000045787181';
        openssl_public_encrypt($name,$name,$pub_key,OPENSSL_PKCS1_OAEP_PADDING);
        openssl_public_encrypt($bankId,$bankId,$pub_key,OPENSSL_PKCS1_OAEP_PADDING);
        $name=base64_encode($name);
        $bankId=base64_encode($bankId);
        $wx->setBankCode('1026');
        $wx->setEncTrueName($name);
        $wx->setEncBankNo($bankId);
        $wx->setAmount(1);
        $res=WxPayApi::mmPayToBank($wx);

    }
    public function testReFund(){
        PayService::makeMoneyComeBack('1526130282KgCy977D',PayTool::makeOutTradeNo(),1,1,'http://baidu.com');
    }
}
