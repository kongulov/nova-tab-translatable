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

    /**
     * Hand a request that is not about a translated field back to Nova's own controller. Resolved
     * through the container so the request class Nova type-hints is built for it, and the action is
     * looked up because Nova renamed some of them between releases (issues #45, #56).
     */
    protected function forwardToNova(string $controller, string ...$methods)
    {
        $controller = app($controller);

        foreach ($methods ?: ['__invoke'] as $method) {
            if (method_exists($controller, $method)) return app()->call([$controller, $method]);
        }

        abort(404);
    }

    protected function fieldLocale(Field $field): string
    {
        return $field->meta['locale'];
    }

    protected function fieldOriginalAttribute(Field $field): string
    {
        return $field->meta['originalAttribute'];
    }
}
