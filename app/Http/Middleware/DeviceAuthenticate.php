<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;
use Firebase\Auth\Token\Exception\InvalidToken;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Factory;
//use Google\Cloud\Firestore\FirestoreClient;


class DeviceAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /*$url = Storage::disk('local')->path('google-services.json');
        $factory = (new Factory)->withServiceAccount($url);
        $firestore = $factory->createFirestore();
        $database = $firestore->database();

        //$name
        $query = $database->collection();
        return $query->documents();
dd($test);*/
        $contents = json_decode(Storage::get('key.json'));
        //$file=Storage::disk('local')->path('key.txt');
        $client_key = $request->header('client-key');
        $secret_key = $request->header('secret-key');
        if(!($client_key && $secret_key)){
            return  Response::json([
                "status_code"=>401,
                "status"=>false,
                "message"=>"Authentication not valid",
                "data"=>""
            ],401);
        }
        if($contents->client_key!=$client_key || $contents->secret_key!=$secret_key){
            return  Response::json([
                "status_code"=>401,
                "status"=>false,
                "message"=>"Authentication not valid",
                "data"=>""
            ],401);
        }
        return $next($request);
    }
}
