<?php

namespace Kongulov\NovaTabTranslatable\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FieldDownloadController extends Controller
{
    use FindsTranslatedField;

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

        $field = $this->findTranslatedField($request, $resource);

        if (!$field) { // not a translatable file
            $controller = new \Laravel\Nova\Http\Controllers\FieldDownloadController();

            return $controller->show($request);
        }

        $resource->authorizeToView($request);

        $model = $resource->model();
        $locale = $this->fieldLocale($field);
        $value = $this->translationFor($model, $this->fieldOriginalAttribute($field), $locale);

        $disk = $field->getStorageDisk();

        if (!$value || !Storage::disk($disk)->exists($value)) abort(404);

        // honour storeOriginalName() so the browser gets the name the file was uploaded with
        $name = $field->originalNameColumn
            ? $this->translationFor($model, $field->originalNameColumn, $locale)
            : null;

        return response()->download(Storage::disk($disk)->path($value), $name ?: null);
    }
}
