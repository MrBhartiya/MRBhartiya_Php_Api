<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Helpers\AppHelper;
use App\Modal\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class ClasseController extends Controller
{
    //
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'class_name' => 'required|max:255|unique:classes',//|unique:classes
            'description' => 'required'
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
        Classe::create([
           "class_id"=>AppHelper::random_strings(6),
           "class_name"=> $request->class_name,
            "description"=>$request->description
        ]);
        return Response::json([
            "status_code"=>201,
            "status"=>true,
            "message"=>"Class create successfully",
            "data"=>""
        ],201);
    }
    public function list(){
        $classes=Classe::get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Class List",
            "data"=>$classes
        ],200);
    }

    public function objectById($id){
        $classes=Classe::find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Class List",
            "data"=>$classes
        ],200);
    }
    public function delete(Request $request){
        $id=$request->id;
        $class=Classe::find($id);
        if(Storage::disk('s3')->exists($class->class_name."/")) {
            Storage::disk('s3')->deleteDirectory($class->class_name."/");
        }
        if($class){
            $class->delete();
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Class delete successfully",
                "data"=>""
            ]);
        }
        return Response::json([
            "status_code"=>400,
            "status"=>false,
            "message"=>"Class not exist",
            "data"=>""
        ],400);
    }
    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'class_name' => 'required|max:255|unique:classes,class_name,'."$request->id",//|unique:classes
            'description' => 'required'
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
        $classs=Classe::find($request->id);
        $classs->class_name=$request->class_name;
        $classs->description=$request->description;
        $classs->save();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Class update successfully",
            "data"=>""
        ],200);
    }
    public function getSubject($id){
        $classSubjectList=Classe::with('subject')->get()->find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Class Subject List",
            "data"=>$classSubjectList
        ],200);
    }
}
