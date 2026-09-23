export default {
    data() {
        return {
            selectedLang: '',

            menuIsOpen:false,
            menuStyle: {},
            totalSpace: 0,
            lineMenu: [],
            hamburgerMenu: [],
            breakWidths: [],
        }
    },
    mounted() {
        this.selectedLang = this.getSelectedLang();
        this.lineMenu = this.field.languages;

        window.addEventListener("resize", this.calculateMenu);
        window.addEventListener("resize", this.closeMenu);
        window.addEventListener("scroll", this.closeMenu, true);

        this.$nextTick(() => {
            this.$refs.tabItem.forEach(col => {
                this.totalSpace += col.clientWidth;
                this.breakWidths.push(this.totalSpace);
            })

            this.calculateMenu();
        })
    },
    unmounted() {
        window.removeEventListener("resize", this.calculateMenu);
        window.removeEventListener("resize", this.closeMenu);
        window.removeEventListener("scroll", this.closeMenu, true);
    },
    computed: {
        reversedHamburgerMenu(){
            return this.hamburgerMenu.slice().reverse();
        }
    },
    methods: {
        calculateMenu(){
            let tabItems = this.$refs.tabItems;
            let availableSpace = tabItems.clientWidth - ( 24 * 2 ) - 40; // 24*2=padding, 15=?, 40=hamburgerMenu
            let numOfVisibleItems = this.lineMenu.length;
            let requiredSpace = this.breakWidths[numOfVisibleItems - 1];

            if (requiredSpace > availableSpace) {
                this.hamburgerMenu.push(this.lineMenu[this.lineMenu.length - 1]);
                this.lineMenu.pop();
                numOfVisibleItems -= 1;
                this.calculateMenu();
                // There is more than enough space
            } else if (availableSpace > this.breakWidths[numOfVisibleItems]) {
                this.lineMenu.push(this.hamburgerMenu[this.hamburgerMenu.length - 1]);
                this.hamburgerMenu.pop();
                numOfVisibleItems += 1;
                this.calculateMenu();
            }
        },
        /**
         * The panel around the field clips overflow, so an absolutely positioned dropdown gets cut off (issue #46).
         * Pin it to the viewport under the icon instead; it closes on scroll/resize rather than drifting away.
         */
        toggleMenu(event){
            if (this.menuIsOpen) return this.closeMenu();

            const rect = event.currentTarget.getBoundingClientRect();

            this.menuStyle = {
                position: 'fixed',
                top: rect.bottom + 'px',
                right: (document.documentElement.clientWidth - rect.right) + 'px',
                maxHeight: Math.max(120, Math.min(300, window.innerHeight - rect.bottom - 8)) + 'px',
                zIndex: 50,
            };
            this.menuIsOpen = true;
        },
        closeMenu(event){
            // scrolling inside the dropdown itself must not close it
            if (event && event.type === 'scroll' && event.target instanceof Element && event.target.closest('.hamburger-content')) return;

            this.menuIsOpen = false;
        },
        switchLanguage(lang){
            this.selectedLang = lang;
            this.menuIsOpen = false;

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
