<?php

return [

    /*
     * The source of supported locales on the application
     * Available selection "array", "database". Default array
     */
    'source' => 'array',

    /*
     * If you choose array selection, you should add all supported translation on it as "code"
     */
    'locales' => [
        'en', 'fr', 'es'
    ],

    /*
     * If you choose database selection, you should choose the model responsible for retrieving supported translations
     * And choose the 'code_field' for example "en", "fr", "es"...
     */
    'database' => [
        'model' => 'App\\Model\\Language',
        'code_field' => 'lang',
        'sort_by' => 'id',
        'sort_direction' => 'asc'
    ],

    /*
     * If you want to save the tab in the last selected language for the whole project, set this "true".
     * But if you want to use in one place call the saveLastSelectedLang(true|false) method
     */
    'save_last_selected_lang' => false,

    /*
     * If you have a large number of languages, we recommend changing the layout to "select"
     * Available selection "tabs", "select". Default tabs
     */
    'layout' => 'tabs'
];
