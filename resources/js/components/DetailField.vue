<template>
    <div id="nova-tab-translatable" class="details w-full">
        <div class="tab-items px-8" style="margin: 0;">
            <span class="tab-item" v-for="lang in field.languages"
                  :class="{'active':selectedLang === lang}" @click="switchLanguage(lang)">
                {{ lang }}
            </span>
        </div>
        <div class="tab-contents">
            <div class="tab-content table">
                <div class="table-row" v-for="(component, index) in field.fields" v-show="selectedLang === component.locale && component.showOnDetail">
                    <div class="table-cell p-2">{{ componentName(component) }}</div>
                    <div class="table-cell p-2">&nbsp;:&nbsp;</div>
                    <div class="table-cell p-2" v-html="component.value"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import IndexMixin from '../mixins/index'

export default {
    mixins: [IndexMixin],
    props: ['resource', 'resourceName', 'resourceId', 'field'],
}
</script>
