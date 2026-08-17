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
    use FindsTranslatedField;

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

        /** @var \Laravel\Nova\Fields\Field&\Laravel\Nova\Contracts\Previewable|null $field */
        $field = $this->findTranslatedField($request, $resource);

        if (!$field) { // not a translatable file
            $controller = new \Laravel\Nova\Http\Controllers\FieldPreviewController;

            return $controller($request);
        }

        $request->validate(['value' => ['nullable', 'string']]);

        return response()->json([
            'preview' => $field->previewFor($request->value),
        ]);
    }
}
