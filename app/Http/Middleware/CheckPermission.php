<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class CheckPermission
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
        if(!Auth::user() || Auth::user()->role=="SD"){
            return $next($request);
        }
        if($request->get('pageName')!='login'){
            $user=User::find(Auth::user()->id);
            $userCheck=$user->can($request->get('pageName'));
            if(!$userCheck){
                return Response::json([
                    "status" => false,
                    "errorCode"=>401,
                    "message"=>"Unauthorized login",
                    "result"=>""
                ],401);
            }
        }
        return $next($request);
    }
}
