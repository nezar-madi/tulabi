export const plainFieldValidateOnBlur = {
    validateOnBlur: true,
    validateOnChange: false,
    validateOnInput: false,
    validateOnModelUpdate: false,
};

export const plainFieldValidateOnModelUpdate = {
    validateOnBlur: false,
    validateOnChange: false,
    validateOnInput: false,
    validateOnModelUpdate: true,
};

export const plainFieldValidateOnOverlayCommit = {
    validateOnBlur: true,
    validateOnChange: false,
    validateOnInput: false,
    validateOnModelUpdate: true,
};

export function overlayControlAttrs(fieldAttrs = {}) {
    const { onBlur, ...rest } = fieldAttrs;

    return rest;
}

export function commitOverlayFieldBlur(fieldAttrs) {
    fieldAttrs?.onBlur?.();
}
