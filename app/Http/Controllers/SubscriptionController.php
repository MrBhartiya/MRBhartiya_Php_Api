<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Modal\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    //
    //
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            "price"=>'required',
            "image"=>'required',
            "days"=>'required'
        ]);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ],400);
        }
        $file = $request->file('image');
        $timestamp = now()->timestamp;
        $imagePath="storage/banner/".$timestamp.$file->getClientOriginalName();
        Storage::disk('s3')->put($imagePath, file_get_contents($file));
        Subscription::create([
            "subscription_id"=>AppHelper::random_strings(8),
            "title"=> $request->title,
            "price"=>$request->price,
            "banner_image"=>$imagePath,
            "days"=>$request->days,
        ]);
        return Response::json([
            "status_code"=>201,
            "status"=>true,
            "message"=>"Subscription create successfully",
            "data"=>""
        ],201);
    }
    public function list(){
        $classes=Subscription::get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subscription List",
            "data"=>$classes
        ],200);
    }
    public function objectById($id){
        $classes=Subscription::find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subscription List",
            "data"=>$classes
        ],200);
    }

    public function delete(Request $request){
        $id=$request->id;
        $subC=Subscription::find($id);
        if(Storage::disk('s3')->exists($subC->banner_image)) {
            Storage::disk('s3')->delete($subC->banner_image);
        }
        $subC->delete();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subscription delete successfully",
            "result"=>""
        ],200);
    }
    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            "price"=>'required',
            "days"=>'required'
        ]);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ],400);
        }
        $classs=Subscription::find($request->id);
        $classs->title=$request->title;
        if($request->has('image')){
            $file = $request->file('image');
            $timestamp = now()->timestamp;
            $imagePath="storage/banner/".$timestamp.$file->getClientOriginalName();
            Storage::disk('s3')->put($imagePath, file_get_contents($file));
            if(Storage::disk('s3')->exists($classs->banner_image)) {
                Storage::disk('s3')->delete($classs->banner_image);
            }
            $classs->banner_image=$imagePath;
        }
        $classs->days=$request->days;
        $classs->price=$request->price;
        $classs->save();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subscription update successfully",
            "data"=>""
        ],200);
    }


}
