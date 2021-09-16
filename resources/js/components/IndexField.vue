<template>
    <div class="nova-tab-translatable-index w-full">
        <div class="tab-items px-8">
            <span class="tab-item" v-for="lang in field.languages"
                  :class="{'active':selectedLang === lang}" @click="switchLanguage(lang)">
                {{ lang }}
            </span>
        </div>
        <div class="tab-contents">
            <div class="tab-content" v-for="(component, index) in field.fields"
                 v-show="selectedLang === component.locale && component.showOnIndex">
                {{ componentName(component) }}: {{ component.value }}
            </div>
        </div>
    </div>
</template>

<script>
import IndexMixin from '../mixins/index'

export default {
    mixins: [IndexMixin],
    props: ['resourceName', 'field'],
}
</script>
