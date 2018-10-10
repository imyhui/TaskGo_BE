<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private $cardService;

    public function __construct(CardService $cardService)
    {
        $this->cardService = $cardService;
    }

    public function getUserId($mobile)
    {
        $userId = DB::table('users')->where('mobile', $mobile)->value('id');
        return $userId;
    }

    public function isUserExist($mobile)
    {
        $userId = $this->getUserId($mobile);
        if ($userId > 0)
            return true;
        else
            return false;
    }

    /*
     * fixme register and login
     */
    public function register($userInfo)
    {
        if ($this->isUserExist($userInfo['mobile']))
            return -1;
        $time = new Carbon();

        $userInfo['password'] = bcrypt($userInfo['password']);
        $str="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        $name='用户'.substr(str_shuffle($str),5,8);
        $userInfo = array_merge($userInfo, [
            'name' => $name, // 昵称默认为手机号
            'created_at' => $time,
            'updated_at' => $time
        ]);
        $userId = 0;
        DB::transaction(function () use ($userInfo, &$userId) {
            $userId = DB::table('users')->insertGetId($userInfo);
            $this->cardService->addUserCard($userId, 1, 3);
        });

        return $userId;
    }

    public function login($mobile, $password)
    {
        $user = DB::table('users')->where('mobile', $mobile)->first();
        if ($user == null)
            return -1;
        if (!Hash::check($password, $user->password))
            return 0;
        else
            return $user->id;
    }

    public function loginByWx($wxNumber)
    {
        $user = DB::table('users')->where('wechat_openid', $wxNumber)->first();
        if ($user == null)
            return -1;
        return $user->id;
    }

    /*
     * fixme password
     */
    public function resetPassword($userId, $oldPassword, $newPassword)
    {
        $user = DB::table('users')->where('id', $userId)->first();

        if (Hash::check($oldPassword, $user->password)) {
            $this->updateUserInfo($userId, [
                'password' => bcrypt($newPassword)
            ]);
            return true;
        } else
            return false;
    }

    public function forgotPassword($userId, $newPassword)
    {
        $this->updateUserInfo($userId, [
            'password' => bcrypt($newPassword)
        ]);
    }

    /*
     * fixme UserInfo
     */

    public function getUserInfo($userId)
    {
        $allInfo = DB::table('users')->where('id', $userId)->first();
        $guardeds = ['password', 'wechat_openid', 'created_at', 'updated_at'];

        if ($allInfo->wechat_openid != null)
            $allInfo->bindwx = '1';
        else
            $allInfo->bindwx = '0';

        foreach ($allInfo as $key => $value) {
            if (!in_array($key, $guardeds))
                $userInfo[$key] = $value;
        }

        $followCount = $this->getFollowCount($userId);
        $userInfo = array_merge($userInfo, $followCount);

        return $userInfo;
    }

    public function getSimpleUserInfo($userId)
    {
        $userInfo = DB::table('users')->where('id', $userId)->select('id', 'name', 'avatar')->first();
        return $userInfo;
    }

    public function updateUserInfo($userId, $userInfo)
    {
        $time = new Carbon();

        $userInfo = array_merge($userInfo, [
            'updated_at' => $time
        ]);
        DB::table('users')->where('id', $userId)->update($userInfo);
        return true;
    }

    public function addAuthInfo($userId, $authInfo)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if ($user->status == 1)
            return -1;
        $this->updateUserInfo($userId, [
            'id_pic_p' => $authInfo['id_pic_p'],
            'id_pic_n' => $authInfo['id_pic_n'],
            'true_name' => $authInfo['true_name']
        ]);
        return 1;
    }

    public function bindBankCard($userId, $bankType,$bankCard, $bankCardName)
    {
        $this->updateUserInfo($userId, [
            'bank_type'=>$bankType,
            'bank_card' => $bankCard,
            'bank_card_name' => $bankCardName
        ]);
    }

    public function bindWechat($userId, $wxNumber)
    {
        $this->updateUserInfo($userId, [
            'wechat_openid' => $wxNumber
        ]);
    }

    public function getUserInfoByMobile($mobile)
    {
        $userInfo = DB::table('users')->where('mobile', $mobile)->select('id', 'true_name', 'name', 'avatar')->first();
        return $userInfo;
    }

    public function getUserBankInfo($userId){
        $data=DB::table('users')->where('id',$userId)->select('bank_type','bank_card_name','bank_card','balance')->first();
        return $data;
    }

    /*
     * fixme Captcha
     */
    public function sendMessage($mobile)
    {
        $data = [
            'account' => '',
            'pswd' => '',
            'mobile' => $mobile
        ];

        $header = "【NEUQer】";
        $captcha = rand(1000, 9999);
        $msg = "您的验证码为" . $captcha . "，此验证码用于taskgo注册或忘记密码。";

        $newMsg = $header . $msg;
        $url = "http://zapi.253.com/msg/HttpBatchSendSM?" . http_build_query($data) . "&msg=" . $newMsg;
        $res = '' . $this->doCurlGetRequest($url);
        $code = explode(',', $res)[1];
        if ($code == 0) {
            $this->addCaptcha($mobile, $captcha);
            return true;
        } else {
            return false;
        }
    }

    public function doCurlGetRequest(string $url)
    {
        $con = curl_init($url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_TIMEOUT, 5);
        return curl_exec($con);
    }

    public function getCaptcha($mobile)
    {
        $captcha = DB::table('captchas')->where('mobile', $mobile)->value('captcha');
        return $captcha;
    }

    public function addCaptcha($mobile, $captcha)
    {
        $oldCaptcha = $this->getCaptcha($mobile);
        $time = new Carbon();
        if ($oldCaptcha == null) {
            DB::table('captchas')->insert([
                'mobile' => $mobile,
                'captcha' => $captcha,
                'created_at' => $time,
                'updated_at' => $time
            ]);
        } else {
            DB::table('captchas')->where('mobile', $mobile)->update([
                'captcha' => $captcha,
                'updated_at' => $time
            ]);
        }
    }

    public function checkCaptcha($mobile, $captcha)
    {
        if ($mobile == '' || $captcha == '')
            return -1;
        $DBcaptcha = $this->getCaptcha($mobile);
        if ($captcha != $DBcaptcha) {
            return 0;
        } else {
            return 1;
        }
    }

    /*
     * fixme Following and folloer
     */
    public function getFollowings($userId)
    {
        // 获取 我关注的人 列表
        // Who You’re Following
        $userList = DB::table('followers')->where('followers.follower_id', $userId)
            ->join('users', 'users.id', '=', 'followers.user_id')
            ->select('user_id', 'name', 'avatar')
            ->get();
        return $userList;
    }

    public function getFollowers($userId)
    {
        // 获取 关注我 列表
        //Your Followers
        $userList = DB::table('followers')->where('followers.user_id', $userId)
            ->join('users', 'users.id', '=', 'followers.follower_id')
            ->select('follower_id', 'name', 'avatar')
            ->get();;
        return $userList;
    }

    public function isFollowing($userId, $followerId)
    {
        $user = DB::table('followers')->where([
            ['user_id', '=', $userId],
            ['follower_id', '=', $followerId],
        ])->first();
        if ($user == null)
            return false;
        else
            return true;
    }

    public function follow($userId, $toFollowId)
    {
        if ($this->isFollowing($toFollowId, $userId))
            return false;
        else {
            DB::table('followers')->insert([
                'user_id' => $toFollowId,
                'follower_id' => $userId
            ]);
            return true;
        }
    }

    public function unFollow($userId, $toFollowId)
    {
        if (!$this->isFollowing($toFollowId, $userId))
            return false;
        else {
            DB::table('followers')->where([
                    ['user_id', '=', $toFollowId],
                    ['follower_id', '=', $userId]
                ]
            )->delete();
            return true;
        }
    }

    public function getFollowCount($userId)
    {
        $followersCount = count($this->getFollowers($userId));
        $followingsCount = count($this->getFollowings($userId));
        $followCount = [
            'followers_count' => $followersCount,
            'followings_count' => $followingsCount
        ];
        return $followCount;
    }

    public function addAdvice($userId, $content)
    {
        $time = new Carbon();
        DB::table('advice')->insert([
            'user_id' => $userId,
            'content' => $content,
            'created_at' => $time
        ]);
    }


}