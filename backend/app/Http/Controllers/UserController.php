<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function index()
    {
        $user = User::all();

        if (!$user) {
            return response()->json([
                "message" => "User not found"
            ], 404);
        }

        return response()->json([
            "message" => "This is user data",
            "data" => $user
        ]);
    }

    public function show($id){
        $user = User::find($id);

        if(!$user){
            return response()->json([
                "message"=>"user not found"
            ], 404);
        }

        return response()->json([
            "message"=>"This find seccusfully",
            "data" => $user
        ]);
    }
}
