<?php

namespace App\Http\Controllers;

use App\Jobs\WantFinishWaterTasks;
use App\Services\WaterService;
use App\Tool\PayTool;
use App\Tool\ValidationHelper;
use App\Tool\WxPay\WxPayApi;
use App\Tool\WxPay\WxPayConfig;
use App\Tool\WxPay\WxPayNotify;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaterController extends Controller
{
    //
    private $waterService;

    public function __construct(WaterService $waterService)
    {
        $this->waterService = $waterService;
    }

    public function buyWater(Request $request)
    {
        $user = $request->user;
        $rules = [
            'attributes.apartment' => 'required',
            'attributes.address' => 'required',
            'attributes.send' => 'required',
            'cards' => 'required'
        ];
        $res = ValidationHelper::validateCheck($request->input(), $rules);
        if ($res->fails()) {
            return response()->json([
                'code' => 2001,
                'message' => $res->errors()
            ]);
        }
        $data = [
            'cards' => $request->input('cards'),
            'attributes' => $request->input('attributes'),
            'user_id' => $user->id
        ];
        if ($data['cards']['1'] < 1) {
            return response()->json([
                'code' => 2001,
                'message' => '至少一张卡片'
            ]);
        }
        $pre_pay = $this->waterService->createWaterOrder($data);
        if ($pre_pay == 2003) {
            return response()->json([
                'code' => 2003,
                'message' => '卡片不足'
            ]);
        }
        if ($pre_pay == 2004) {
            return response()->json([
                'code' => 2004,
                'message' => '未知错误'
            ]);
        }
        if ($pre_pay == 2005) {
            return response()->json([
                'code' => 2005,
                'message' => '订单生成失败'
            ]);
        }
        if ($pre_pay['return_code'] != 'SUCCESS') {
            return response()->json([
                'code' => 901,
                'message' => $pre_pay['return_msg']
            ]);
        }
        $payData = [];
        $payData['appid'] = WxPayConfig::APPID;
        $payData['partnerid'] = WxPayConfig::MCHID;
        $payData['prepayid'] = $pre_pay['prepay_id'];
        $payData['package'] = 'Sign=WXPay';
        $payData['noncestr'] = WxPayApi::getNonceStr();
        $payData['timestamp'] = (string)Carbon::parse(Carbon::now())->timestamp;
        $payData['sign'] = PayTool::MakeSign($payData);
        $payData['taskId'] = $pre_pay['taskId'];
        return response()->json([
            'code' => 2000,
            'data' => $payData
        ]);
    }

    public function showWaters(Request $request)
    {
        $user = $request->user;
        $orders = $this->waterService->getOrders($user->role);
        return response()->json([
            'code' => 2000,
            'data' => $orders
        ]);
    }

    public function acceptTask(Request $request)
    {
        $user = $request->user;
        if ($user->role != 'water4' && $user->role != 'water6') {
            return response()->json([
                'code' => 2002,
                'message' => '不是送水者，没有权限'
            ]);
        }
        $rule = [
            'taskArray' => 'required'
        ];
        $res = ValidationHelper::validateCheck($request->input(), $rule);
        if ($res->fails()) {
            return response()->json([
                'code' => 2001,
                'message' => $res->errors()
            ]);
        }
        $taskArray = $request->input('taskArray');
        $this->waterService->acceptOrder($taskArray, $user->id);
        return response()->json([
            'code' => 2000,
            'message' => '任务接取成功'
        ]);
    }

    public function notify()
    {
        $notify = new WaterNotify();
        $notify->Handle();
    }

    public function finishTask($waterId, Request $request)
    {
        $user = $request->user;
        /*if (!$this->waterService->isWaterMaster($waterId,$user->id)){
            return response()->json([
                'code' => 2003,
                'message'=>'不是水任务所有者，不能主动完成任务'
            ]);
        }*/
        if ($user->role != 'water4' && $user->role != 'water6')
            return response()->json([
                'code' => 2003,
                'message' => '不是送水员，不能完成任务'
            ]);
        $this->waterService->finishOrder($waterId);
        return response()->json([
            'code' => 2000,
            'message' => '任务完成'
        ]);
    }

    public function rejectFinishTask($waterId, Request $request)
    {
        $user = $request->user;
        if (!$this->waterService->isWaterMaster($waterId, $user->id)) {
            return response()->json([
                'code' => 2003,
                'message' => '不是水任务所有者，不能拒绝完成任务'
            ]);
        }
        $this->waterService->rejectFinishOrder($waterId);
        return response()->json([
            'code' => 2000,
            'message' => '任务已拒绝完成'
        ]);
    }

    public function applyFinishTask(Request $request)
    {
        $rule = [
            'waters' => 'required'
        ];
        $res = ValidationHelper::validateCheck($request->input(), $rule);
        if ($res->fails()) {
            return response()->json([
                'code' => 2001,
                'message' => $res->errors()
            ]);
        }
        $waters = $request->input('$waters');
        $this->waterService->wantFinishOrder($waters);
        $job = (new WantFinishWaterTasks($waters, $this->waterService))
            ->delay(Carbon::now()->addDay(1));
        dispatch($job);
        return response()->json([
            'code' => 2000,
            'message' => '申请完成成功'
        ]);
    }

    public function getWaterPayStatus($waterId)
    {
        $status = $this->waterService->getWaterPayStatus($waterId);
        return response()->json([
            'code' => 2000,
            'data' => $status
        ]);
    }

    public function refundNotify()
    {
        $notify = new WaterRefundNotify();
        $notify->Handle(false,true);
    }

    public function returnCards($waterId)
    {
        $res = $this->waterService->returnCards($waterId);
        if ($res) {
            return response()->json([
                'code' => 2000,
                'message' => "卡片已退回"
            ]);
        }
        return response()->json([
            'code' => 2007,
            'message' => "退换失败"
        ]);
    }
    public function closeOrder($waterId,Request $request){
        $user=$request->user;
        if (!$this->waterService->isWaterMaster($waterId,$user->id)){
            return response()->json([
                'code'=> 2003,
                'message' => '不是任务所有者不可以申请退款'
            ]);
        }
        if ($this->waterService->closeOrder($waterId)){
            return response()->json([
                'code' => 2000,
                'message'=>'退款成功,请等待结果'
            ]);
        }else{
            return response()->json([
                'code' => 2018,
                'message' => '不符合退款条件'
            ]);
        }
    }
}

class WaterNotify extends WxPayNotify
{
    // $data:XML转换来的数组
    public function NotifyProcess($data, &$msg)
    {
        $order_out_trade = $data['out_trade_no'];
        $pay_result = $data['result_code'] == 'SUCCESS' ? 1 : -1;
        WaterService::payForWater($order_out_trade, $pay_result);
        return true;
    }
}

class WaterRefundNotify extends WxPayNotify
{
    // TODO 异常处理
    public function NotifyProcess($data, &$msg)
    {
        try {
            $req_info = $data['req_info'];
            $req_info = base64_decode($req_info);
            $mch_key = md5(WxPayConfig::KEY);
            $req_info = openssl_decrypt($req_info, 'AES-256-ECB', $mch_key,OPENSSL_CIPHER_AES_256_CBC);
            $req_info =preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/","",$req_info);
            $req_info = PayTool::xmlToArray($req_info);
            if ($req_info['refund_status']=='SUCCESS'){
                DB::table('tasks')->where('out_trade_no',$req_info['out_trade_no'])->delete();
            }
            }catch (\Exception $exception){
            throw $exception;
        }
        return true;
    }
}
