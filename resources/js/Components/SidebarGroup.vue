<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: 'tabler:folder',
    },
});
</script>

<template>
    <v-list-group>
        <template #activator="{ props: activatorProps }">
            <v-list-item
                v-bind="activatorProps"
                rounded="sm"
                density="comfortable"
                color="primary"
                class="px-4"
            >
                <template #prepend>
                    <v-icon :icon="icon" :size="20" />
                </template>
                <v-list-item-title class="ms-0">{{ title }}</v-list-item-title>
            </v-list-item>
        </template>

        <slot />
    </v-list-group>
</template>
