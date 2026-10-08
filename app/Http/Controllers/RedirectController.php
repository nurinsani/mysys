<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function check() {
        if (auth()->user()->role_id == 1) {
            return redirect('/admin');
        } else {
            return redirect('/al');
        }
    }
}
