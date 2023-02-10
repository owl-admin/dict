<?php

use Slowlyo\SlowDict\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/admin_dict/dict_type_options', [Controllers\SlowDictController::class, 'dictTypeOptions']);
Route::resource('/admin_dict', Controllers\SlowDictController::class);
