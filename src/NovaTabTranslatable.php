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
use Laravel\Nova\Fields\Repeater;
use Laravel\Nova\Fields\Repeater\Presets\JSON as JsonPreset;
use Laravel\Nova\Fields\Repeater\RepeatableCollection;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\SupportsDependentFields;
use Throwable;

class NovaTabTranslatable extends Field
{
    use SupportsDependentFields;
    
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

    public function __construct(array $fields = [], array $locales = [])
    {
        parent::__construct($this->name);
        $config = config('tab-translatable');
        if ($config['source'] == 'database') {
            $this->locales = $config['database']['model']::query()
                ->when(isset($config['database']['sort_by']), function ($query) use ($config) {
                    $query->orderBy($config['database']['sort_by'], $config['database']['sort_direction']);
                })
                ->pluck($config['database']['code_field'])
                ->toArray();
        } else {
            $this->locales = count($locales) > 0 ? $locales : $config['locales'];
        }

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

    protected function createTranslatableFields(): void
    {
        collect($this->locales)
            ->crossJoin($this->originalFields)
            ->eachSpread(function (string $locale, Field $field) {
                if ($field->authorizedToSee(request())) {
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
            ->resolveUsing(function ($value, $model) use ($translatedField, $locale, $originalAttribute) {
                if ($model instanceof Model) return $model->translations[$originalAttribute][$locale] ?? '';

                // Repeater rows (and other non-model datasets) arrive as array/Fluent
                return data_get($model, $originalAttribute . '.' . $locale, '');
            });

        if ($this->isJsonRepeater($originalField)) {
            $translatedField
                ->resolveUsing(function ($value, $model) use ($originalField, $locale, $originalAttribute) {
                    $blocks = $model instanceof Model
                        ? ($model->translations[$originalAttribute][$locale] ?? [])
                        : data_get($model, $originalAttribute . '.' . $locale, []);

                    return RepeatableCollection::make($blocks ?? [])
                        ->filter(fn ($block) => isset($block['type']))
                        ->map(fn ($block) => $originalField->repeatables->newRepeatableByKey($block['type'], $block['fields'] ?? []))
                        ->values();
                })
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) use ($originalField, $locale, $originalAttribute) {
                    // Let Nova's own preset build the blocks on a throwaway model, then store them as a translation
                    $proxy = new class extends Model {
                    };

                    $callback = $originalField->getPreset()
                        ->set($request, $proxy, $requestAttribute, $originalField->repeatables, $originalField->uniqueField);

                    if (is_callable($callback)) $callback();

                    $blocks = json_decode($proxy->getAttributes()[$requestAttribute] ?? '[]', true);

                    $this->setTranslation($model, $originalAttribute, $locale, array_values($blocks ?? []));
                });
        } elseif ($originalField instanceof Image || $originalField instanceof File) {
            $translatedField
                ->store(function ($request, $model, $attribute, $requestAttribute) use ($locale, $originalAttribute, $translatedField) {
                    $file = $request->file($requestAttribute)->store($translatedField->getStorageDir(), $translatedField->getStorageDisk());

                    $this->setTranslation($model, $originalAttribute, $locale, $file);

                    return true;
                })
                ->thumbnail(function ($value) use ($translatedField) {
                    $disk = $translatedField->getStorageDisk();

                    if (!Storage::disk($disk)->exists($value)) return false;

                    return Storage::disk($disk)->url($value);
                })
                ->preview(function ($value) use ($translatedField) {
                    $disk = $translatedField->getStorageDisk();

                    if (!Storage::disk($disk)->exists($value)) return false;

                    return Storage::disk($disk)->url($value);
                });
        } else {
            $translatedField->fillUsing(function (Request $request, $model, $attribute, $requestAttribute) use ($locale, $originalAttribute, $translatedField) {
                $savedData = $request->input($requestAttribute);
                if (!isset($savedData)) {
                    foreach ($request->all() as $key => $value) {
                        if (!is_array($value)) continue;
                        if (!isset($value[$requestAttribute])) continue;

                        $savedData = $value[$requestAttribute];
                    }
                }

                if ($this->isJson($savedData)) $savedData = json_decode($savedData, true);

                $this->setTranslation($model, $originalAttribute, $locale, $savedData);
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

            if (strpos($rule, 'required_lang') !== false) {
                $langs = explode(',', Str::after($rule, 'required_lang:'));

                if (in_array($locale, $langs)) {
                    $rule = 'required';
                    $translatedField->requiredCallback = true;
                } else unset($translatedField->rules[$key]);
            } elseif (strpos($rule, 'required_with') !== false) {
                $fields = explode(',', Str::after($rule, 'required_with:'));

                $fields = array_map(function ($item) use ($locale) {
                    return 'translations_' . $item . '_' . $locale;
                }, $fields);
                $fields = implode(',', $fields);

                $rule = 'required_with:' . $fields;
                $translatedField->requiredCallback = true;
            } elseif ($rule === 'required') {
                $translatedField->requiredCallback = true;
            }
        }

        if ($translatedField->requiredCallback) {
            $this->requiredLocales[$locale] = $translatedField->requiredCallback;
        }

        return $translatedField;
    }

    protected function setUnique($rules, $locale)
    {
        foreach ($rules as &$rule) {
            if (strpos($rule, 'unique:') !== false) {
                $before = Str::before($rule, 'unique:');
                $after = Str::after($rule, 'unique:');
                $explode = explode(',', $after);

                $explode[1] = $explode[1] . '->' . $locale;

                $rule = $before . 'unique:' . implode(',', $explode);
            }
        }

        return $rules;
    }

    protected function compatibilityWithOtherPlugins($translatedField)
    {
        if ($translatedField instanceof SluggableText) {
            $translatedField->slug($translatedField->meta['slug'] . ' [' . $translatedField->meta['locale'] . ']');
        } elseif ($translatedField instanceof Slug) {
            $translatedField->from('translations_' . $translatedField->from . '_' . $translatedField->meta['locale']);
        } elseif ($translatedField instanceof NovaDependencyContainer) {
            // @todo
        }

        return $translatedField;
    }

    public function resolve($resource, $attribute = null): void
    {
        foreach ($this->data as $field) {
            $field->resolve($resource, $attribute);
        }
    }

    public function fillInto($request, $model, $attribute, $requestAttribute = null)
    {
        // Inside a Repeater the request keys are nested: "data.0.fields.<attribute>"
        $prefix = $requestAttribute ? Str::beforeLast($requestAttribute, $attribute) : '';

        $callbacks = [];

        foreach ($this->data as $field) {
            $callbacks[] = $field->fillInto($request, $model, $field->attribute, $prefix . $field->attribute);
        }

        $callbacks = array_filter($callbacks, 'is_callable');

        if (count($callbacks)) {
            return function () use ($callbacks) {
                foreach ($callbacks as $callback) $callback();
            };
        }

        return null;
    }

    /**
     * A Repeater using the JSON preset stores its blocks in the field's own column, so it can be translated.
     * The HasMany preset writes to a relation instead and is not translatable.
     */
    protected function isJsonRepeater(Field $field): bool
    {
        return $field instanceof Repeater && $field->getPreset() instanceof JsonPreset;
    }

    /**
     * Store a translation on an Eloquent model, a Repeater row (Nova Fluent) or any array-like target.
     */
    protected function setTranslation($model, string $attribute, string $locale, $value): void
    {
        if (is_object($model) && method_exists($model, 'setTranslation')) {
            $model->setTranslation($attribute, $locale, $value);

            return;
        }

        $translations = data_get($model, $attribute);
        $translations = is_array($translations) ? $translations : [];
        $translations[$locale] = $value;

        $model->{$attribute} = $translations;
    }

    public function getCreationRules(NovaRequest $request): array
    {
        $fieldsRules = $this->getSituationalRulesSet($request, 'creationRules');

        return array_merge_recursive(
            $this->getRules($request),
            $fieldsRules
        );
    }

    protected function getSituationalRulesSet(NovaRequest $request, string $propertyName = 'rules'): array
    {
        $fieldsRules = [$this->attribute => []];

        foreach ($this->data as $field) {
            $fieldsRules[$field->attribute] = is_callable($field->{$propertyName})
                ? call_user_func($field->{$propertyName}, $request)
                : $field->{$propertyName};
        }

        return $fieldsRules;
    }

    public function getUpdateRules(NovaRequest $request): array
    {
        $fieldsRules = $this->getSituationalRulesSet($request, 'updateRules');

        return array_merge_recursive(
            $this->getRules($request),
            $fieldsRules
        );
    }

    public function getRules(NovaRequest $request): array
    {
        return $this->getSituationalRulesSet($request);
    }

    private function isJson($string): bool
    {
        try {
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        } catch (Throwable $th) {
        }
        return false;
    }
}
