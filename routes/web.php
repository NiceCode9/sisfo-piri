<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('spmb.home');
});

Route::get('/pendaftaran', function () {
    return view('spmb.pendaftaran');
});
