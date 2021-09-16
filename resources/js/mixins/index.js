export default {
    data() {
        return {
            selectedLang: '',
        }
    },
    mounted() {
        this.selectedLang = this.getSelectedLang();
    },
    methods: {
        switchLanguage(lang){
            this.selectedLang = lang;

            localStorage.setItem(this.getStorageName()+'lastSelectedLang', lang);
        },
        getSelectedLang(){
            if(this.field.saveLastSelectedLang){
                let lastSelectedLang = localStorage.getItem(this.getStorageName()+'lastSelectedLang');

                if (lastSelectedLang && this.field.languages.includes(lastSelectedLang))
                    return lastSelectedLang;
            }

            return this.field.languages[0] ? this.field.languages[0] : '';
        },
        getStorageName(){
            return 'kongulov/nova-tab-translatable:';
        },
        componentName(component){
            return component.name.replace(' ['+component.locale+']', '');
        }
    },
};
