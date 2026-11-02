<template>
    <div
        class="form-plain-field form-plain-rich-text"
        :class="{ 'form-plain-rich-text--error': hasError }"
    >
        <label
            v-if="hasLabel"
            class="form-plain-field__label d-inline-block pb-4 font-weight-medium"
        >
            <span class="me-1">{{ label }}</span>
            <span v-if="showRequiredMark" class="text-error">*</span>
        </label>

        <div
            class="form-plain-rich-text__shell"
            :class="{ 'form-plain-rich-text__shell--focused': focused }"
        >
            <div
                v-if="editor"
                class="form-plain-rich-text__toolbar"
                role="toolbar"
            >
                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :class="{ 'is-active': editor.isActive('bold') }"
                    :title="t('editor.bold')"
                    :disabled="disabled"
                    @click="editor.chain().focus().toggleBold().run()"
                >
                    <v-icon icon="mdi-format-bold" size="18" />
                </button>
                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :class="{ 'is-active': editor.isActive('italic') }"
                    :title="t('editor.italic')"
                    :disabled="disabled"
                    @click="editor.chain().focus().toggleItalic().run()"
                >
                    <v-icon icon="mdi-format-italic" size="18" />
                </button>
                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :class="{ 'is-active': editor.isActive('underline') }"
                    :title="t('editor.underline')"
                    :disabled="disabled"
                    @click="editor.chain().focus().toggleUnderline().run()"
                >
                    <v-icon icon="mdi-format-underline" size="18" />
                </button>
                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :class="{ 'is-active': editor.isActive('strike') }"
                    :title="t('editor.strike')"
                    :disabled="disabled"
                    @click="editor.chain().focus().toggleStrike().run()"
                >
                    <v-icon icon="mdi-format-strikethrough" size="18" />
                </button>

                <span class="form-plain-rich-text__divider" />

                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
                    :title="t('editor.heading')"
                    :disabled="disabled"
                    @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                >
                    <v-icon icon="mdi-format-header-2" size="18" />
                </button>
                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :class="{ 'is-active': editor.isActive('bulletList') }"
                    :title="t('editor.bullet_list')"
                    :disabled="disabled"
                    @click="editor.chain().focus().toggleBulletList().run()"
                >
                    <v-icon icon="mdi-format-list-bulleted" size="18" />
                </button>
                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :class="{ 'is-active': editor.isActive('orderedList') }"
                    :title="t('editor.ordered_list')"
                    :disabled="disabled"
                    @click="editor.chain().focus().toggleOrderedList().run()"
                >
                    <v-icon icon="mdi-format-list-numbered" size="18" />
                </button>

                <span class="form-plain-rich-text__divider" />

                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :class="{ 'is-active': editor.isActive('link') }"
                    :title="t('editor.link')"
                    :disabled="disabled"
                    @click="toggleLink"
                >
                    <v-icon icon="mdi-link-variant" size="18" />
                </button>
                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :title="t('editor.unlink')"
                    :disabled="disabled || !editor.isActive('link')"
                    @click="editor.chain().focus().unsetLink().run()"
                >
                    <v-icon icon="mdi-link-variant-off" size="18" />
                </button>

                <span class="form-plain-rich-text__divider" />

                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :title="t('editor.undo')"
                    :disabled="disabled || !editor.can().undo()"
                    @click="editor.chain().focus().undo().run()"
                >
                    <v-icon icon="mdi-undo" size="18" />
                </button>
                <button
                    type="button"
                    class="form-plain-rich-text__btn"
                    :title="t('editor.redo')"
                    :disabled="disabled || !editor.can().redo()"
                    @click="editor.chain().focus().redo().run()"
                >
                    <v-icon icon="mdi-redo" size="18" />
                </button>
            </div>

            <editor-content
                :editor="editor"
                class="form-plain-rich-text__content"
                :style="{ minHeight: contentMinHeight }"
            />
        </div>

        <div
            v-if="resolvedErrorMessages.length"
            class="form-plain-rich-text__messages text-error text-caption mt-1"
        >
            <div
                v-for="(message, index) in resolvedErrorMessages"
                :key="`${index}-${message}`"
            >
                {{ message }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import './formPlainControl.css';
import './formPlainRichText.css';
import { useResolvedErrorMessages } from '@/Components/formFields/useResolvedErrorMessages';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: '',
    },
    errorMessages: {
        type: [String, Array, Object],
        default: () => [],
    },
    rows: {
        type: [Number, String],
        default: 8,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    fieldAttrs: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const focused = ref(false);

function isSafeEditorHref(url) {
    const trimmed = String(url ?? '').trim();

    if (!trimmed) {
        return false;
    }

    if (/^\s*javascript\s*:/iu.test(trimmed)
        || /^\s*data\s*:/iu.test(trimmed)
        || /^\s*vbscript\s*:/iu.test(trimmed)) {
        return false;
    }

    return /^(https?:\/\/|mailto:|\/(?!\/)|#)/iu.test(trimmed);
}

const hasLabel = computed(() => props.label.trim() !== '');
const showRequiredMark = computed(() => props.required && hasLabel.value);
const resolvedPlaceholder = computed(() =>
    props.placeholder !== '' ? props.placeholder : props.label,
);
const resolvedErrorMessages = useResolvedErrorMessages(() => props.errorMessages);
const hasError = computed(() => resolvedErrorMessages.value.length > 0);

const contentMinHeight = computed(() => {
    const rows = Number(props.rows) || 8;

    return `${Math.max(rows, 4) * 1.5}rem`;
});

const editor = useEditor({
    content: props.modelValue || '',
    editable: !props.disabled,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            link: {
                openOnClick: false,
                HTMLAttributes: {
                    rel: 'noopener noreferrer nofollow',
                    target: '_blank',
                },
                validate: (href) => isSafeEditorHref(href),
                isAllowedUri: (url) => isSafeEditorHref(url),
            },
        }),
        Placeholder.configure({
            placeholder: () => resolvedPlaceholder.value || '',
        }),
    ],
    editorProps: {
        attributes: {
            class: 'form-plain-rich-text__prose',
            dir: 'rtl',
            ...(props.fieldAttrs?.name ? { 'data-name': props.fieldAttrs.name } : {}),
        },
    },
    onFocus: () => {
        focused.value = true;
    },
    onBlur: () => {
        focused.value = false;
    },
    onUpdate: ({ editor: current }) => {
        emit('update:modelValue', current.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) {
            return;
        }

        const next = value || '';
        const current = editor.value.getHTML();

        if (next !== current) {
            editor.value.commands.setContent(next, { emitUpdate: false });
        }
    },
);

watch(
    () => props.disabled,
    (disabled) => {
        editor.value?.setEditable(!disabled);
    },
);

watch(resolvedPlaceholder, () => {
    editor.value?.view.dispatch(editor.value.state.tr);
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function toggleLink() {
    if (!editor.value || props.disabled) {
        return;
    }

    if (editor.value.isActive('link')) {
        editor.value.chain().focus().unsetLink().run();

        return;
    }

    const previous = editor.value.getAttributes('link').href || '';
    const url = window.prompt(t('editor.link_prompt'), previous);

    if (url === null) {
        return;
    }

    const trimmed = url.trim();

    if (!trimmed) {
        editor.value.chain().focus().unsetLink().run();

        return;
    }

    if (!isSafeEditorHref(trimmed)) {
        window.alert(t('editor.link_invalid'));

        return;
    }

    editor.value
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: trimmed })
        .run();
}
</script>
