<?php

namespace Kongulov\NovaTabTranslatable\Http\Controllers;

use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

trait FindsTranslatedField
{
    /**
     * Find the translated field the request refers to.
     *
     * The locale and the original attribute are read from the field's meta instead of being parsed out
     * of the attribute name, which breaks for locales containing an underscore ("az_AZ").
     */
    protected function findTranslatedField(NovaRequest $request, $resource): ?Field
    {
        $tabs = $resource->updateFields($request)->whereInstanceOf(NovaTabTranslatable::class);

        foreach ($tabs as $tab) {
            $field = collect($tab->data)->first(function ($field) use ($request) {
                return isset($field->attribute) && $field->attribute === $request->field;
            });

            if ($field) return $field;
        }

        return null;
    }

    protected function fieldLocale(Field $field): string
    {
        return $field->meta['locale'];
    }

    protected function fieldOriginalAttribute(Field $field): string
    {
        return $field->meta['originalAttribute'];
    }

    /**
     * Read a translation without blowing up on attributes the model does not translate.
     */
    protected function translationFor($model, string $attribute, string $locale)
    {
        if (!method_exists($model, 'getTranslation')) return null;

        if (method_exists($model, 'isTranslatableAttribute') && !$model->isTranslatableAttribute($attribute)) {
            return $model->{$attribute} ?? null;
        }

        return $model->getTranslation($attribute, $locale);
    }
}
