<?php

use Illuminate\Support\Facades\Route;

// Fields...
Route::get('/nova-api/{resource}/{resourceId}/download/{field}', 'FieldDownloadController@show')->where('field', 'translations_(.*)_(.*)');
Route::delete('/nova-api/{resource}/{resourceId}/field/{field}', 'FieldDestroyController@handle')->where('field', 'translations_(.*)_(.*)');
Route::delete('/nova-api/{resource}/{resourceId}/{relatedResource}/{relatedResourceId}/field/{field}', 'PivotFieldDestroyController@handle')->where('field', 'translations_(.*)_(.*)');
