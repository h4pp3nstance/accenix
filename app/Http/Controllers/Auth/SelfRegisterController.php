<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\SelfRegisterHandler;

class SelfRegisterController extends Controller
{
    use SelfRegisterHandler;

    /**
     * Show the self-registration form.
     */
    public function showForm()
    {
        return view('auth.self-register');
    }

    /**
     * Handle the self-registration form submission.
     */
    public function submit(Request $request)
    {
        return $this->handleSelfRegister($request);
    }
}
