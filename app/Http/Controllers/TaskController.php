<?php

namespace App\Http\Controllers;

use App\Services\BaseTaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    //
    private $baseService;
    public function __construct(BaseTaskService $baseTaskService)
    {
        $this->baseService=$baseTaskService;
    }

    public function showMyTasksByStatus($status,Request $request){
        $user=$request->user;
        $tasks=$this->baseService->showMyTasks($user->id,$status);
        return response()->json([
            'code'=>1000,
            'data'=>$tasks
        ]);
    }
    public function showMyAcceptTasksByStatus($status,Request $request){
        $user=$request->user;
        $tasks=$this->baseService->showMyAcceptTasks($user->id,$status);
        return response()->json([
            'code'=>1000,
            'data'=>$tasks
        ]);
    }
}
