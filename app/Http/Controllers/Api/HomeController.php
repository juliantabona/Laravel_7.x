<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Home as HomeResource;

class HomeController extends Controller
{
    public function home()
    {
        //  Return the home Api Resource
        return new HomeResource($this);
    }
}
