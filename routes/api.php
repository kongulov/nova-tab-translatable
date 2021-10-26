<?php

use Illuminate\Support\Facades\Route;

// Fields...
Route::get('/{resource}/{resourceId}/download/{field}', 'FieldDownloadController@show');
Route::delete('/{resource}/{resourceId}/field/{field}', 'FieldDestroyController@handle');
Route::delete('/{resource}/{resourceId}/{relatedResource}/{relatedResourceId}/field/{field}', 'PivotFieldDestroyController@handle');
