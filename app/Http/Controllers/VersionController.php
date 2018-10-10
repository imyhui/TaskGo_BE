<?php

namespace App\Http\Controllers;

use App\Tool\ValidationHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VersionController extends Controller
{
    //
    public function getVersionInfo()
    {
        $versionInfo = DB::table('versions')
            ->orderBy('version_first_number', 'desc')
            ->orderBy('version_second_number', 'desc')
            ->orderBy('version_third_number', 'desc')
            ->first();
        return response()->json([
            'code' => 4000,
            'data' => $versionInfo
        ]);
    }

    public function newVersionInfo(Request $request)
    {
        $rule=[
            'name'=>'required',
            'version_first_number'=> 'required',
            'version_second_number'=> 'required',
            'version_third_number'=> 'required',
            'url' =>'required',
            'about' =>'required',
            'update_content'=>'required'
        ];
        $res=ValidationHelper::validateCheck($request->input(),$rule);
        if ($res->fails()){
            return response()->json([
                'code' => 4001,
                'message' => $res->errors()
            ]);
        }
        $data=ValidationHelper::getInputData($request,$rule);
        $data['created_at']=Carbon::now();
        $num=DB::table('versions')->where([
            ['version_first_number','=',1],
            ['version_second_number','=',1],
            ['version_third_number','=',1]
        ])->update($data);
        if ($num == 1){
            return response()->json([
                'code' => 4000,
                'message' => '更新成功'
            ]);
        }
        DB::table('versions')->insert($data);
        return response()->json([
            'code' =>4000,
            'message' =>'创建成功'
        ]);
    }

}
