<?php
/**
 * Created by PhpStorm.
 * User: yz
 * Date: 18/4/27
 * Time: 下午4:15
 */

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TokenService
{
    private static $EXPIRE_TIME = 360; // 小时 -> 15天

    public function createToken($userId)
    {
        $tokenStr = md5(uniqid());
        $time = new Carbon();
        $outTime = new Carbon();
        $outTime->addHour(self::$EXPIRE_TIME);
        $data = [
            'user_id' => $userId,
            'token' => $tokenStr,
            'created_at' => $time,
            'updated_at' => $time,
            'expires_at' => $outTime
        ];

        DB::table('tokens')->insert($data);
        return $tokenStr;
    }

    private function updateToken($userId)
    {
        $time = new Carbon();
        $outTime = new Carbon();
        $outTime->addHour(self::$EXPIRE_TIME);
        $tokenStr = md5(uniqid());
        $data = [
            'token' => $tokenStr,
            'updated_at' => $time,
            'expires_at' => $outTime
        ];

        DB::table('tokens')->where('user_id', $userId)->update($data);
        return $tokenStr;
    }

    public function makeToken($userId)
    {
        $token = DB::table('tokens')->where('user_id', $userId)->first();

        if ($token == null) {
            return $this->createToken($userId);
        } else {
            return $this->updateToken($userId);
        }
    }

    public function deleteToken($userId)
    {
        DB::table('tokens')->where('user_id', $userId)->delete();
    }

    public function getToken($tokenStr)
    {
        return DB::table('tokens')->where('token', $tokenStr)->first();
    }

    public function verifyToken($tokenStr)
    {
        $res = $this->getToken($tokenStr);
        if ($res == null)
            return -1;
        else {
            $time = new Carbon();
            if ($res->expires_at > $time) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    public function getUserByToken($tokenStr)
    {
        $tokenInfo = $this->getToken($tokenStr);
        $userInfo = DB::table('users')->where('id', $tokenInfo->user_id)->select('id', 'role', 'status', 'name', 'avatar')->first();
        return $userInfo;
    }

    public function refreshToken($tokenStr)
    {
        $time = new Carbon();
        $outTime = new Carbon();
        $updateTime = new Carbon();
        $outTime->addHour(self::$EXPIRE_TIME);
        $updateTime->subDay();
        $newTokenStr = md5(uniqid());
        $data = [
            'token' => $newTokenStr,
            'updated_at' => $time,
            'expires_at' => $outTime
        ];

        $res = DB::table('tokens')->where([
            ['token', '=', $tokenStr],
            ['updated_at', '<', $updateTime]
        ])->update($data);

        if ($res == 1)
            return $newTokenStr;
        else
            return $tokenStr;
    }
}