<template>
    <div v-if="siteKey" class="recaptcha-box">
        <div ref="el" />
        <small v-if="error" class="text-error d-block mt-1">{{ error }}</small>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';

// Google reCAPTCHA v2 checkbox (as in rqmana's apply form). Without a site key (local
// development) nothing renders and the server skips the check.
const props = defineProps({
    siteKey: { type: String, default: '' },
    error: { type: String, default: '' },
});

const el = ref(null);
let widgetId = null;

function render() {
    if (!el.value || widgetId !== null || !window.grecaptcha?.render) {
        return;
    }

    widgetId = window.grecaptcha.render(el.value, { sitekey: props.siteKey });
}

onMounted(() => {
    if (!props.siteKey) {
        return;
    }

    window.onTulabiRecaptchaLoad = () => render();

    if (window.grecaptcha?.render) {
        render();

        return;
    }

    if (document.querySelector('script[src*="recaptcha/api.js"]')) {
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://www.google.com/recaptcha/api.js?hl=ar&onload=onTulabiRecaptchaLoad&render=explicit';
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
});

function token() {
    return widgetId !== null ? (window.grecaptcha?.getResponse(widgetId) || '') : '';
}

function reset() {
    if (widgetId !== null) {
        window.grecaptcha?.reset(widgetId);
    }
}

defineExpose({ token, reset });
</script>

<style scoped>
.recaptcha-box {
    margin: 4px 0 8px;
}
</style>
