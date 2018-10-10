<?php

namespace App\Services;

use App\Tool\PayTool;
use App\Tool\WxPay\WxPayApi;
use App\Tool\WxPay\WxPayConfig;
use App\Tool\WxPay\WxPayRefund;
use App\Tool\WxPay\WxPayToBank;
use App\Tool\WxPay\WxPayUnifiedOrder;

class  PayService{

    public function makePayOrder(string $payOutTradeNo,string $payBody,int $totalFee,string $tradeType,string $notify_url){
        $wxPayData=new WxPayUnifiedOrder();
        $wxPayData->SetOut_trade_no($payOutTradeNo);
        $wxPayData->SetBody($payBody);
        $wxPayData->SetTotal_fee($totalFee);
        $wxPayData->SetTrade_type($tradeType);
        $wxPayData->SetNotify_url($notify_url);
        $result=WxPayApi::unifiedOrder($wxPayData);
        return $result;
    }

    public function makeMoneyComeTrue(){
        //TODO 提现
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

    public static function makeMoneyComeBack($out_trade_no,$out_refund_no,$total_fee,$refund_fee,$url){

        $refund=new WxPayRefund();
        $refund->SetOut_trade_no($out_trade_no);
        $refund->SetOut_refund_no($out_refund_no);
        $refund->SetTotal_fee($total_fee);
        $refund->SetRefund_fee($refund_fee);
        $refund->SetNotify_url($url);
        $res=WxPayApi::refund($refund);
        return $res;
    }
}