<?php

namespace App\Http\Controllers;

use App\Services\MessageService;
use App\Tool\ValidationHelper;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    //
    public function getMessages(Request $request){
        $user=$request->user;
        $messages=MessageService::getMessage($user->id);
        return response()->json([
            'code' => 3000,
            'data'=>$messages
        ]);
    }
    public function readMessages(Request $request){
        $rule=[
            'taskarray'=>'required'
        ];
        $res=ValidationHelper::validateCheck($request->input(),$rule);
        if ($res->fails()){
            return response()->json([
                'code'=>3001,
                'message'=>$res->errors()
            ]);
        }
        $array=ValidationHelper::getInputData($request,$rule);
        MessageService::readMessages($array);
        return response()->json([
            'code' =>3000,
            'message' =>'已读取'
        ]);
    }
    public function deleteMessages(Request $request){
        $rule=[
            'taskarray'=>'required'
        ];
        $res=ValidationHelper::validateCheck($request->input(),$rule);
        if ($res->fails()){
            return response()->json([
                'code'=>3001,
                'message'=>$res->errors()
            ]);
        }
        $array=ValidationHelper::getInputData($request,$rule);
        MessageService::deleteMessages($array);
        return response()->json([
            'code' =>3000,
            'message' =>'已删除'
        ]);
    }
}
