<template>
    <div class="nova-tab-translatable-index w-full">
        <select-nova-tab-translatable v-if="isSelect()"
                                      :options="field.languages"
                                      :value="selectedLang"
                                      @input="switchLanguage">
        </select-nova-tab-translatable>

        <div v-if="!isSelect()" class="tab-items px-8">
            <span class="tab-item" v-for="lang in field.languages"
                  :data-langfor="lang"
                  :class="{'active':selectedLang === lang}" @click.stop="switchLanguage(lang)">
                {{ lang }}
            </span>
        </div>

        <div class="tab-contents">
            <div class="tab-content" style="display: flex"
                 v-for="(component, index) in field.fields"
                 v-show="selectedLang === component.locale && component.showOnIndex"
                 :data-lang="component.locale">
                <span style="margin-right: 5px;">{{ componentName(component) }}:</span>
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
    props: ['field', 'resourceId', 'resourceName', 'errors', 'viaResource', 'viaRelationship', 'viaResourceId'],
    methods:{
        fieldDefaultValue(field) {
            if (field.value === '' && field.defaultValue !== '') field.value = field.defaultValue;

            return field;
        },
        resolveComponentName(field) {
            return field.prefixComponent ? 'index-' + field.component : field.component
        },
    }
}
</script>
