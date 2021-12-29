import {Errors} from "laravel-nova";

Nova.booting((Vue, router, store) => {
    Vue.component('index-nova-tab-translatable', require('./components/IndexField'))
    Vue.component('detail-nova-tab-translatable', require('./components/DetailField'))
    Vue.component('form-nova-tab-translatable', require('./components/FormField'))

    const BaseFormFileField = Vue.options.components["form-file-field"];
    const CustomFormFileField = BaseFormFileField.extend({
        props: [
            'resourceId',
            'relatedResourceName',
            'relatedResourceId',
            'viaRelationship',
            'dataDefault',
        ],
        methods: {
            async removeFile() {
                this.uploadErrors = new Errors()

                const {
                    resourceName,
                    resourceId,
                    relatedResourceName,
                    relatedResourceId,
                    viaRelationship,
                } = this
                const attribute = this.field.attribute

                var uri =
                    this.viaRelationship &&
                    this.relatedResourceName &&
                    this.relatedResourceId
                        ? `/nova-api/kongulov/nova-tab-translatable/${resourceName}/${resourceId}/${relatedResourceName}/${relatedResourceId}/field/${attribute}?viaRelationship=${viaRelationship}`
                        : `/nova-api/kongulov/nova-tab-translatable/${resourceName}/${resourceId}/field/${attribute}`

                if (!this.dataDefault){
                    uri =
                        this.viaRelationship &&
                        this.relatedResourceName &&
                        this.relatedResourceId
                            ? `/nova-api/${resourceName}/${resourceId}/${relatedResourceName}/${relatedResourceId}/field/${attribute}?viaRelationship=${viaRelationship}`
                            : `/nova-api/${resourceName}/${resourceId}/field/${attribute}`
                }

                try {
                    await Nova.request().delete(uri)
                    this.closeRemoveModal()
                    this.deleted = true
                    this.$emit('file-deleted')
                    Nova.success(this.__('The file was deleted!'))
                } catch (error) {
                    this.closeRemoveModal()

                    if (error.response.status == 422) {
                        this.uploadErrors = new Errors(error.response.data.errors)
                    }
                }
            },
        }
    })
    Vue.component('form-file-field', CustomFormFileField)

    const BaseDetailFileField = Vue.options.components["detail-file-field"];
    const CustomDetailFileField = BaseDetailFileField.extend({
        props: ['resource', 'resourceName', 'resourceId', 'field', 'dataDefault'],
        methods: {
            download() {
                const { resourceName, resourceId } = this
                const attribute = this.field.attribute

                let link = document.createElement('a')

                link.href = `/nova-api/kongulov/nova-tab-translatable/${resourceName}/${resourceId}/download/${attribute}`
                if (!this.dataDefault) {
                    link.href = `/nova-api/${resourceName}/${resourceId}/download/${attribute}`
                }

                link.download = 'download'
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
            },
        }
    })
    Vue.component('detail-file-field', CustomDetailFileField)
})
