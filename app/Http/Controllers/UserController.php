<?php

namespace App\Http\Controllers;

use App\Services\TokenService;
use App\Services\UserService;
use App\Tool\ValidationHelper;
use App\Tool\WxPay\WxPayApi;
use App\Tool\WxPay\WxPayConfig;
use App\Tool\WxPay\WxPayToBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private $userService;
    private $tokenService;

    public function __construct(UserService $userService, TokenService $tokenService)
    {
        $this->userService = $userService;
        $this->tokenService = $tokenService;
    }

    public function sendMessage(Request $request)
    {
        $res = $this->userService->sendMessage($request->mobile);
        if ($res)
            return response()->json([
                'code' => 1000,
                'message' => '验证码发送成功',
            ]);
        else
            return response()->json([
                'code' => 1003,
                'message' => '验证码发送失败',
            ]);
    }

    public function checkCaptcha(Request $request)
    {
        $res = $this->userService->checkCaptcha($request->mobile, $request->captcha);
        if ($res == -1)
            return response()->json([
                'code' => 1002,
                'message' => '未填写手机号或验证码'
            ]);
        if (!$res) {
            return response()->json([
                'code' => 1004,
                'message' => '验证码错误'
            ]);
        } else {
            return response()->json([
                'code' => 1000,
                'message' => '验证码正确'
            ]);
        }
    }

    public function register(Request $request)
    {
        $rules = [
            'mobile' => 'bail|required',
            'password' => 'required|min:6|max:20',
            'captcha' => 'required'
        ];

        $validator = ValidationHelper::validateCheck($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }

        $userInfo = ValidationHelper::getInputData($request, $rules);

        if (!$this->userService->checkCaptcha($userInfo['mobile'], $userInfo['captcha'])) {
            return response()->json([
                'code' => 1004,
                'message' => '验证码错误'
            ]);
        }
        unset($userInfo['captcha']);
        $userId = $this->userService->register($userInfo);

        if ($userId == -1) {
            return response()->json([
                'code' => 1006,
                'message' => '用户已注册'
            ]);
        } else if ($userId == 0) {
            return response()->json([
                'code' => 1012,
                'message' => '发生错误，请重试'
            ]);
        } else {
            $tokenStr = $this->tokenService->makeToken($userId);
            return response()->json([
                'code' => 1000,
                'message' => '注册成功',
                'data' => [
                    'user_id' => $userId,
                    'token' => $tokenStr
                ]
            ]);
        }
    }

    public function login(Request $request)
    {
        $rules = [
            'mobile' => 'required',
            'password' => 'required|min:6|max:20'
        ];

        $validator = ValidationHelper::validateCheck($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }
        $loginInfo = ValidationHelper::getInputData($request, $rules);
        $userId = $this->userService->login($loginInfo['mobile'], $loginInfo['password']);
        if ($userId == -1)
            return response()->json([
                'code' => 1005,
                'message' => '用户不存在'
            ]);
        else if ($userId == 0)
            return response()->json([
                'code' => 1007,
                'message' => '密码错误'
            ]);
        else {
            $tokenStr = $this->tokenService->makeToken($userId);
            return response()->json([
                'code' => 1000,
                'message' => '登陆成功',
                'data' => [
                    'user_id' => $userId,
                    'token' => $tokenStr
                ]
            ]);
        }
    }

    public function bingWx(Request $request)
    {
        $userId = $request->user->id;

        $rules = [
            'wx_number' => 'required',
        ];

        $validator = ValidationHelper::validateCheck($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }
        $this->userService->bindWechat($userId, $request->wx_number);
        return response()->json([
            'code' => 1000,
            'message' => '绑定微信成功'
        ]);
    }

    public function loginByWx(Request $request)
    {
        $rules = [
            'wx_number' => 'required',
        ];

        $validator = ValidationHelper::validateCheck($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }
        // fixme 根据微信open_id直接直接登录
        $userId = $this->userService->loginByWx($request->wx_number);
        if ($userId == -1)
            return response()->json([
                'code' => 1005,
                'message' => '用户不存在'
            ]);
        else {
            $tokenStr = $this->tokenService->makeToken($userId);
            return response()->json([
                'code' => 1000,
                'message' => '登陆成功',
                'data' => [
                    'user_id' => $userId,
                    'token' => $tokenStr
                ]
            ]);
        }
    }

    public function resetPassword(Request $request)
    {
        $rules = [
            'old_password' => 'required',
            'new_password' => 'required|string|min:6|max:20'
        ];

        $validator = ValidationHelper::validateCheck($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }
        $res = $this->userService->resetPassword($request->user->id, $request->old_password, $request->new_password);
        if (!$res)
            return response()->json([
                'code' => 1007,
                'message' => "原密码错误"
            ]);
        else
            return response()->json([
                'code' => 1000,
                'message' => "密码重置成功"
            ]);
    }

    public function forgotPassword(Request $request)
    {
        $rules = [
            'mobile' => 'required',
            'captcha' => 'required',
            'new_password' => 'required|string|min:6|max:20'
        ];
        $validator = ValidationHelper::validateCheck($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }
        if (!$this->userService->checkCaptcha($request->mobile, $request->captcha)) {
            return response()->json([
                'code' => 1004,
                'message' => '验证码错误'
            ]);
        }
        $userId = $this->userService->getUserId($request->mobile);
        $this->userService->forgotPassword($userId, $request->new_password);
        return response()->json([
            'code' => 1000,
            'message' => '密码重置成功'
        ]);
    }

    public function getUserInfo(Request $request)
    {
        $userId = $request->user->id;
        $userInfo = $this->userService->getUserInfo($userId);
        return response()->json([
            'code' => 1000,
            'message' => '请求成功',
            'data' => $userInfo
        ]);
    }

    public function getUserInfoById(Request $request)
    {
        // fixme 查看其它用户资料
        $nowUserId = $request->user->id;
        $userId = $request->user_id;
        $allUserInfo = $this->userService->getUserInfo($userId);
        $userInfo = [];
        $rules = ['name', 'avatar', 'sex', 'mobile', 'followers_count', 'followings_count'];
        foreach ($rules as $key) {
            $userInfo[$key] = $allUserInfo[$key];
        }
        $isFollowing = $this->userService->isFollowing($userId, $nowUserId);
        $userInfo['is_following'] = $isFollowing;
        return response()->json([
            'code' => 1000,
            'message' => '请求成功',
            'data' => $userInfo
        ]);
    }

    public function updateUserInfo(Request $request)
    {
        $rules = [
            'avatar' => 'required',
            'name' => 'required',
            'sex' => 'required',
        ];
        $validator = ValidationHelper::validateCheck($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }
        $userInfo = ValidationHelper::getInputData($request, $rules);
        $userId = $request->user->id;
        $this->userService->updateUserInfo($userId, $userInfo);
        return response()->json([
            'code' => 1000,
            'message' => '更新成功'
        ]);
    }

    public function addAuthInfo(Request $request)
    {
        $rules = [
            'id_pic_p' => 'required',
            'id_pic_n' => 'required',
            'true_name' => 'required'
        ];
        $validator = ValidationHelper::validateCheck($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }
        $data = ValidationHelper::getInputData($request, $rules);
        $userId = $request->user->id;
        $code = $this->userService->addAuthInfo($userId, $data);
        if ($code == -1) {
            return response()->json([
                'code' => 1008,
                'message' => '用户已认证无需重复认证'
            ]);
        } else {
            return response()->json([
                'code' => 1000,
                'message' => '更新成功'
            ]);
        }
    }

    public function bindBankCard(Request $request)
    {
        $rules = [
            'bankCard_type'=>'required',
            'bankCard_id' => 'required|min:15|max:20',
            'bankCard_userName' => 'required',
        ];
        $validator = ValidationHelper::validateCheck($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'code' => 1001,
                'message' => $validator->errors()
            ]);
        }
        $data = ValidationHelper::getInputData($request, $rules);
        $userId = $request->user->id;
        $this->userService->bindBankCard($userId, $data['bankCard_type'],$data['bankCard_id'], $data['bankCard_userName']);
        return response()->json([
            'code' => 1000,
            'message' => '绑定银行卡成功'
        ]);
    }

    public function getFollowers(Request $request)
    {
        // 获取 关注我 列表
        //Your Followers
        $userId = $request->user->id;
        $users = $this->userService->getFollowers($userId);
        return response()->json([
            'code' => 1000,
            'message' => '关注我的列表',
            'data' => $users
        ]);
    }

    public function getFollowings(Request $request)
    {
        // 获取 我关注的人 列表
        // Who You’re Following
        $userId = $request->user->id;
        $users = $this->userService->getFollowings($userId);
        return response()->json([
            'code' => 1000,
            'message' => '我的关注列表',
            'data' => $users
        ]);
    }

    public function followUser(Request $request)
    {
        $userId = $request->user->id;
        $toFollowId = $request->follower_id;
        if ($userId == $toFollowId) {
            return response()->json([
                'code' => 1009,
                'message' => '用户不能关注自己'
            ]);
        }
        if ($this->userService->follow($userId, $toFollowId)) {
            return response()->json([
                'code' => 1000,
                'message' => '关注成功'
            ]);
        } else {
            return response()->json([
                'code' => 1010,
                'message' => '已经关注该用户'
            ]);
        }
    }

    public function unFollowUser(Request $request)
    {
        $userId = $request->user->id;
        $toFollowId = $request->follower_id;

        if ($this->userService->unFollow($userId, $toFollowId)) {
            return response()->json([
                'code' => 1000,
                'message' => '取消关注成功'
            ]);
        } else {
            return response()->json([
                'code' => 1011,
                'message' => '未关注该用户'
            ]);
        }
    }

    public function addAdvice(Request $request)
    {
        $userId = $request->user->id;
        $rule = [
            'contents' => 'required'
        ];
        $res = ValidationHelper::validateCheck($request->all(),$rule);
        if($res->fails())
        {
            return response()->json([
                'code' => 1001,
                'message' => $res->errors()
            ]);
        }
        $this->userService->addAdvice($userId,$request->contents);
        return response()->json([
            'code' => 1000,
            'message' => '提交成功'
        ]);
    }

    public function refreshToken(Request $request)
    {
        /**
         * 每次打开app请求该接口获取新token
         */
        $tokenStr = $request->header('token');
        $newToken = $this->tokenService->refreshToken($tokenStr);
        return response()->json([
            'code' => 1000,
            'message' => '更新token成功',
            'data' =>  $newToken
        ]);
    }

    public function makeMoneyComeTrue(Request $request){
        $user=$request->user;
        $bankInfo=$this->userService->getUserBankInfo($user->id);
        if ($bankInfo->balance <=100){
            return response()->json([
                'code' => 1017,
                'message' => '你的余额还不够付手续费呢！'
            ]);
        }
        $wx=new WxPayToBank();
        $pub_key =openssl_get_publickey(file_get_contents(WxPayConfig::PUBLIC_PEM_PATH));
        $name=$bankInfo->bank_card_name;
        $bankId=$bankInfo->bank_card;
        openssl_public_encrypt($name,$name,$pub_key,OPENSSL_PKCS1_OAEP_PADDING);
        openssl_public_encrypt($bankId,$bankId,$pub_key,OPENSSL_PKCS1_OAEP_PADDING);
        $name=base64_encode($name);
        $bankId=base64_encode($bankId);
        $wx->setBankCode($bankInfo->bank_type);
        $wx->setEncTrueName($name);
        $wx->setEncBankNo($bankId);
        if ($bankInfo->balance<100000){
            $total_fee=$bankInfo->balance-100;
        }
        else{
            $total_fee=(int)((int)$bankInfo->balance * 0.999);
        }
        $wx->setAmount($total_fee);
        $res=WxPayApi::mmPayToBank($wx);
        if ($res['return_code']=='SUCCESS'&& $res['result_code']=='SUCCESS'&&$res['return_msg']=='OK'){
            DB::table('users')->where('id',$user->id)->update([
                'balance'=>0
            ]);
            return response()->json([
                'code' => 1000,
                'message' => '提现成功'
            ]);
        }
        else{
            return response()->json([
                'code'=> 1018,
                'message'=> '出现未知错误，请稍后重试'
            ]);
        }
    }
}
