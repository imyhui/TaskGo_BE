<?php

namespace App\Http\Controllers;

use App\Services\CardService;
use App\Tool\ValidationHelper;
use Illuminate\Http\Request;

class CardController extends Controller
{
    private $cardService;

    public function __construct(CardService $cardService)
    {
        $this->cardService = $cardService;
    }

    public function getMyCards(Request $request)
    {
        $userId = $request->user->id;
        $cards = $this->cardService->getUserCardsAll($userId);
        return response()->json([
            'code' => 1000,
            'message' => '请求成功',
            'data' => $cards
        ]);
    }

    public function createCard(Request $request)
    {
        $rules = [
            'use' => 'required',
            'picture' => 'required',
            'price' => 'required',
            'content' => 'required'
        ];

        $validator = ValidationHelper::validateCheck($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'code' => '1001',
                'message' => $validator->errors()
            ]);
        }

        $data = ValidationHelper::getInputData($request, $rules);
        $res = $this->cardService->createCard($data);
        if ($res) {
            return response()->json([
                'code' => '1000',
                'message' => '卡片创建成功'
            ]);
        } else {
            return response()->json([
                'code' => '3001',
                'message' => '卡片已存在'
            ]);
        }
    }

    public function updateCard(Request $request)
    {
        $rules = [
            'card_id' => 'required',
            'use' => 'required',
            'picture' => 'required',
            'price' => 'required',
            'content' => 'required'
        ];

        $validator = ValidationHelper::validateCheck($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'code' => '1001',
                'message' => $validator->errors()
            ]);
        }
        $cardId = $request->card_id;
        $data = ValidationHelper::getInputData($request, $rules);
        unset($data['card_id']);
        $this->cardService->updateCard($cardId, $data);
        return response()->json([
            'code' => '1000',
            'message' => '卡片修改成功'
        ]);
    }

    public function getAllCard()
    {
        $cards = $this->cardService->getAllCard();
        return response()->json([
            'code' => '1000',
            'message' => '获取成功',
            'data' => $cards
        ]);
    }

    public function getCardById($cardId)
    {
        $cardInfo = $this->cardService->getCardById($cardId);
        return response()->json([
            'code' => '1000',
            'message' => '请求成功',
            'data' => $cardInfo
        ]);
    }
}