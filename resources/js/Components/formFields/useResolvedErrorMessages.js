import { computed } from 'vue';
import { normalizeFieldErrors } from '@/utils/normalizeFieldErrors';


export function useResolvedErrorMessages(getMessages) {
    return computed(() => normalizeFieldErrors(getMessages()));
}
