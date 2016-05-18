<?php

namespace App\Http\Requests\Legacy;


use App\Http\Requests\Request;
use Auth;

class ListenRequest extends Request
{
    public function authorize() {
        return check_user_mod(Auth::user()->id, 'listen');
    }

    public function rules() {
        return [];
    }
}