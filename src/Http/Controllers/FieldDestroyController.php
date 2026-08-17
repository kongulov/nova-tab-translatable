<?php

namespace Kongulov\NovaTabTranslatable\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class FieldDestroyController extends Controller
{
    use FindsTranslatedField;

    /**
     * Delete the file at the given field.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle(NovaRequest $request): \Illuminate\Http\Response
    {
        $resource = $request->findResourceOrFail();

        $field = $this->findTranslatedField($request, $resource);

        if (!$field) { // not a translatable file
            $controller = new \Laravel\Nova\Http\Controllers\FieldDestroyController;

            return $controller($request);
        }

        $resource->authorizeToUpdate($request);

        $model = $resource->model();

        $model->forgetTranslation($this->fieldOriginalAttribute($field), $this->fieldLocale($field));
        $model->timestamps = false;
        $model->save();

        return response(['success' => true]);
    }
}
