<?php
/**
 * Created by PhpStorm.
 * User: zhangjianye
 * Date: 16/5/14
 * Time: 上午11:33
 */

namespace Blog\Http\Controllers\Test;

use Blog\Models\User;
use Blog\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TestController extends Controller{

    public function __construct()
    {

    }

    public function getIndex(Request $request)
    {
        dd(User::find(1000)->sms_time);
    }
}