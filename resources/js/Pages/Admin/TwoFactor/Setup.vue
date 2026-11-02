<template>
    <v-app>
        <v-main class="d-flex align-center justify-center pa-6">
            <v-card class="w-100" max-width="480" rounded="lg" elevation="0" border>
                <v-card-text class="pa-6">
                    <!-- Recovery codes: shown once, right after setup or after making new ones. -->
                    <template v-if="recoveryCodes && recoveryCodes.length">
                        <h1 class="text-h6 font-weight-bold mb-1">رموز الاسترداد</h1>
                        <p class="text-body-2 text-medium-emphasis mb-4">
                            استخدم أحد هذه الرموز للدخول إذا فقدت هاتفك. كل رمز يعمل مرة واحدة.
                        </p>
                        <v-alert type="warning" variant="tonal" density="compact" class="mb-4 text-body-2">
                            احفظها الآن في مكان آمن (مدير كلمات المرور مثلًا). لن تظهر مرة أخرى.
                        </v-alert>
                        <div class="codes mb-4" dir="ltr">
                            <code v-for="item in recoveryCodes" :key="item">{{ item }}</code>
                        </div>
                        <v-btn variant="tonal" block class="mb-3" prepend-icon="tabler:copy" @click="copy(recoveryCodes.join('\n'))">
                            {{ copied ? 'تم النسخ' : 'نسخ الرموز' }}
                        </v-btn>
                        <v-btn color="primary" block @click="router.visit(route('admin.dashboard'))">
                            حفظتها، متابعة
                        </v-btn>
                    </template>

                    <!-- Already set up: make new recovery codes. -->
                    <template v-else-if="enabled">
                        <h1 class="text-h6 font-weight-bold mb-1">التحقق بخطوتين</h1>
                        <p class="text-body-2 text-medium-emphasis mb-6">
                            مفعّل لهذا الحساب. رموز الاسترداد المتبقية: {{ recoveryCodesLeft }}.
                        </p>
                        <p class="text-body-2 mb-3">
                            لإنشاء رموز استرداد جديدة (تُلغى القديمة) أدخل الرمز الحالي من التطبيق.
                        </p>
                        <v-form @submit.prevent="regenerate">
                            <v-text-field
                                v-model="form.code"
                                label="رمز التحقق"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                pattern="[0-9]*"
                                maxlength="6"
                                dir="ltr"
                                :error-messages="form.errors.code"
                                class="mb-4"
                            />
                            <v-btn type="submit" color="primary" block :loading="form.processing">
                                رموز استرداد جديدة
                            </v-btn>
                        </v-form>
                        <div class="text-center mt-4">
                            <Link class="text-body-2" :href="route('admin.dashboard')">رجوع إلى الإدارة</Link>
                        </div>
                    </template>

                    <!-- First setup. -->
                    <template v-else>
                        <h1 class="text-h6 font-weight-bold mb-1">إعداد التحقق بخطوتين</h1>
                        <p class="text-body-2 text-medium-emphasis mb-6">
                            مطلوب لكل حسابات الإدارة. ستحتاج تطبيق مصادقة على هاتفك
                            (Google Authenticator أو Microsoft Authenticator أو غيرهما).
                        </p>

                        <p class="text-body-2 font-weight-medium mb-2">1. أضف الحساب إلى التطبيق</p>
                        <v-btn
                            :href="otpauthUrl"
                            variant="tonal"
                            block
                            class="mb-3"
                            prepend-icon="tabler:phone"
                        >
                            فتح في تطبيق المصادقة
                        </v-btn>
                        <p class="text-body-2 text-medium-emphasis mb-2">
                            أو أدخل هذا المفتاح يدويًا (نوع الرمز: حسب الوقت):
                        </p>
                        <div class="secret mb-1" dir="ltr">{{ groupedSecret }}</div>
                        <div class="text-caption text-medium-emphasis mb-2" dir="ltr">{{ issuer }} · {{ account }}</div>
                        <v-btn variant="text" size="small" prepend-icon="tabler:copy" class="mb-6" @click="copy(secret)">
                            {{ copied ? 'تم النسخ' : 'نسخ المفتاح' }}
                        </v-btn>

                        <p class="text-body-2 font-weight-medium mb-2">2. أدخل الرمز الظاهر في التطبيق</p>
                        <v-form @submit.prevent="confirm">
                            <v-text-field
                                v-model="form.code"
                                label="رمز التحقق"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                pattern="[0-9]*"
                                maxlength="6"
                                dir="ltr"
                                :error-messages="form.errors.code"
                                class="mb-4"
                            />
                            <v-btn type="submit" color="primary" block :loading="form.processing">
                                تفعيل
                            </v-btn>
                        </v-form>
                        <div class="text-center mt-4">
                            <a href="#" class="text-body-2" @click.prevent="router.post(route('admin.logout'))">خروج</a>
                        </div>
                    </template>
                </v-card-text>
            </v-card>
        </v-main>
    </v-app>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';

// Full-page screens with their own v-app (no admin bar until the second factor is done).
defineOptions({ layout: null });

const props = defineProps({
    enabled: { type: Boolean, default: false },
    secret: { type: String, default: '' },
    otpauthUrl: { type: String, default: '' },
    issuer: { type: String, default: '' },
    account: { type: String, default: '' },
    recoveryCodes: { type: Array, default: null },
    recoveryCodesLeft: { type: Number, default: 0 },
});

const form = useForm({ code: '' });
const copied = ref(false);

const groupedSecret = computed(() => (props.secret.match(/.{1,4}/g) || []).join(' '));

async function copy(text) {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        const area = document.createElement('textarea');
        area.value = text;
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

function confirm() {
    form.post(route('admin.two-factor.confirm'), {
        onFinish: () => form.reset('code'),
    });
}

function regenerate() {
    form.post(route('admin.two-factor.recovery-codes'), {
        onFinish: () => form.reset('code'),
    });
}
</script>

<style scoped>
.secret {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 1.05rem;
    letter-spacing: 0.06em;
    word-break: break-all;
    padding: 10px 12px;
    border-radius: 8px;
    background: rgba(var(--v-theme-on-surface), 0.05);
    user-select: all;
}

.codes {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.codes code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    text-align: center;
    padding: 8px;
    border-radius: 8px;
    background: rgba(var(--v-theme-on-surface), 0.05);
}
</style>
