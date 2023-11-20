<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use \Illuminate\Support\Facades\Validator;
use \Illuminate\Support\Facades\Auth;

class AuthController extends BaseController
{
    public function register(Request $request){

        $validator = Validator::make($request->all(),
        [
            'name' => 'required',
            'email' => 'required|email|unique:App\Models\User,email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
            'role' => 'required|integer'
        ],
        [
            'name.required' => "Kötelező kitölteni!",

            'email.required' => "Kötelező kitölteni!",
            'email.email' => "Hibás az email cím!",
            'email.unique' => "Az email cím már létezik",

            'password.required' => "Kötelező kitölteni!",

            'confirm_password.required' => "Kötelező kitölteni!",
            'confirm_password.same' => "A két jelszó nem egyforma!",

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


    public function login(Request $request){
        if (Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('Secret')->plainTextToken;
            $success['name'] = $user->name;
            $success['id'] = $user->id;
            $success['role'] = $user->role;

            return $this->sendResponse($success,'Sikeres bejelentkezés!');
        } else {
            return $this->sendError('Unauthorized',['error' => 'Sikertelen bejelentkezés!'],401);
        }
    }

    public function logout(Request $request){
        auth()->user()->tokens()->delete();
        return $this->sendResponse('','Sikeres kijelentkezés!');
    }
}
