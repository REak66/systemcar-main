import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { createI18n } from 'vue-i18n';
import en from './locales/en.js';
import km from './locales/km.js';
import zh from './locales/zh.js';

import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ConfirmationService from 'primevue/confirmationservice';
import Select from 'primevue/select';
import Skeleton from 'primevue/skeleton';
import ProgressSpinner from 'primevue/progressspinner';
import AnimateOnScroll from 'primevue/animateonscroll';
import ConfirmPopup from 'primevue/confirmpopup';
import SplitButton from 'primevue/splitbutton';
import ToggleSwitch from 'primevue/toggleswitch';
import ToggleButton from 'primevue/togglebutton';
import DatePicker from 'primevue/datepicker';
import AutoComplete from 'primevue/autocomplete';
import 'primeicons/primeicons.css';

const appName = document.title || 'Car System';

const i18n = createI18n({
    legacy: false,
    locale: localStorage.getItem('locale') || 'en',
    fallbackLocale: 'en',
    messages: { en, km, zh },
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .use(PrimeVue, { theme: { preset: Aura } })
            .use(ConfirmationService)
            .directive('animateonscroll', AnimateOnScroll)
            .component('Select', Select)
            .component('Skeleton', Skeleton)
            .component('ProgressSpinner', ProgressSpinner)
            .component('ConfirmPopup', ConfirmPopup)
            .component('SplitButton', SplitButton)
            .component('ToggleSwitch', ToggleSwitch)
            .component('ToggleButton', ToggleButton)
            .component('DatePicker', DatePicker)
            .component('AutoComplete', AutoComplete)
            .mount(el);
    },
    progress: {
        color: '#1d4ed8',
    },
});
