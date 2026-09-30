import { ref, readonly } from 'vue';

const toasts = ref([]);
let nextId = 0;

export function useToast() {
    const add = (message, type = 'info', duration = 4000) => {
        const id = ++nextId;
        toasts.value.push({ id, message, type });
        setTimeout(() => remove(id), duration);
        return id;
    };

    const remove = (id) => {
        const idx = toasts.value.findIndex(t => t.id === id);
        if (idx !== -1) toasts.value.splice(idx, 1);
    };

    return {
        toasts: readonly(toasts),
        success: (msg, duration) => add(msg, 'success', duration),
        error:   (msg, duration) => add(msg, 'danger', duration ?? 6000),
        warning: (msg, duration) => add(msg, 'warning', duration),
        info:    (msg, duration) => add(msg, 'info', duration),
        remove,
    };
}
