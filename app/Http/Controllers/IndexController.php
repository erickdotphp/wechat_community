<?php
namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class IndexController extends BaseController
{

    public function getIndex(Request $request)
    {
        return view("index");
    }
    
    
}
