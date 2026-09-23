<?php

namespace Kongulov\NovaTabTranslatable\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FieldDownloadController extends Controller
{
    use FindsTranslatedField;

    /**
     * Download the given field's contents.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return \Laravel\Nova\Http\Controllers\FieldDownloadController|BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function show(NovaRequest $request)
    {
        $resource = $request->findResourceOrFail();

        $field = $this->findTranslatedField($request, $resource);

        if (!$field) { // not a translatable file
            $controller = new \Laravel\Nova\Http\Controllers\FieldDownloadController;

            return $controller($request);
        }

        $resource->authorizeToView($request);

        // resolve the translated value, then let the field's own download() callback answer: it honours a
        // user defined download() and streams from any disk, not only local ones (issue #55)
        $field->resolve($resource->resource);

        if (empty($field->value) || !($field->downloadsAreEnabled ?? true)) abort(404);

        return $field->toDownloadResponse($request, $resource);
    }
}
