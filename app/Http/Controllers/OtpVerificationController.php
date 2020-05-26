<?php

namespace App\Http\Controllers;

use App\Modal\OtpVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;


class OtpVerificationController extends Controller
{
    //
    public function create(Request $request){
        $otp=mt_rand(100000,999999);
        $data=OtpVerification::where('mobile_no',$request->mobile_no)->get();
        if(count($data)){
            foreach ($data as $dt){
                $dt->status=0;
                $dt->save();
            }
        }
        OtpVerification::create([
           "mobile_no"=>$request->mobile_no,
           "otp"=>$otp
        ]);
        //otp send to user number
        return  Response::json([
            "status"=>true,
            "message"=>"Otp validate for 10 minutes",
            "result"=>""
        ]);
    }
    public function verify(Request $request){
        $data=OtpVerification::where('mobile_no',$request->mobile_no)->where('status',1)->first();
        if($data->otp==$request->otp){
            $data->status=0;
            $data->save();
            return  Response::json([
                "status"=>true,
                "message"=>"Otp Matched",
                "result"=>""
            ]);
        }
        return  Response::json([
            "status"=>true,
            "message"=>"Otp didn't match try again",
            "result"=>""
        ]);
    }
}
