<template>
    <div id="nova-tab-translatable" class="w-full">
        <div class="tab-items px-8" ref="tabItems">
            <span class="tab-item"
                  v-for="lang in lineMenu"
                  ref="tabItem"
                  :data-langfor="lang"
                  :class="{'active':selectedLang === lang, 'has-error':checkError(lang)}"
                  @click="switchLanguage(lang)"
            >
                {{ lang }}
                <span class="text-danger text-sm">{{ field.requiredLocales[lang] !== undefined && field.requiredLocales[lang] == true ? '*' : '' }}</span>
            </span>

            <div id="hamburger-menu" v-show="hamburgerMenu.length">
                <div class="hamburger-icon" @click="menuIsOpen = !menuIsOpen" :data-hiddencount="hamburgerMenu.length" :class="{'fs14':hamburgerMenu.length < 100}">
                    <span></span>
                </div>
                <div class="hamburger-content tab-items" v-show="menuIsOpen">
                    <span class="tab-item"
                          v-for="lang in reversedHamburgerMenu"
                          ref="tabItem"
                          :data-langfor="lang"
                          :class="{'active':selectedLang === lang, 'has-error':checkError(lang)}"
                          @click="switchLanguage(lang)"
                    >
                        {{ lang }}
                        <span class="text-danger text-sm">{{ field.requiredLocales[lang] !== undefined && field.requiredLocales[lang] == true ? '*' : '' }}</span>
                    </span>
                </div>
            </div>
        </div>
        <div class="tab-contents">
            <div class="tab-content"
                 v-for="(component, index) in field.fields"
                 v-show="selectedLang === component.locale && checkVisibility(component)"
                 :data-lang="component.locale"
            >
                <component
                    :key="index"
                    :data-default="true"
                    :class="{'remove-bottom-border ': (index + 1) % field.originalFieldsCount !== 0}"
                    :is="resolveComponentName(component)"
                    :resource-name="resourceName"
                    :resource-id="resourceId"
                    :resource="resource"
                    :field="fieldDefaultValue(component)"
                    :errors="errors"
                    :show-help-text="component.helpText !== null"
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
import {FormField, HandlesValidationErrors, InteractsWithResourceInformation} from 'laravel-nova';
import IndexMixin from '../mixins/index'

export default {
    mixins: [FormField, HandlesValidationErrors, InteractsWithResourceInformation, IndexMixin],

    props: ['field', 'resourceId', 'resourceName', 'errors', 'viaResource', 'viaRelationship', 'viaResourceId'],

    data() {
        return {
            errorLanguages: new Set(),
        };
    },
    watch:{
        errors() {
            this.switchToErrorTab();
            this.updateErrorLanguages();
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
                const lang = key.match(/^translations_.+?_([a-z]{2}(?:_[A-Za-z]{2,})*)$/)[1] ?? '';
                if (Object.keys(this.field.requiredLocales).includes(lang)) {
                    this.selectedLang = lang;
                    return true;
                }
            })
        },
        updateErrorLanguages() {
            this.errorLanguages.clear();
            Object.keys(this.errors.errors).forEach((key) => {
                const lang = key.match(/^translations_.+?_([a-z]{2}(?:_[A-Za-z]{2,})*)$/);
                if (lang && lang.length > 1) {
                    this.errorLanguages.add(lang[1]);
                }
            });
        },
        checkError(lang) {
            return this.errorLanguages.has(lang);
        },
    },
    computed: {},
}
</script>
