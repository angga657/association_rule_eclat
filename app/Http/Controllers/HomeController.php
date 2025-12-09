<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the beranda page.
     */
    public function index()
    {
        return view('beranda');
    }
}