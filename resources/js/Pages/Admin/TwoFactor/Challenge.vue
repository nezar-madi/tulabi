<template>
    <v-app>
        <v-main class="d-flex align-center justify-center pa-6">
            <v-card class="w-100" max-width="440" rounded="lg" elevation="0" border>
                <v-card-text class="pa-6">
                    <h1 class="text-h6 font-weight-bold mb-1">التحقق بخطوتين</h1>
                    <p class="text-body-2 text-medium-emphasis mb-6">
                        <template v-if="useRecovery">
                            أدخل أحد رموز الاسترداد التي حفظتها. كل رمز يعمل مرة واحدة.
                        </template>
                        <template v-else>
                            أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة.
                        </template>
                    </p>

                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-if="useRecovery"
                            :key="'recovery'"
                            v-model="form.recovery_code"
                            label="رمز الاسترداد"
                            placeholder="XXXX-XXXX"
                            autocomplete="off"
                            autocapitalize="characters"
                            spellcheck="false"
                            dir="ltr"
                            autofocus
                            :error-messages="form.errors.recovery_code"
                            class="mb-4"
                        />
                        <v-text-field
                            v-else
                            :key="'code'"
                            v-model="form.code"
                            label="رمز التحقق"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            pattern="[0-9]*"
                            maxlength="6"
                            dir="ltr"
                            autofocus
                            :error-messages="form.errors.code"
                            class="mb-4"
                        />
                        <v-btn type="submit" color="primary" block :loading="form.processing">
                            تحقق
                        </v-btn>
                    </v-form>

                    <div class="d-flex justify-space-between mt-4">
                        <a href="#" class="text-body-2" @click.prevent="toggle">
                            {{ useRecovery ? 'استخدام رمز التطبيق' : 'استخدام رمز استرداد' }}
                        </a>
                        <Link class="text-body-2" :href="route('admin.login')">رجوع</Link>
                    </div>
                </v-card-text>
            </v-card>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: null });

const useRecovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

function toggle() {
    useRecovery.value = !useRecovery.value;
    form.reset();
    form.clearErrors();
}

function submit() {
    form.transform((data) => (useRecovery.value
        ? { recovery_code: data.recovery_code }
        : { code: data.code }))
        .post(route('admin.two-factor.verify'), {
            onFinish: () => form.reset('code'),
        });
}
</script>
