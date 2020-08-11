<template>
    <div id="nova-tab-translatable" class="details">
        <div class="tab-items px-8" style="margin: 0;">
            <span class="tab-item" v-for="lang in field.languages"
                  :class="{'active':selectedLang === lang}" @click="selectedLang = lang">
                {{ lang }}
            </span>
        </div>
        <div class="tab-contents">
            <div class="tab-content table">
                <div class="table-row" v-for="(component, index) in field.fields" v-show="selectedLang === component.locale && component.showOnDetail">
                    <div class="table-cell p-2">{{ componentName(component) }}</div>
                    <div class="table-cell p-2">&nbsp;:&nbsp;</div>
                    <div class="table-cell p-2">{{ component.value }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],
    data() {
        return {
            selectedLang: '',
        }
    },
    methods: {
        componentName(component){
            return component.name.replace(' ['+component.locale+']', '');
        }
    },
    mounted() {
        this.selectedLang = this.field.languages[0] ? this.field.languages[0] : '';
    },
}
</script>
