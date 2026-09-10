<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['service' => 'poshprofs-lms-api', 'status' => 'ok']));
