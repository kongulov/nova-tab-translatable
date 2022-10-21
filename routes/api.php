<?php

use Illuminate\Support\Facades\Route;

// Fields...
Route::get('/nova-api/{resource}/{resourceId}/download/{field}', 'FieldDownloadController@show');
Route::delete('/nova-api/{resource}/{resourceId}/field/{field}', 'FieldDestroyController@handle');
Route::delete('/nova-api/{resource}/{resourceId}/{relatedResource}/{relatedResourceId}/field/{field}', 'PivotFieldDestroyController@handle');
