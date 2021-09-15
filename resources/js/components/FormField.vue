<template>
    <div id="nova-tab-translatable" class="w-full">
        <div class="tab-items px-8">
            <span class="tab-item" v-for="lang in field.languages"
                  :class="{'active':selectedLang === lang, 'has-error':checkError(lang)}" @click="switchLanguage(lang)">
                {{ lang }} <span class="text-danger text-sm">{{ field.requiredLocales[lang] ? '*' : '' }}</span>
            </span>
        </div>
        <div class="tab-contents">
            <div class="tab-content" v-for="(component, index) in field.fields"
                 v-show="selectedLang === component.locale">
                <component
                    :key="index"
                    :class="{'remove-bottom-border ': (index + 1) % field.originalFieldsCount !== 0}"
                    :is="resolveComponentName(component)"
                    :resource-name="resourceName"
                    :resource-id="resourceId"
                    :resource="resource"
                    :field="component"
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
import {FormField, HandlesValidationErrors, InteractsWithResourceInformation} from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors, InteractsWithResourceInformation],

    props: ['field', 'resourceId', 'resourceName', 'errors', 'viaResource', 'viaRelationship', 'viaResourceId'],

    data() {
        return {
            selectedLang: '',
        }
    },
    mounted() {
        let lastSelectedLanguage;
        if (lastSelectedLanguage = sessionStorage.getItem(this.sessionStoragePreviousLang())) {
            this.selectedLang = lastSelectedLanguage;
        } else {
            this.selectedLang = this.field.languages[0] ? this.field.languages[0] : '';
        }
    },
    watch: {
        errors() {
            this.switchToErrorTab();
        }
    },
    methods: {
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
        switchLanguage(lang) {
            this.selectedLang = lang;
            sessionStorage.setItem(this.sessionStoragePreviousLang(), this.selectedLang);
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
        },
        sessionStoragePreviousLang() {
            return this.$options._componentTag + ':previous-language';
        }
    },
    computed: {},
}
</script>
