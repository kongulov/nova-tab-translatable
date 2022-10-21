<?php

namespace Kongulov\NovaTabTranslatable\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FieldDownloadController extends Controller
{
    /**
     * Download the given field's contents.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return Response|BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function show(NovaRequest $request)
    {
        $resource = $request->findResourceOrFail();

        $explode = explode('_', $request->field);
        $locale = last($explode);
        $fieldNameArray = array_slice($explode, 1, -1);
        $fieldName = implode('_', $fieldNameArray);

        if (($resource->translatable === null && $fieldName === '') || !in_array($fieldName, $resource->translatable)){ // not translatable file
            $controller = new \Laravel\Nova\Http\Controllers\FieldDownloadController();

            return $controller->show($request);
        }

        $resource->authorizeToView($request);
        $model = $resource->model();
        $value = $model->getTranslation($fieldName, $locale);

        $tabs = $resource->updateFields($request)->whereInstanceOf(NovaTabTranslatable::class);

        $field = false;

        foreach ($tabs as $tab) {
            $field = collect($tab->data)->first(function($field) use ($request){
                return isset($field->attribute) &&
                    $field->attribute == $request->field;
            });
        }

        if (!$field) abort(404);

        $disk = $field->getStorageDisk();

        if (!Storage::disk($disk)->exists($value)) abort(404);

        $path = Storage::disk($disk)->path($value);

        return response()->download($path);
    }
}
