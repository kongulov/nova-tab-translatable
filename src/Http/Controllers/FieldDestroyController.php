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
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle(NovaRequest $request)
    {
        $resource = $request->findResourceOrFail();

        $field = $this->findTranslatedField($request, $resource);

        if (!$field) { // not a translatable file
            return $this->forwardToNova(\Laravel\Nova\Http\Controllers\FieldDestroyController::class);
        }

        $resource->authorizeToUpdate($request);

        $model = $resource->model();
        $locale = $this->fieldLocale($field);
        $originalAttribute = $this->fieldOriginalAttribute($field);

        // run the field's delete() callback against the translated value, so the file leaves the disk
        // as well and storeOriginalName()/storeSize() columns are reported back (issue #45)
        $field->resolve($model);

        $result = $field->value && is_callable($field->deleteCallback ?? null)
            ? call_user_func($field->deleteCallback, $request, $model, $field->getStorageDisk(), $field->getStoragePath())
            : null;

        $model->forgetTranslation($originalAttribute, $locale);

        foreach (is_array($result) ? $result : [] as $column => $value) {
            if ($column === $field->attribute || $column === $originalAttribute) continue;

            if ($model->isTranslatableAttribute($column)) $model->forgetTranslation($column, $locale);
            else $model->{$column} = $value;
        }

        $model->timestamps = false;
        $model->save();

        return response(['success' => true]);
    }
}
