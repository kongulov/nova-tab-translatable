<?php

namespace Kongulov\NovaTabTranslatable;

use Drobee\NovaSluggable\SluggableText;
use Epartment\NovaDependencyContainer\NovaDependencyContainer;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class NovaTabTranslatable extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-tab-translatable';

    public $showOnIndex = false;

    public $data = [];
    public $locales = [];
    public $originalFields = [];
    public $translatedFieldsByLocale = [];

    /** @var \Closure|null */
    protected static $displayLocalizedNameByDefaultUsingCallback;

    /** @var \Closure */
    protected $displayLocalizedNameUsingCallback;

    public $panel;

    public function __construct(array $fields = [])
    {
        parent::__construct('');
        $config = config('tab-translatable');
        if($config['source'] == 'database')
            $this->locales = $config['database']['model']::pluck($config['database']['code_field'])->toArray();
        else
            $this->locales = $config['locales'];

        $this->displayLocalizedNameUsingCallback = self::$displayLocalizedNameByDefaultUsingCallback ?? function (Field $field, string $locale) {
                return ucfirst($field->name) . " [{$locale}]";
            };

        $this->collectAllFields($fields);
        $this->createTranslatableFields();

        $this->withMeta([
            'languages' => $this->locales,
            'fields' => $this->data,
            'originalFieldsCount' => count($fields),
        ]);
    }

    protected function collectAllFields($fields)
    {
        foreach ($fields as $field) {
            $this->data[] = $field;
        }
    }

    protected function createTranslatableFields()
    {
        collect($this->locales)
            ->crossJoin($this->data)
            ->eachSpread(function (string $locale, Field $field) {
                $translatedField = $this->createTranslatedField($field, $locale);

                $this->data[] = $translatedField;
                $this->translatedFieldsByLocale[$locale][] = $translatedField;
            });
    }

    protected function createTranslatedField(Field $originalField, string $locale): Field
    {
        $translatedField = clone $originalField;

        $originalAttribute = $translatedField->attribute;

        $translatedField->attribute = 'translations';
        $translatedField->withMeta([
            'locale' => $locale
        ]);

        $translatedField->name = (count($this->locales) > 1)
            ? ($this->displayLocalizedNameUsingCallback)($translatedField, $locale)
            : $translatedField->name;

        $translatedField
            ->resolveUsing(function ($value, Model $model) use ($translatedField, $locale, $originalAttribute) {
                $translatedField->attribute = 'translations_' . $originalAttribute . '_' . $locale;
                $translatedField->panel = $this->panel;

                return $model->translations[$originalAttribute][$locale] ?? '';
            });

        $translatedField->fillUsing(function ($request, $model, $attribute, $requestAttribute) use ($locale, $originalAttribute) {
            $model->setTranslation($originalAttribute, $locale, $request->get($requestAttribute));
        });

        $translatedField = $this->compatibilityWithOtherPlugins($translatedField);

        return $translatedField;
    }

    protected function compatibilityWithOtherPlugins($translatedField)
    {


        if ($translatedField instanceof SluggableText) {
            $translatedField->slug($translatedField->meta['slug'] . ' [' . $translatedField->meta['locale'] . ']');
        } elseif ($translatedField instanceof NovaDependencyContainer) {
            // @todo
        }

        return $translatedField;
    }


    public function resolve($resource, $attribute = null)
    {
        foreach ($this->data as $field) {
            $field->resolve($resource, $attribute);
        }
    }

    public function fillInto($request, $model, $attribute, $requestAttribute = null)
    {
        foreach ($this->data as $field) {
            $field->fill($request, $model);
        }
    }

    public function getCreationRules(NovaRequest $request)
    {
        $fieldsRules = $this->getSituationalRulesSet($request, 'creationRules');

        return array_merge_recursive(
            $this->getRules($request),
            $fieldsRules
        );
    }

    protected function getSituationalRulesSet(NovaRequest $request, string $propertyName = 'rules')
    {
        $fieldsRules = [$this->attribute => []];


        /** @var Field $field */
        foreach ($this->data as $field) {
            $fieldsRules[$field->attribute] = is_callable($field->{$propertyName})
                ? call_user_func($field->{$propertyName}, $request)
                : $field->{$propertyName};
        }

        return $fieldsRules;
    }

    public function getUpdateRules(NovaRequest $request)
    {
        $fieldsRules = $this->getSituationalRulesSet($request, 'updateRules');

        return array_merge_recursive(
            $this->getRules($request),
            $fieldsRules
        );
    }

    public function getRules(NovaRequest $request)
    {
        return $this->getSituationalRulesSet($request);
    }

}




