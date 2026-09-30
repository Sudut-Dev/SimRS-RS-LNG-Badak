import { ref } from 'vue';
import axios from 'axios';

export function useApi() {
    const loading = ref(false);
    const errors = ref({});

    const request = async (method, url, data = null, params = null) => {
        loading.value = true;
        errors.value = {};
        try {
            const response = await axios({ method, url, data, params });
            return { success: true, data: response.data };
        } catch (err) {
            if (err.response?.status === 422) {
                errors.value = err.response.data.errors ?? {};
            }
            return {
                success: false,
                error: err.response?.data?.message ?? 'Terjadi kesalahan. Silakan coba lagi.',
                status: err.response?.status,
            };
        } finally {
            loading.value = false;
        }
    };

    const get    = (url, params) => request('GET', url, null, params);
    const post   = (url, data)   => request('POST', url, data);
    const put    = (url, data)   => request('PUT', url, data);
    const patch  = (url, data)   => request('PATCH', url, data);
    const del    = (url)         => request('DELETE', url);

    return { loading, errors, get, post, put, patch, del };
}
