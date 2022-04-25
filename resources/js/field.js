//import {Errors} from "laravel-nova";

import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, Vue, router, store) => {
    app.component('index-nova-tab-translatable', IndexField)
    app.component('detail-nova-tab-translatable', DetailField)
    app.component('form-nova-tab-translatable', FormField)
})
