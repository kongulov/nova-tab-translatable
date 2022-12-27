<template>
    <div class="nova-tab-translatable-index w-full">
        <div class="tab-items px-8" ref="tabItems">
            <span class="tab-item"
                  v-for="lang in lineMenu"
                  ref="tabItem"
                  :data-langfor="lang"
                  :class="{'active':selectedLang === lang}"
                  @click.stop="switchLanguage(lang)"
            >
                {{ lang }}
            </span>

            <div id="hamburger-menu" v-show="hamburgerMenu.length">
                <div class="hamburger-icon" @click.stop="menuIsOpen = !menuIsOpen" :data-hiddencount="hamburgerMenu.length" :class="{'fs14':hamburgerMenu.length < 100}">
                    <span></span>
                </div>
                <div class="hamburger-content tab-items" v-show="menuIsOpen">
                    <span class="tab-item"
                          v-for="lang in reversedHamburgerMenu"
                          ref="tabItem"
                          :data-langfor="lang"
                          :class="{'active':selectedLang === lang}"
                          @click.stop="switchLanguage(lang)"
                    >
                        {{ lang }}
                    </span>
                </div>
            </div>
        </div>
        <div class="tab-contents">
            <div class="tab-content" style="display: flex"
                 v-for="(component, index) in field.fields"
                 v-show="selectedLang === component.locale && component.showOnIndex"
                 :data-lang="component.locale"
            >
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
