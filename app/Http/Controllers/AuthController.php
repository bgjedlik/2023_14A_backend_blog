<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use \Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    public function register(Request $request){

        $validator = Validator::make($request->all(),
        [
            'name' => 'required',
            'email' => 'required|email|unique:App\Models\User,email',
            'password' => 'required',
            'role' => 'required|integer'
        ],
        [
            'name.required' => "Kötelező kitölteni!",

            'email.required' => "Kötelező kitölteni!",
            'email.email' => "Hibás az email cím!",
            'email.unique' => "Az email cím már létezik",

            'password.required' => "Kötelező kitölteni!",
            
            'role.required' => "Kötelező kitölteni!",
            'role.integer' => "Csak szám lehet!",
        ]);

        if ($validator->fails()){
            return $this->sendError('Bad request',$validator->errors(),400);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input); // insert into ...
        $success['token'] = $user->createToken('Secret')->plainTextToken;
        $success['name'] = $user->name;

        return $this->sendResponse($success,'Sikeres regisztráció!');
    }
}
