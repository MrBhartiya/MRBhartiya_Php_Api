<?php
namespace App\Http\Controllers;
use App\Helpers\AppHelper;
use App\Modal\FavouriteVideo;
use App\Modal\LikedVideo;
use App\Modal\Topic;
use App\Modal\TopicVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
class TopicController extends Controller
{
    //
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'chapter_id' => 'required',
            'topic_name' => 'required',
            'description' => 'required',
            'thumbnail' => 'required'
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
        $topic=Topic::create([
            "topic_id"=>AppHelper::random_strings(6),
            "chapter_id"=> $request->chapter_id,
            "topic_name"=>$request->topic_name,
            "description"=>$request->description,
            "thumbnail"=>$path
        ]);
        return Response::json([
            "status_code"=>201,
            "status"=>true,
            "message"=>"topic create successfully",
            "data"=>""
        ],201);
    }
    public function list(){
        $topic=Topic::with('chapter.subject.classes')->with('chapter.subject.teacher')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Topic List",
            "data"=>$topic
        ],200);
    }
    public function objectById($id){
        $topic=Topic::with('chapter.subject.classes')->with('chapter.subject.teacher')->find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Topic List",
            "data"=>$topic
        ],200);
    }
    public function delete(Request $request){
        $id=$request->id;
        $topic=Topic::with('chapter.subject.classes')->find($id);
        if(Storage::disk('s3')->exists($topic->chapter->subject->classes->class_name."/".$topic->chapter->subject->subject_name."/".$topic->chapter->chapter_name."/".$topic->topic_name."/")) {
            Storage::disk('s3')->deleteDirectory($topic->chapter->subject->classes->class_name."/".$topic->chapter->subject->subject_name."/".$topic->chapter->chapter_name."/".$topic->topic_name."/");
        }
        if(Storage::disk('s3')->exists($topic->thumbnail)) {
            Storage::disk('s3')->delete($topic->thumbnail);
        }
        $topic->delete();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"topic delete successfully",
            "data"=>""
        ],200);
    }
    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'chapter_id' => 'required',
            'topic_name' => 'required',
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

        $topic=Topic::find($request->id);
        if(!$topic){
            return Response::json([
                "status_code"=>400,
                "status"=>true,
                "message"=>"Not exist",
                "data"=>""
            ],400);
        }
        if($request->has('thumbnail') && isset($request->thumbnail) && $request->thumbnail!=""){
            if(Storage::disk('s3')->exists($topic->thumbnail)) {
                Storage::disk('s3')->delete($topic->thumbnail);
            }
            $image = $request->file('thumbnail');
            $timestamp = now()->timestamp;
            $path="storage/chapter/".$timestamp.$image->getClientOriginalName();
            Storage::disk('s3')->put($path, file_get_contents($image));
        }
        if(!isset($path)){
            $path="";
        }
        $topic->chapter_id=$request->chapter_id;
        $topic->topic_name=$request->topic_name;
        $topic->description=$request->description;
        if($path){
            $topic->thumbnail=$path;
        }
        $topic->save();
        return Response::json([
            "status_code"=>201,
            "status"=>true,
            "message"=>"topic update successfully",
            "data"=>""
        ],201);

    }
    public function fav_video(Request $request){
        $user_id=Auth::user()->id;
        if(!isset($request->video_id)){
            return Response::json([
                "status_code"=>400,
                "status"=>true,
                "message"=>"Video id required",
                "data"=>""
            ],400);
        }
        $videoExist=TopicVideo::find($request->video_id);
        if(!$videoExist){
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Video not exist",
                "data"=>""
            ],400);
        }
        $videoObj=FavouriteVideo::where('user_id',$user_id)->where('video_id',$request->video_id)->first();
        if($videoObj){
            $videoObj->delete();
            return Response::json([
                "status_code"=>200,
                "status"=>false,
                "message"=>"video unfavourited successfully",
                "data"=>""
            ]);
        }else{
            FavouriteVideo::create([
                'user_id'=>$user_id,
                'video_id'=>$request->video_id
            ]);
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Favourited video successfully",
                "data"=>""
            ]);
        }

    }
    public function like_video(Request $request){
        $user_id=Auth::user()->id;
        if(!isset($request->video_id)){
            return Response::json([
                "status_code"=>400,
                "status"=>true,
                "message"=>"Video id required",
                "data"=>""
            ]);
        }
        $videoExist=TopicVideo::find($request->video_id);
        if(!$videoExist){
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Video not exist",
                "data"=>""
            ],400);
        }


        $videoObj=LikedVideo::where('user_id',$user_id)->where('video_id',$request->video_id)->first();
        if($videoObj){
            $videoObj->delete();
            return Response::json([
                "status_code"=>200,
                "status"=>false,
                "message"=>"Video disliked successfully",
                "data"=>""
            ],200);
        }else{
            LikedVideo::create([
                'user_id'=>$user_id,
                'video_id'=>$request->video_id
            ]);
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Video liked successfully",
                "data"=>""
            ],200);
        }

    }

    public function favVideoList(){
        $user_id=Auth::user()->id;
        $videoObj=FavouriteVideo::with('video')->where('user_id',$user_id)->has('video')->get();
        if($videoObj){
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Favourite video",
                "data"=>$videoObj
            ],200);
        }else{
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Favourite video",
                "data"=>""
            ],400);
        }

    }

}
?>