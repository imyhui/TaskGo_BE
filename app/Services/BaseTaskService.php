<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BaseTaskService
{

    public function createTask($taskInfo)
    {
        $taskInfo['created_at'] = Carbon::now();
        $taskInfo['attributes'] = json_encode($taskInfo['attributes']);
        $taskInfo['cards'] = json_encode($taskInfo['cards']);
        $taskId = DB::table('tasks')->insertGetId($taskInfo);
        return $taskId;
    }

    public function showTasks(array $conditions)
    {
        $tasks = DB::table('tasks')->where($conditions)->get();
        return $tasks;
    }

    public function showMyTasks(int $userId, int $status)
    {
        $tasks = DB::table('tasks')->where([
            ['user_id', '=', $userId],
            ['attributes->status', '=', $status]
        ])->orderBy('created_at', 'desc')->paginate(20);
//        foreach ($tasks as $item){
//            $item->attributes=json_decode($item->attributes);
//            $item->cards=json_decode($item->cards);
//        }
        return $tasks;
    }

    public function showMyAcceptTasks(int $userId, int $status)
    {
        $tasks = DB::table('tasks')->join('users', 'tasks.user_id', '=', 'users.id')->where([
            ['tasks.attributes->status', '=', $status],
            ['tasks.attributes->acceptor', '=', $userId]
        ])->select('tasks.*', 'users.name', 'users.avatar')->orderBy('tasks.created_at','desc')->paginate(20);
        return $tasks;
    }
}