<?php
namespace App\Traits;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Exception;

trait AuthVerify
{
    public function getSession()
    {
        // Periksa token akses OAuth2
        return Session::get('access_token');
    }

    public function hasSession(){
        return !empty($this->getSession());
    }
}
