<?php

use Slowlyo\OwlDict\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/admin_dict/options', [Controllers\OwlDictController::class, 'dictOptions']);
Route::get('/admin_dict/dict_type_options', [Controllers\OwlDictController::class, 'dictTypeOptions']);
Route::post('/admin_dict/save_order', [Controllers\OwlDictController::class, 'saveOrder']);
Route::resource('/admin_dict', Controllers\OwlDictController::class);
