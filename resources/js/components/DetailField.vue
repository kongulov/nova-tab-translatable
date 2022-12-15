<template>
    <div id="nova-tab-translatable" class="details w-full">
        <select-nova-tab-translatable v-if="isSelect()"
                                      :options="field.languages"
                                      :value="selectedLang"
                                      @input="switchLanguage">
        </select-nova-tab-translatable>

        <div v-if="!isSelect()" class="tab-items px-8">
            <span class="tab-item" v-for="lang in field.languages"
                  :data-langfor="lang"
                  :class="{'active':selectedLang === lang}" @click="switchLanguage(lang)">
                {{ lang }}
            </span>
        </div>

        <div class="tab-contents">
            <div v-for="(component, index) in field.fields"
                 v-show="selectedLang === component.locale && component.showOnDetail"
                 :data-lang="component.locale">
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
                />
            </div>
        </div>
    </div>
</template>

<script>
import IndexMixin from '../mixins/index'

export default {
    mixins: [IndexMixin],
    props: ['field', 'resource', 'resourceId', 'resourceName', 'errors', 'viaResource', 'viaRelationship', 'viaResourceId'],
    methods:{
        fieldDefaultValue(field) {
            if (field.value === '' && field.defaultValue !== '') field.value = field.defaultValue;

            return field;
        },
        resolveComponentName(field) {
            return field.prefixComponent ? 'detail-' + field.component : field.component
        },
    }
}
</script>
