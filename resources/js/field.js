Nova.booting((Vue, router, store) => {
  Vue.component('index-nova-tab-translatable', require('./components/IndexField'))
  Vue.component('detail-nova-tab-translatable', require('./components/DetailField'))
  Vue.component('form-nova-tab-translatable', require('./components/FormField'))
})
