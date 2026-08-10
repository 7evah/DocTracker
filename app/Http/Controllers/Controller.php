<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Laravel 12's skeleton ships a bare controller; DocFlow authorises inside
    // controllers (§13), so the trait is opted into here.
    use AuthorizesRequests;
}
