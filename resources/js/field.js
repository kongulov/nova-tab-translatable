//import {Errors} from "laravel-nova";
import vSelect from "vue-select";

import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'
import Select from './components/Select'

Nova.booting((app, Vue, router, store) => {
    app.component('v-select', vSelect)
    app.component('select-nova-tab-translatable', Select)
    app.component('index-nova-tab-translatable', IndexField)
    app.component('detail-nova-tab-translatable', DetailField)
    app.component('form-nova-tab-translatable', FormField)
})
