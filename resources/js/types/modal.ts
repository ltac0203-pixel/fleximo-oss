/**
 * モーダル共通 Props。
 * 基盤 {@link @/Components/Modal} を用いる個別モーダルはこれらを継承することで
 * show / onClose / onSuccess の命名と型を揃える。
 */

export interface BaseModalProps {
    show: boolean;
    onClose: () => void;
}

export interface FormModalProps<TSuccessArg = void> extends BaseModalProps {
    onSuccess?: TSuccessArg extends void
        ? () => void
        : (arg: TSuccessArg) => void;
}
