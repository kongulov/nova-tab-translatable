<template>
    <div class="nova-tab-translatable-index">
        <div class="tab-items px-8">
            <span class="tab-item" v-for="lang in field.languages"
                  :class="{'active':selectedLang === lang}" @click="selectedLang = lang">
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
export default {
    props: ['resourceName', 'field'],
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
