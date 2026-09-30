import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { useToast } from './composables/useToast';

// Global axios config
import axios from 'axios';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const app = createApp(App);

app.use(router);

// Global properties
app.config.globalProperties.$user = window.__AUTH_USER__;
app.config.globalProperties.$toast = useToast();
app.config.globalProperties.$formatRupiah = (val) => {
    if (!val && val !== 0) return '-';
    return 'Rp ' + Number(val).toLocaleString('id-ID');
};

app.mount('#app');
