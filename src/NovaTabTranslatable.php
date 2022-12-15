<?php

namespace Kongulov\NovaTabTranslatable;

use Drobee\NovaSluggable\SluggableText;
use Epartment\NovaDependencyContainer\NovaDependencyContainer;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Image;
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
    private $locales = [];
    private $requiredLocales = [];
    private $translatedFieldsByLocale = [];
    public $originalFields = [];

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
            $this->locales = $config['database']['model']::query()
                ->when(isset($config['database']['sort_by']), function($query) use($config) {
                    $query->orderBy($config['database']['sort_by'], $config['database']['sort_direction']);
                })
                ->pluck($config['database']['code_field'])
                ->toArray();
        else
            $this->locales = $config['locales'];

        $this->displayLocalizedNameUsingCallback = self::$displayLocalizedNameByDefaultUsingCallback ?? function (Field $field, string $locale) {
            return ucfirst($field->name) . " [{$locale}]";
        };

        $this->originalFields = $fields;

        $this->createTranslatableFields();

        $this->withMeta([
            'saveLastSelectedLang'  => $config['save_last_selected_lang'] ?? false,
            'languages'             => $this->locales,
            'fields'                => $this->data,
            'originalFieldsCount'   => count($fields),
            'requiredLocales'       => $this->requiredLocales,
            'layout'                => $config['layout'] ?? 'tabs',
        ]);
    }

    public function setTitle($title): self
    {
        $this->name = $title;

        return $this;
    }

    public function saveLastSelectedLang(bool $state = true): self
    {
        return $this->withMeta([
            'saveLastSelectedLang'  => $state,
        ]);
    }

    protected function createTranslatableFields()
    {
        collect($this->locales)
            ->crossJoin($this->originalFields)
            ->eachSpread(function (string $locale, Field $field) {
                if($field->authorizedToSee(request())) {
                    $translatedField = $this->createTranslatedField($field, $locale);

                    $this->data[] = $translatedField;
                    $this->translatedFieldsByLocale[$locale][] = $translatedField;
                }
            });
    }

    protected function createTranslatedField(Field $originalField, string $locale): Field
    {
        $translatedField = clone $originalField;

        $originalAttribute = $translatedField->attribute;

        $translatedField->withMeta([
            'defaultValue' => $translatedField->defaultCallback,
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

        $translatedField->attribute = 'translations_' . $originalAttribute . '_' . $locale;
        $translatedField->panel = $this->panel;

        $translatedField
            ->resolveUsing(function ($value, Model $model) use ($translatedField, $locale, $originalAttribute) {
                return $model->translations[$originalAttribute][$locale] ?? '';
            });

        if ($originalField instanceof Image || $originalField instanceof File){
            $translatedField
                ->store(function ($request, $model, $attribute, $requestAttribute) use ($locale, $originalAttribute, $translatedField) {
                    $file = $request->file($requestAttribute)->store($translatedField->getStorageDir(), $translatedField->getStorageDisk());

                    $model->setTranslation($originalAttribute, $locale, $file);

                    return true;
                })
                ->thumbnail(function($value) use ($translatedField){
                    $disk = $translatedField->getStorageDisk();

                    if (!Storage::disk($disk)->exists($value)) return false;

                    return Storage::disk($disk)->url($value);
                })
                ->preview(function($value) use ($translatedField){
                    $disk = $translatedField->getStorageDisk();

                    if (!Storage::disk($disk)->exists($value)) return false;

                    return Storage::disk($disk)->url($value);
                });
        }
        else{
            $translatedField->fillUsing(function (Request $request, $model, $attribute, $requestAttribute) use ($locale, $originalAttribute, $translatedField) {
                $savedData = $request->get($requestAttribute);
                if (!isset($savedData)) {
                    foreach ($request->all() as $key => $value) {
                        if (!is_array($value)) continue;
                        if (!isset($request->get($key)[$requestAttribute])) continue;

                        $savedData = $request->get($key)[$requestAttribute];
                    }
                }

                if ($this->isJson($savedData)) $savedData = json_decode($savedData,true);

                $model->setTranslation($originalAttribute, $locale, $savedData);
            });
        }

        $translatedField = $this->compatibilityWithOtherPlugins($translatedField);

        return $translatedField;
    }

    protected function setRules($translatedField)
    {
        $locale = $translatedField->meta['locale'];
        $translatedField->creationRules = $this->setUnique($translatedField->creationRules, $locale);
        $translatedField->updateRules = $this->setUnique($translatedField->updateRules, $locale);

        foreach ($translatedField->rules as $key => &$rule) {
            if ($rule instanceof Rule) continue;

            if (strpos($rule, 'required_lang') !== false){
                $langs = explode(',', Str::after($rule,'required_lang:'));

                if (in_array($locale, $langs)){
                    $rule = 'required';
                    $translatedField->requiredCallback = true;
                }
                else unset($translatedField->rules[$key]);
            }
            elseif (strpos($rule, 'required_with') !== false){
                $fields = explode(',', Str::after($rule,'required_with:'));

                $fields = array_map(function($item) use ($locale){
                    return 'translations_'.$item.'_'.$locale;
                }, $fields);
                $fields = implode(',', $fields);

                $rule = 'required_with:'.$fields;
                $translatedField->requiredCallback = true;
            }
            elseif ($rule === 'required') {
                $translatedField->requiredCallback = true;
            }
        }

        if ($translatedField->requiredCallback){
            $this->requiredLocales[$locale] = $translatedField->requiredCallback;
        }

        return $translatedField;
    }

    protected function setUnique($rules, $locale){
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

    private function isJson($string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}




