<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessageService
{

    public static function  sendMessage(int $from, int $to, string $content)
    {
        $message = [];
        $message['from'] = $from;
        $message['to'] = $to;
        $message['content'] = $content;
        $message['created_at'] = Carbon::now();
        $message['status'] = 0;
        DB::table('messages')->insert($message);
    }

    public static function getMessage(int $to)
    {
        $messages = DB::table('messages')->where([
            ['messages.to', '=', $to]
        ])->join('users', 'messages.from', '=', 'users.id')
            ->orderBy('messages.status', 'asc')->orderBy('messages.created_at', 'desc')->select('messages.*', 'users.avatar', 'users.name')->get();
        if (!$messages->first()) {
            return null;
        }
        return $messages;
    }

    public static function readMessages(array $messages)
    {
        DB::table('messages')->whereIn('id', $messages)->update([
            'status' => 1
        ]);
    }

    public static function deleteMessages(array $messages)
    {
        DB::table('messages')->whereIn('id', $messages)->delete();
    }

}
