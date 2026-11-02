<template>
    <v-btn class="rq-btn" variant="tonal" color="primary" @click="copy">
        <v-icon :icon="copied ? 'tabler:check' : 'tabler:copy'" start />
        {{ copied ? $t('common.copied') : (label || $t('common.copy')) }}
    </v-btn>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    text: { type: String, required: true },
    label: { type: String, default: '' },
});

const copied = ref(false);

async function copy() {
    try {
        await navigator.clipboard.writeText(props.text);
    } catch {
        const area = document.createElement('textarea');
        area.value = props.text;
        document.body.appendChild(area);
        area.select();
        document.execCommand('copy');
        area.remove();
    }

    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}
</script>
