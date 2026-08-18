<?php

namespace Kongulov\NovaTabTranslatable\Repeater;

use Kongulov\NovaTabTranslatable\NovaTabTranslatable;
use Laravel\Nova\Fields\Repeater\Presets\JSON;

/**
 * JSON preset for Repeaters that contain a NovaTabTranslatable.
 *
 * Nova >= 5 prunes every attribute written into the row payload that it cannot match back to a field
 * of the repeatable (JSON::cleanObsoleteFields(), only reached when the Repeater has uniqueField() set).
 * NovaTabTranslatable writes its translations under the attributes of the fields it wraps — `title`,
 * `image`, ... — while the only field the repeatable exposes is NovaTabTranslatable itself, so the
 * whole row gets emptied. This preset teaches the pruning about the wrapped attributes.
 *
 * Nova 4 has no cleanObsoleteFields() at all, so there the override is simply never called.
 *
 * Usage:
 *     Repeater::make('Data')
 *         ->uniqueField('uuid')
 *         ->preset(new TranslatableJson)
 *         ->repeatables([...]);
 */
class TranslatableJson extends JSON
{
    /**
     * Drop attributes that belong to no field of the repeatable, keeping the ones owned by a
     * NovaTabTranslatable.
     *
     * Parameter types are intentionally omitted so the override stays compatible across Nova
     * releases that move or rename the Fluent class.
     *
     * @param  \Laravel\Nova\Support\Fluent  $data
     * @param  \Laravel\Nova\Fields\FieldCollection  $fields
     */
    protected function cleanObsoleteFields($data, $fields): void
    {
        $translated = [];

        foreach ($fields as $field) {
            if ($field instanceof NovaTabTranslatable) {
                $this->collectTranslatedAttributes($field, $translated);
            }
        }

        foreach ($data->getAttributes() as $attribute => $value) {
            if (in_array($attribute, $translated, true)) {
                continue;
            }

            if (is_null($fields->findFieldByAttribute($attribute))) {
                $data->offsetUnset($attribute);
            }
        }
    }

    /**
     * Collect the attributes a NovaTabTranslatable writes, following nested ones.
     */
    protected function collectTranslatedAttributes(NovaTabTranslatable $field, array &$attributes): void
    {
        foreach ($field->originalFields as $original) {
            if ($original instanceof NovaTabTranslatable) {
                $this->collectTranslatedAttributes($original, $attributes);

                continue;
            }

            $attributes[] = $original->attribute;
        }
    }
}
