<?php

namespace Kongulov\NovaTabTranslatable\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Contracts\Previewable;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FieldPreviewController extends Controller
{
    /**
     * Delete the file at the given field.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function invoke(NovaRequest $request)
    {
        $resource = $request->newResource();

        $explode = explode('_', $request->field);
        $locale = last($explode);
        $fieldNameArray = array_slice($explode, 1, -1);
        $fieldName = implode('_', $fieldNameArray);

        if (($resource->translatable === null && $fieldName === '') || !in_array($fieldName, $resource->translatable)) { // not translatable file
            $controller = new \Laravel\Nova\Http\Controllers\FieldPreviewController;

            return $controller($request);
        }

        $tabs = $resource->updateFields($request)->whereInstanceOf(NovaTabTranslatable::class);

        /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Previewable&false $field */
        $field = false;

        foreach ($tabs as $tab) {
            $field = collect($tab->data)->first(function($field) use ($request){
                return isset($field->attribute) &&
                    $field->attribute == $request->field;
            });
            if($field) break;
        }

        if (!$field) abort(404);

        $request->validate(['value' => ['nullable', 'string']]);

        return response()->json([
            'preview' => $field->previewFor($request->value),
        ]);
    }
}
