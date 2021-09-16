<?php

namespace Kongulov\NovaTabTranslatable;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\NovaRequest;

trait TranslatableTabToRowTrait
{
    protected $childFieldsArr = [];

    /**
     * @param NovaRequest $request
     * @return FieldCollection|\Illuminate\Support\Collection
     */
    public function availableFields(NovaRequest $request)
    {
        $method = $this->fieldsMethod($request);

        // needs to be filtered once to resolve Panels
        $fields = $this->filter($this->{$method}($request));
        $availableFields = [];

        foreach ($fields as $key => $field) {
            if ($field instanceof NovaTabTranslatable) {
                $availableFields[] = $this->filterFieldForRequest($field, $request);
                if($this->extractableRequest($request, $this->model())) {
                    if ($this->doesRouteRequireChildFields()) {
                        $this->extractChildFields($field, $key);
                    }
                }
            } else {
                $availableFields[] = $this->filterFieldForRequest($field, $request);
            }
        }

        if ($this->childFieldsArr) {
            for ($i = count($fields)-1; $i >= 0; $i--){
                $field = $fields[$i];
                if ($field instanceof NovaTabTranslatable) {
                    array_splice($availableFields, $i+1,0, $this->childFieldsArr[$i]);
                    unset($availableFields[$i]);
                }
            }
        }


        $availableFields = new FieldCollection(array_values($this->filter($availableFields)));
        return $availableFields;
    }

    /**
     * Check if request needs to extract child fields
     *
     * @param NovaRequest $request
     * @param $model
     * @return bool
     */
    protected function extractableRequest(NovaRequest $request, $model)
    {
        // if form was submitted to update (method === 'PUT')
        if ($request->isUpdateOrUpdateAttachedRequest() && strtoupper($request->get('_method', null)) === 'PUT') {
            return false;
        }
        // if form was submitted to create and new resource
        if ($request->isCreateOrAttachRequest() && $model->id === null) {
            return false;
        }
        return true;
    }

    /**
     * @param $field
     * @param NovaRequest $request
     * @return mixed
     *
     * @todo: implement
     */
    public function filterFieldForRequest($field, NovaRequest $request)
    {
        // @todo: filter fields for request, e.g. show/hideOnIndex, create, update or whatever
        return $field;
    }

    /**
     * @param array $availableFields
     * @param NovaRequest $request
     */
    public function filterFieldsForRequest(Collection $availableFields, NovaRequest $request)
    {
        return $availableFields;
    }

    /**
     * @return bool
     */
    protected function doesRouteRequireChildFields(): bool
    {

        return Str::endsWith(Route::currentRouteAction(), [
            /*'FieldDestroyController@handle',
            'ResourceUpdateController@handle',
            'ResourceStoreController@handle',
            'AssociatableController@index',
            'MorphableController@index',*/

            'ResourceIndexController@handle',
            'ResourceShowController@handle',
        ]);
    }

    /**
     * @param  [array] $childFields [meta fields]
     * @return void
     */
    protected function extractChildFields($field, $key)
    {
        foreach ($field->originalFields as $childField) {
            if ($childField instanceof NovaTabTranslatable) {
                $this->extractChildFields($childField->data, $key);
            } else {
                if (array_search($childField->attribute, array_column($this->childFieldsArr, 'attribute')) === false) {
                    // @todo: we should not randomly apply rules to child-fields.
                    $childField = $this->applyRulesForChildFields($childField);
                    if (! $field instanceof NovaTabTranslatable && isset($field->panel)) $childField->panel = $field->panel;
                    $this->childFieldsArr[$key][] = $childField;
                }
            }
        }
    }

    /**
     * @param  [array] $childField
     * @return [array] $childField
     */
    protected function applyRulesForChildFields($childField)
    {
        if (isset($childField->rules)) {
            $childField->rules[] = "sometimes:required:" . $childField->attribute;
        }
        if (isset($childField->creationRules)) {
            $childField->creationRules[] = "sometimes:required:" . $childField->attribute;
        }
        if (isset($childField->updateRules)) {
            $childField->updateRules[] = "sometimes:required:" . $childField->attribute;
        }
        return $childField;
    }

    /**
     * Validate action fields
     * Overridden using ActionController & ActionRequest by modifying routes
     * @return void
     */
    public function validateFields()
    {
        $availableFields = [];
        if (!empty(($action_fields = $this->action()->fields()))) {
            foreach ($action_fields as $field) {
                if ($field instanceof NovaTabTranslatable) {
                    // do not add any fields for validation if container is not satisfied
                } else {
                    $availableFields[] = $field;
                }
            }
        }

        if ($this->childFieldsArr) {
            $availableFields = array_merge($availableFields, $this->childFieldsArr);
        }

        $this->validate(collect($availableFields)->mapWithKeys(function ($field) {
            return $field->getCreationRules($this);
        })->all());
    }
}
