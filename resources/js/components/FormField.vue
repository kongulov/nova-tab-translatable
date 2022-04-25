<template>
    <div id="nova-tab-translatable" class="w-full">
        <div class="tab-items px-8">
            <span class="tab-item"
                  v-for="lang in field.languages"
                  :class="{'active':selectedLang === lang, 'has-error':checkError(lang)}"
                  @click="switchLanguage(lang)"
            >
                {{ lang }}
                <span class="text-danger text-sm">{{ field.requiredLocales[lang] ? '*' : '' }}</span>
            </span>
        </div>
        <div class="tab-contents">
            <div class="tab-content" v-for="(component, index) in field.fields"
                 v-show="selectedLang === component.locale && checkVisibility(component)">
                <component
                    :key="index"
                    :class="{'remove-bottom-border ': (index + 1) % field.originalFieldsCount !== 0}"
                    :is="resolveComponentName(component)"
                    :resource-name="resourceName"
                    :resource-id="resourceId"
                    :resource="resource"
                    :field="fieldDefaultValue(component)"
                    :errors="errors"
                    :via-resource="viaResource"
                    :via-resource-id="viaResourceId"
                    :via-relationship="viaRelationship"
                    @file-deleted="file-deleted"
                />
            </div>
        </div>
    </div>
</template>

<script>
import {FormField, HandlesValidationErrors} from 'laravel-nova';
import IndexMixin from '../mixins/index'

export default {
    mixins: [FormField, HandlesValidationErrors, IndexMixin],

    props: ['field', 'resourceId', 'resourceName', 'errors', 'viaResource', 'viaRelationship', 'viaResourceId'],

    watch:{
        errors() {
            this.switchToErrorTab();
        }
    },
    methods: {
        fieldDefaultValue(field) {
            if (field.value === '' && field.defaultValue !== '') field.value = field.defaultValue;

            return field;
        },
        isCreatePage(){
            return this.resourceId === undefined;
        },
        checkVisibility(component){
            if (this.isCreatePage()) return component.showOnCreation;
            else return component.showOnUpdate;
        },
        setInitialValue() {
            this.value = this.field.value || ''
        },
        fill(formData) {
            _.each(this.field.fields, field => {
                if (field.fill) {
                    field.fill(formData)
                }
            })
        },
        handleChange(value) {
            this.value = value
        },
        resolveComponentName(field) {
            return field.prefixComponent ? 'form-' + field.component : field.component
        },
        switchToErrorTab() {
            Object.keys(this.errors.errors).find((key) => {
                let lang = key.substr(key.length - 2);
                if (Object.keys(this.field.requiredLocales).includes(lang)) {
                    this.selectedLang = lang;
                    return true;
                }
            })
        },
        checkError(lang) {
            for (var key in this.errors.errors) {
                if (key.substr(key.length - 2) === lang) {
                    return true;
                }
            }

            return false;
        }
    },
    computed: {},
}
</script>
