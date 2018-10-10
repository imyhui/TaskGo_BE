<?php

namespace App\Services;

use App\Tool\PayTool;
use App\Tool\ValidationHelper;
use App\Tool\WxPay\WxPayException;
use Illuminate\Support\Facades\DB;

class WaterService
{

    private $baseService;
    private $payService;
    private $cardService;
    public function __construct(BaseTaskService $baseTaskService, PayService $payService,CardService $cardService)
    {
        $this->baseService = $baseTaskService;
        $this->payService = $payService;
        $this->cardService = $cardService;
    }

    public function createWaterOrder($orderInfo)
    {
        $orderInfo['type'] = 'water';
        $orderInfo['attributes']['pay_status'] = 0;
        $orderInfo['attributes']['status'] = 0;
        $orderInfo['attributes']['fee'] = 800;
        //根据是否自提设置水价格
        if ($orderInfo['attributes']['send'] == 1) {
            $orderInfo['attributes']['fee'] = 900;
        }
        $fee = $orderInfo['attributes']['fee'];
        DB::beginTransaction();
            $no = PayTool::makeOutTradeNo();
            $orderInfo['out_trade_no'] = $no;
            if (!$this->cardService->removeUserKindsCard($orderInfo['user_id'],json_decode(json_encode($orderInfo['cards']),true))){
                return 2003;
            }
            $waterId = $this->baseService->createTask($orderInfo);
            if (!$waterId){
                return 2003;
            }
            try {
                $result = $this->payService->makePayOrder($no, "TaskGo-送水", $fee, "APP", "http://taskgobe.sealbaby.cn/pay/water/notify");
                if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                    DB::table('tasks')->where('id', $waterId)->update([
                        'pre_pay' => $result['prepay_id']
                    ]);
                    $result['taskId']=$waterId;
                }
            }
            catch (\Exception $e){
                $result =2005;
                DB::rollback();
            }

        DB::commit();
        //返回统一支付订单结果
        return $result;

    }

    // 接取任务，根据user角色来决定apartment是校六还是校四
    public function acceptOrder(array $taskArray, $userId)
    {
        $users=$this->getTaskUserArray($taskArray);
        DB::transaction(function ()use ($users,$taskArray,$userId){
            DB::table('tasks')->whereIn('id', $taskArray)->where([
                ['attributes->pay_status','=',1],
                ['attributes->status','=',0]
            ])->update([
                'attributes->status' => 1,
                'attributes->acceptor' => $userId
            ]);
            foreach ($users as $user){
                MessageService::sendMessage($userId,$user,'你的水订单已被接取！请注意查看');
            }
        });
    }
    public function getTaskUserArray(array $taskArray){
        $users=DB::table('tasks')->whereIn('id',$taskArray)->where([
            ['attributes->pay_status','=',1],
            ['attributes->status','=',0]
        ])->pluck('user_id')->toArray();
        return $users;
    }

    // 申请完成任务，使任务进入队列状态
    public function wantFinishOrder(array $waters)
    {
        DB::table('tasks')->whereIn('id', $waters)->where([
            ['attributes->status','=',1]
        ])->update([
            'attributes->status' => 2
        ]);
    }

    // 用户主动或者队列任务完成
    public function finishOrder($orderId)
    {
        $orderInfo = $this->getWaterInfo($orderId);
        DB::transaction(function () use ($orderInfo) {
            $num=DB::table('tasks')->where([['id', $orderInfo->id],['attributes->status','<',3]])->update([
                'attributes->status' => 3
            ]);
            if ($num==1) {
                DB::table('users')->where('id', $orderInfo->attributes->acceptor)->increment('balance', $orderInfo->attributes->fee);
                $this->cardService->addUserKindsCard($orderInfo->attributes->acceptor,$orderInfo->cards);
                MessageService::sendMessage($orderInfo->attributes->acceptor,$orderInfo->user_id,'你的水任务已被完成！请注意查看');
            }});
    }

    // 用户拒绝了任务的完成
    public function rejectFinishOrder($order_id)
    {
        $orderInfo = $this->getWaterInfo($order_id);
        DB::transaction(function () use ($orderInfo) {
            $num=DB::table('tasks')->where([['id', $orderInfo->id],['attributes->status','<',3]])->update([
                'attributes->status' => 4
            ]);
            if ($num==1){
                DB::table('users')->where('id', $orderInfo->user_id)->increment('balance', $orderInfo->attributes->fee);
                $this->cardService->addUserKindsCard($orderInfo->user_id,$orderInfo->cards);
            }
        });
    }
    //获取水任务信息，attributes已decode
    public function getWaterInfo($waterId)
    {
        $waterInfo = DB::table('tasks')->where('id', $waterId)->first();
        $waterInfo->attributes = json_decode($waterInfo->attributes);
        $waterInfo->cards = json_decode($waterInfo->cards,true);
        return $waterInfo;
    }

    // 用户个人中心获取自己的水订单
    public function getMyOrders()
    {
        //TODO 暂时不需要
    }

    // 送水模块展示所有用户的订单列表，并给送水者用来接受订单
    public function getOrders($userRole)
    {
        switch ($userRole) {
            case 'user':
                $Orders = DB::table('tasks')->join('users','tasks.user_id','=','users.id')->where([
                    ['tasks.type', '=', 'water'],
                    ['tasks.attributes->pay_status', '=', 1],
                    ['tasks.attributes->status','=',0]
                ])->orderBy('tasks.created_at', 'desc')->select('tasks.*','users.name','users.avatar')->paginate(20);
                break;
            case 'water4':
                $Orders = DB::table('tasks')->join('users','tasks.user_id','=','users.id')->where([
                    ['tasks.type', '=', 'water'],
                    ['tasks.attributes->pay_status', '=', 1],
                    ['tasks.attributes->apartment', '=', '4'],
                    ['tasks.attributes->status','=',0]
                ])->orderBy('tasks.created_at', 'desc')->select('tasks.*','users.name','users.avatar')->paginate(20);
                break;
            case 'water6':
                $Orders = DB::table('tasks')->join('users','tasks.user_id','=','users.id')->where([
                    ['tasks.type', '=', 'water'],
                    ['tasks.attributes->pay_status', '=', 1],
                    ['tasks.attributes->apartment', '=', '6'],
                    ['tasks.attributes->status','=',0]
                ])->orderBy('tasks.created_at', 'desc')->select('tasks.*','users.name','users.avatar')->paginate(20);
                break;
        }
        if (!isset($Orders)) {
            $Orders = DB::table('tasks')->join('users','tasks.user_id','=','users.id')->where([
                ['tasks.type', '=', 'water'],
                ['tasks.attributes->pay_status', '=', 1],
                ['tasks.attributes->status','=',0]
            ])->orderBy('tasks.created_at', 'desc')->select('tasks.*','users.name','users.avatar')->paginate(20);

        }
        foreach ($Orders as $item) {
            $item->attributes = json_decode($item->attributes);
            //$item->cards = json_decode($item->cards);
        }
        return $Orders;
    }
    // 订单支付失败，申请退换卡片
    public function returnCards(int $waterId){
        $orderInfo=$this->getWaterInfo($waterId);
        if ($orderInfo->attributes->pay_status == 0||$orderInfo->attributes->pay_status == -1){
            DB::transaction(function () use ($orderInfo,$waterId) {
                $this->cardService->addUserKindsCard($orderInfo->user_id, $orderInfo->cards);
                DB::table('tasks')->where('id', $waterId)->delete();
            });
            return true;
        }
        else{
            return false;
        }
    }

    public static function payForWater($outTradeNo, $payResult)
    {
        DB::table('tasks')->where('out_trade_no',$outTradeNo)->update([
            'attributes->pay_status' => $payResult
        ]);
    }
    // 判断用户是水任务的发起者与否
    public function isWaterMaster($waterId, $user_id)
    {
        $userId = DB::table('tasks')->where('id', $waterId)->value('user_id');
        if ($userId == $user_id) {
            return true;
        }
        return false;
    }
    public function getWaterPayStatus($waterId){
        $status=DB::table('tasks')->where('id',$waterId)->value('attributes->pay_status');
        return $status;
    }
    // 未被接取，用户主动撤销任务
    public function closeOrder($waterId){
        $orderInfo=$this->getWaterInfo($waterId);
        if (!$orderInfo->attributes->status == 0){
            return false;
        }
        if ($orderInfo->attributes->pay_status == 1||$orderInfo->attributes->pay_status == -2){
            DB::transaction(function () use ($orderInfo,$waterId) {
                if ($orderInfo->attributes->pay_status == 1)
                    $this->cardService->addUserKindsCard($orderInfo->user_id, $orderInfo->cards);
                $this->payService::makeMoneyComeBack($orderInfo->out_trade_no,PayTool::makeOutTradeNo(),$orderInfo->attributes->fee,$orderInfo->attributes->fee,"http://taskgobe.sealbaby.cn/refund/water/".$waterId );
                DB::table('tasks')->where('id', $waterId)->update([
                    'attributes->pay_status' => -2
                ]);
            });
            return true;
        }
        else{
            return false;
        }
    }

}