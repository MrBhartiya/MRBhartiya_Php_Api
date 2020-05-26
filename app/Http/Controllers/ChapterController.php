<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Modal\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ChapterController extends Controller
{
    //
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|max:255',
            'chapter_name' => 'required',
            "description"=>'required',
            "thumbnail"=>'required'
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
        if($request->has('thumbnail') && isset($request->thumbnail) && $request->thumbnail!=""){
            $image = $request->file('thumbnail');
            $timestamp = now()->timestamp;
            $path="storage/chapter/".$timestamp.$image->getClientOriginalName();
            Storage::disk('s3')->put($path, file_get_contents($image));
        }
        if(!isset($path)){
            $path="";
        }

        Chapter::create([
            "chapter_id"=>AppHelper::random_strings(6),
            "subject_id"=> $request->subject_id,
            "chapter_name"=>$request->chapter_name,
            "description"=>$request->description,
            "thumbnail"=>$path
        ]);
        return Response::json([
            "status_code"=>201,
            "status"=>true,
            "message"=>"Chapter create successfully",
            "data"=>""
        ],201);
    }
    public function list(){
        $classes=Chapter::with('subject.classes')->with('subject.teacher')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Chapter List",
            "data"=>$classes
        ]);
    }
    public function objectById($id){
        $classes=Chapter::with('subject.classes')->with('subject.teacher')->find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Chapter List",
            "data"=>$classes
        ],200);
    }
    public function delete(Request $request){
        $id=$request->id;
        $chapter=Chapter::with('subject.classes')->find($id);
        if(Storage::disk('s3')->exists($chapter->subject->classes->class_name."/".$chapter->subject->subject_name."/".$chapter->chapter_name."/")) {
            Storage::disk('s3')->deleteDirectory($chapter->subject->classes->class_name."/".$chapter->subject->subject_name."/".$chapter->chapter_name."/");
        }
        if(Storage::disk('s3')->exists($chapter->thumbnail)) {
            Storage::disk('s3')->delete($chapter->thumbnail);
        }
        $chapter->delete();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Chapter delete successfully",
            "data"=>""
        ],200);
    }
    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|max:255',
            'chapter_name' => 'required',
            "description"=>'required'
        ]);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>200,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ],200);
        }
        $classs=Chapter::find($request->id);
        if($request->has('thumbnail') && isset($request->thumbnail) && $request->thumbnail!=""){
            if(Storage::disk('s3')->exists($classs->thumbnail)) {
                Storage::disk('s3')->delete($classs->thumbnail);
            }
            $image = $request->file('thumbnail');
            $timestamp = now()->timestamp;
            $path="storage/chapter/".$timestamp.$image->getClientOriginalName();
            Storage::disk('s3')->put($path, file_get_contents($image));
        }
        $classs->subject_id=$request->subject_id;
        $classs->chapter_name=$request->chapter_name;
        $classs->description=$request->description;
        if(isset($path)){
            $classs->thumbnail=$path;
        }
        $classs->save();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Chapter update successfully",
            "data"=>""
        ],200);
    }
    public function getTopic($id){
        $topicChapterList=Chapter::with('topic')->get()->find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Topic chapter List",
            "data"=>$topicChapterList
        ],200);
    }
    public function chapterList(Request $request){
        if(isset($request->chapter)){
            $classes=Chapter::with('subject.classes')->with('subject.teacher')->where('chapter_name','LIKE','%'.$request->chapter.'%')->get();
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Chapter List search",
                "data"=>$classes
            ]);
        }else{
            $classes=Chapter::with('subject.classes')->with('subject.teacher')->get();
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Chapter List",
                "data"=>$classes
            ]);
        }
    }
}
