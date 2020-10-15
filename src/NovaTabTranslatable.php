<?php

namespace Kongulov\NovaTabTranslatable;

use Drobee\NovaSluggable\SluggableText;
use Epartment\NovaDependencyContainer\NovaDependencyContainer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Http\Requests\NovaRequest;

class NovaTabTranslatable extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-tab-translatable';

    public $name = 'Tab translatable';
    public $data = [];
    public $locales = [];
    public $requiredLocales = [];
    public $originalFields = [];
    public $translatedFieldsByLocale = [];

    /** @var \Closure|null */
    protected static $displayLocalizedNameByDefaultUsingCallback;

    /** @var \Closure */
    protected $displayLocalizedNameUsingCallback;

    public $panel;

    public function __construct(array $fields = [])
    {
        parent::__construct($this->name);
        $config = config('tab-translatable');
        if($config['source'] == 'database')
            $this->locales = $config['database']['model']::pluck($config['database']['code_field'])->toArray();
        else
            $this->locales = $config['locales'];

        $this->displayLocalizedNameUsingCallback = self::$displayLocalizedNameByDefaultUsingCallback ?? function (Field $field, string $locale) {
            return ucfirst($field->name) . " [{$locale}]";
        };

        $this->originalFields = $fields;

        $this->createTranslatableFields();

        $this->withMeta([
            'languages' => $this->locales,
            'fields' => $this->data,
            'originalFieldsCount' => count($fields),
            'requiredLocales' => $this->requiredLocales,
        ]);
    }

    public function setTitle($title){
        $this->name = $title;

        return $this;
    }

    protected function createTranslatableFields()
    {
        collect($this->locales)
            ->crossJoin($this->originalFields)
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
            'locale' => $locale,
            'showOnIndex' => $translatedField->showOnIndex,
            'showOnDetail' => $translatedField->showOnDetail,
            'showOnCreation' => $translatedField->showOnCreation,
            'showOnUpdate' => $translatedField->showOnUpdate,
            'onlyOnDetail' => $translatedField->onlyOnDetail,
        ]);

        $translatedField = $this->setRules($translatedField);

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

    public function setRules($translatedField){

        $translatedField->creationRules = $this->setUnique($translatedField->creationRules, $translatedField->meta['locale']);
        $translatedField->updateRules = $this->setUnique($translatedField->updateRules, $translatedField->meta['locale']);

        foreach ($translatedField->rules as $key => &$rule) {
            if (strpos($rule, 'required_lang') !== false){
                $langs = explode(',', Str::after($rule,'required_lang:'));

                if (in_array($translatedField->meta['locale'], $langs)){
                    $rule = 'required';
                    $translatedField->requiredCallback = true;
                }
                else unset($translatedField->rules[$key]);
            }
            elseif ($rule === 'required') {
                $translatedField->requiredCallback = true;
            }
        }

        if ($translatedField->requiredCallback){
            $this->requiredLocales[$translatedField->meta['locale']] = $translatedField->requiredCallback;
        }

        return $translatedField;
    }

    public function setUnique($rules, $locale){
        foreach ($rules as &$rule) {
            if (strpos($rule, 'unique:') !== false){
                $before = Str::before($rule,'unique:');
                $after = Str::after($rule,'unique:');
                $explode = explode(',', $after);

                $explode[1] = $explode[1].'->'.$locale;

                $rule = $before.'unique:'.implode(',', $explode);
            }
        }

        return $rules;
    }

    protected function compatibilityWithOtherPlugins($translatedField)
    {
        if ($translatedField instanceof SluggableText) {
            $translatedField->slug($translatedField->meta['slug'] . ' [' . $translatedField->meta['locale'] . ']');
        }
        elseif ($translatedField instanceof Slug) {
            $translatedField->from('translations_'.$translatedField->from . '_' . $translatedField->meta['locale']);
        }
        elseif ($translatedField instanceof NovaDependencyContainer) {
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




