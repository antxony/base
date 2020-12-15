/** @module ButtonCheckGroup */

export interface checkOptions {
    onChange?: (value: string[]) => void;
    multiple?: boolean
    unCheckClass?: string;
    checkClass?: string;
    extraClass?: string;
}

export enum checkAttributes {
    STATUS = 'status',
    ID = 'id',
    VALUE = 'value'
}

export const DEFAULT_CHECK_OPTIONS: checkOptions = {
    onChange: () => {},
    multiple: false,
    unCheckClass: 'button-check-unchecked',
    checkClass: 'button-check-checked',
    extraClass: ''
}
