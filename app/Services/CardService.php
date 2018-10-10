<?php
/**
 * Created by PhpStorm.
 * User: yz
 * Date: 18/4/27
 * Time: 下午10:00
 */

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CardService
{
    public function createCard($data)
    {
        $isCardExist = DB::table('cards')->where([
            ['use', '=', $data['use']],
            ['picture', '=', $data['picture']]
        ])->first();
        if ($isCardExist)
            return false;

        DB::table('cards')->insert($data);
        return true;
    }

    public function updateCard($cardId, $data)
    {
        DB::table('cards')->where('id', $cardId)->update($data);
    }

    public function getAllCard()
    {
        $cards = DB::table('cards')->get();
        return $cards;
    }

    public function getCardById($cardId)
    {
        $card = DB::table('cards')->where('id', $cardId)->first();
        return $card;
    }

    public function hasCardOrNot($userId, $cardId)
    {
        $isExist = DB::table('user_cards')->where([
            ['user_id', '=', $userId],
            ['card_id', '=', $cardId]
        ])->first();
        if ($isExist && $isExist->number > 0)
            return true;
        else
            return false;
    }

    public function addUserCard($userId, $cardId, $cardNumber = 1)
    {
        $isHave = $this->hasCardOrNot($userId, $cardId);
        if ($isHave) {
            DB::table('user_cards')
                ->where('user_id', $userId)
                ->where('card_id', $cardId)
                ->increment('number', $cardNumber);
        } else {
            DB::table('user_cards')
                ->insert([
                        'user_id' => $userId,
                        'card_id' => $cardId,
                        'number' => $cardNumber
                    ]
                );
        }
    }

    public function addUserKindsCard($userId, array $cards)
    {
        $flag = false;
        DB::transaction(function () use ($userId, $cards, &$flag) {
            foreach ($cards as $kind => $number) {
                $this->addUserCard($userId, $kind, $number);
            }
            $flag = true;
        });
        return $flag;
    }

    public function removeUserKindsCard($userId, array $cards)
    {
        $flag = true;
        DB::beginTransaction(); //事务开始
        foreach ($cards as $kind => $number) {
            if (!$this->removeUserCard($userId, $kind, $number)) {
                $flag = false;
                DB::rollback();//事务失败 操作回滚
                return $flag;
            }
        }
        DB::commit();  //事务成功 提交操作

        return $flag;
    }

    public function getUserCards($userId)
    {
        // 获取用户已有卡片
        $cards = DB::table('user_cards')
            ->where('user_id', $userId)
            ->where('number', '>', 0)
            ->join('cards', 'cards.id', '=', 'card_id')
            ->select('card_id', 'use', 'picture', 'price', 'content', 'number')
            ->get();
        return $cards;
    }

    public function getUserCardsAll($userId)
    {
        //
        $cards = DB::table('cards')
            ->leftJoin('user_cards', function ($join) use ($userId) {
                $join->on('cards.id', '=', 'user_cards.card_id')
                    ->where('user_id', '=', $userId);
            })->select('id as card_id', 'use', 'picture', 'price', 'content', 'number')
            ->get();
//        dd($cards);
        return $cards;
    }

    public function removeUserCard($userId, $cardId, $cardNumber = 1)
    {
        $isExist = DB::table('user_cards')->where([
            ['user_id', '=', $userId],
            ['card_id', '=', $cardId]
        ])->first();
        if ($isExist != null && $isExist->number >= $cardNumber) {
            DB::table('user_cards')
                ->where('user_id', $userId)
                ->where('card_id', $cardId)
                ->decrement('number', $cardNumber);
            return true;
        } else {
            return false;
        }
    }
}