<template>
    <div class='flex flex-col md:flex-row'>
        <div class="px-6 md:px-8 mt-2 md:mt-0 pb-5 w-full md:w-4/5 md:py-5">
            <div class="relative w-full">
                <v-select
                    :options="options"
                    :class="{'form-control form-select form-select-bordered': true, classNames}"
                    :modelValue="value"
                    @update:modelValue="input"></v-select>
            </div>
        </div>
    </div>
</template>

<script>

import 'vue-select/dist/vue-select.css';

export default {

    props: ['options', 'classNames', 'value', 'input'],

}

</script>

<style scoped>
:global(.dark){
    --vs-custom-color: #664cc3;
    --vs-custom-background: #282c34;

    --vs-controls-color: rgba(var(--colors-gray-400));

    --vs-dropdown-bg: rgba(var(--colors-gray-900));
    --vs-dropdown-color: rgba(var(--colors-gray-400));
    --vs-dropdown-option-color: rgba(var(--colors-gray-400));

    --vs-selected-bg: rgba(var(--colors-gray-400));
    --vs-selected-color: #eeeeee;

    --vs-search-input-color: #eeeeee;

    --vs-dropdown-option--active-bg: rgba(var(--colors-gray-400));
    --vs-dropdown-option--active-color: #eeeeee;
}

:deep(.vs__dropdown-toggle) {
    border-width: 0;
}
</style>
