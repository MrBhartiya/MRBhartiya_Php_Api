<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Modal\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class NotificationController extends Controller
{
    //
    public function create(Request $request){
        /*
         * Check user already exist or not
         */
        $validate = AppHelper::Validation($request, [
            'title' => 'required',
            'description' => 'required',
            'date' => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        Notification::create([
           "title"=>$request->title,
           "description"=>$request->description,
           "date"=>date('Y-m-d H:i:s',strtotime($request->date)),
        ]);
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Notification create successfully",
            "data" => ""
        ], 200);
    }
    public function edit(Request $request){
        /*
         * Check user already exist or not
         */
        $validate = AppHelper::Validation($request, [
            'title' => 'required',
            'description' => 'required',
            'date' => 'required',
            'id'=>'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $notification=Notification::find($request->id);
        $notification->title=$request->title;
        $notification->description=$request->description;
        $notification->date=date('Y-m-d H:i:s',strtotime($request->date));
        $notification->save();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Notification update successfully",
            "data" => ""
        ], 200);
    }
    public function delete(Request $request){
        /*
         * Check user already exist or not
         */
        $validate = AppHelper::Validation($request, [
            'id'=>'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $notification=Notification::find($request->id);
        $notification->delete();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Deleted successfully",
            "data" => ""
        ], 200);
    }
    public function ObjectById($id){
        $notification=Notification::find($id);
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Notification",
            "data" => $notification
        ], 200);
    }
    public function list(){
        $notification=Notification::get();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Notification list",
            "data" => $notification
        ], 200);
    }

}
