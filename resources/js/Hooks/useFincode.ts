import { useCallback, useEffect, useRef, useState } from "react";
import { logger } from "@/Utils/logger";
import { getErrorDetails, toError } from "@/Utils/errorHelpers";

// fincode CDN版（https://js.fincode.jp/v1/fincode.js）のグローバル型
declare global {
    interface Window {
        Fincode: FincodeInitializer | undefined;
    }
}

// Fincode(publicKey) → FincodeInstance を返す関数
type FincodeInitializer = (apiKey: string) => FincodeInstance;

// Fincode(publicKey) で返されるインスタンス
interface FincodeInstance {
    ui(appearance: FincodeAppearance): FincodeUI;
    tokens(
        cardData: FincodeCardData,
        callback: (status: number, response: FincodeTokenResponse) => void,
        errorCallback: () => void,
    ): void;
}

// fincode.ui(appearance) で返されるUIオブジェクト
// SDKソース準拠: create, mount, getFormData のみ公式定義
interface FincodeUI {
    create(method: "payments" | "cards" | "token", appearance: FincodeAppearance): void;
    mount(elementId: string, width: string): void;
    getFormData(): Promise<FincodeFormData>;
}

// UI外観カスタマイズ（SDK Appearance型に準拠）
interface FincodeAppearance {
    layout?: "vertical" | "horizontal";
    hideHolderName?: boolean;
    hidePayTimes?: boolean;
    hideLabel?: boolean;
    labelCardNo?: string;
    labelExpire?: string;
    labelCvc?: string;
    labelHolderName?: string;
    colorBackground?: string;
    colorBackgroundInput?: string;
    colorText?: string;
    colorBorder?: string;
    colorPlaceHolder?: string;
    colorLabelText?: string;
    colorError?: string;
    colorCheck?: string;
    fontFamily?: string;
}

interface FincodeCardData {
    card_no: string;
    expire: string;
    security_code: string;
    holder_name: string;
}

// getFormData() の戻り値（SDK FormData型に準拠）
interface FincodeFormData {
    cardNo?: string;
    expire?: string;
    CVC?: string;
    holderName?: string;
    payTimes?: string;
    method?: string;
}

interface FincodeTokenResponse {
    list?: Array<{ token: string }>;
    errors?: Array<{ error_code: string; error_message: string }>;
}

interface UseFincodeOptions {
    publicKey: string;
    isProduction: boolean;
}

interface UseFincodeReturn {
    isReady: boolean;
    isLoading: boolean;
    error: string | null;
    mountUI: (containerId: string) => void;
    unmountUI: () => void;
    createToken: () => Promise<string>;
    clearForm: () => void;
}

const FINCODE_JS_URL_PRODUCTION = "https://js.fincode.jp/v1/fincode.js";
const FINCODE_JS_URL_TEST = "https://js.test.fincode.jp/v1/fincode.js";
const CARD_FORM_MOUNT_ERROR = "カード入力フォームの表示に失敗しました";

const CARD_APPEARANCE: FincodeAppearance = {
    layout: "vertical",
    hideHolderName: true,
    hidePayTimes: true,
    labelCardNo: "カード番号",
    labelExpire: "有効期限",
    labelCvc: "セキュリティコード",
    colorBackground: "f8fafc",
    colorBackgroundInput: "ffffff",
    colorText: "1f2937",
    colorBorder: "e5e7eb",
    colorPlaceHolder: "9ca3af",
};

export function useFincode({ publicKey, isProduction }: UseFincodeOptions): UseFincodeReturn {
    const [isReady, setIsReady] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const fincodeRef = useRef<FincodeInstance | null>(null);
    const uiRef = useRef<FincodeUI | null>(null);
    const mountedContainerRef = useRef<string | null>(null);

    // マウント済みUIのクリーンアップ
    // SDK型にunmount()が定義されていないため、コンテナのDOM直接クリアで確実に解放する。
    const cleanupUI = useCallback(() => {
        if (mountedContainerRef.current) {
            const container = document.getElementById(mountedContainerRef.current);
            if (container) {
                container.innerHTML = "";
            }
            mountedContainerRef.current = null;
        }
        uiRef.current = null;
    }, []);

    // SDKスクリプト読み込みとFincode初期化
    useEffect(() => {
        if (!publicKey) {
            logger.error("Fincode public key is missing", new Error("fincode の公開キーが設定されていません"), {
                isProduction,
            });
            setError("fincode の公開キーが設定されていません");
            setIsLoading(false);
            return;
        }

        // キーのプレフィックスからSDK環境を自動判定し、設定との不一致を防止
        const isTestKey = publicKey.startsWith("p_test_");
        const effectiveIsProduction = isTestKey ? false : isProduction;

        if (isProduction !== effectiveIsProduction) {
            logger.warn("Fincode environment mismatch: key prefix does not match isProduction flag", {
                isProduction,
                keyPrefix: publicKey.substring(0, 7),
                effectiveEnvironment: effectiveIsProduction ? "production" : "test",
            });
        }

        const scriptUrl = effectiveIsProduction ? FINCODE_JS_URL_PRODUCTION : FINCODE_JS_URL_TEST;

        const initializeFincode = () => {
            if (!window.Fincode) {
                logger.error("Fincode global not available", undefined, { scriptUrl, isProduction });
                setError("fincode.js の読み込みに失敗しました");
                setIsLoading(false);
                return;
            }

            try {
                fincodeRef.current = window.Fincode(publicKey);
                setIsReady(true);
                setIsLoading(false);
            } catch (e: unknown) {
                logger.error("Fincode initialization failed", toError(e), { isProduction });
                setError("fincode の初期化に失敗しました");
                setIsLoading(false);
            }
        };

        // 画面再訪時の重複読み込みを避ける
        const existingScript = document.querySelector(`script[src="${scriptUrl}"]`);
        if (existingScript && window.Fincode) {
            initializeFincode();
            return () => {
                cleanupUI();
            };
        }

        const script = document.createElement("script");
        script.src = scriptUrl;
        script.async = true;

        script.onload = () => {
            initializeFincode();
        };

        script.onerror = () => {
            logger.error("Fincode script load failed", undefined, { scriptUrl, isProduction });
            setError("fincode.js の読み込みに失敗しました");
            setIsLoading(false);
        };

        document.head.appendChild(script);

        return () => {
            cleanupUI();
        };
    }, [publicKey, isProduction, cleanupUI]);

    // UI初期化 → 入力フォーム作成 → マウントを一連の流れで実行
    const mountUI = useCallback(
        (containerId: string) => {
            if (!fincodeRef.current) {
                logger.warn("Fincode mountUI called before initialization", { isProduction });
                setError("fincode が初期化されていません");
                return;
            }

            const mountTarget = document.getElementById(containerId);
            if (!mountTarget) {
                logger.warn("Fincode mount target not found", { containerId, isProduction });
                setError(CARD_FORM_MOUNT_ERROR);
                return;
            }

            // 前回のUIが残っている場合はコンテナをクリアして確実に解放
            cleanupUI();

            try {
                // ui(appearance): UIオブジェクトを生成
                const ui = fincodeRef.current.ui(CARD_APPEARANCE);

                // create(method, appearance): カード情報入力フォームを作成
                ui.create("payments", CARD_APPEARANCE);

                // mount(elementId, width): 指定コンテナにカード入力フォームをマウント
                ui.mount(containerId, "100%");

                uiRef.current = ui;
                mountedContainerRef.current = containerId;
                setError(null);
            } catch (e: unknown) {
                uiRef.current = null;
                mountedContainerRef.current = null;
                logger.error("Fincode UI mount failed", toError(e), {
                    containerId,
                    isProduction,
                    errorDetail: e instanceof Error ? e.message : String(e),
                });
                setError(CARD_FORM_MOUNT_ERROR);
            }
        },
        [isProduction, cleanupUI],
    );

    const unmountUI = useCallback(() => {
        cleanupUI();
    }, [cleanupUI]);

    // SDKのUIオブジェクトにclear()が存在する場合のみフォームリセットを実行
    const clearForm = useCallback(() => {
        const ui = uiRef.current as (FincodeUI & { clear?: () => void }) | null;
        if (ui && typeof ui.clear === "function") {
            try {
                ui.clear();
            } catch (e: unknown) {
                logger.warn("Fincode form clear failed", { error: getErrorDetails(e) });
            }
        }
    }, []);

    // カード情報をトークン化して返す
    const createToken = useCallback((): Promise<string> => {
        return new Promise((resolve, reject) => {
            if (!fincodeRef.current || !uiRef.current) {
                logger.warn("Fincode token requested before initialization", { isProduction });
                reject(new Error("fincode が初期化されていません"));
                return;
            }

            uiRef.current
                .getFormData()
                .then((formData) => {
                    if (!fincodeRef.current) {
                        reject(new Error("fincode が初期化されていません"));
                        return;
                    }

                    if (!formData.cardNo || !formData.expire) {
                        reject(new Error("カード情報が入力されていません"));
                        return;
                    }

                    fincodeRef.current.tokens(
                        {
                            card_no: formData.cardNo,
                            expire: formData.expire,
                            security_code: formData.CVC || "",
                            holder_name: formData.holderName || "",
                        },
                        (status, response) => {
                            if (status === 200 && response.list && response.list.length > 0) {
                                resolve(response.list[0].token);
                            } else if (response.errors && response.errors.length > 0) {
                                const errorMessage = response.errors.map((e) => e.error_message).join(", ");
                                logger.warn("Fincode tokenization failed", {
                                    status,
                                    errorCodes: response.errors.map((e) => e.error_code),
                                });
                                reject(new Error(errorMessage || "トークンの生成に失敗しました"));
                            } else {
                                logger.warn("Fincode tokenization returned no token", { status });
                                reject(new Error("トークンの生成に失敗しました"));
                            }
                        },
                        () => {
                            logger.error("Fincode tokenization error callback", undefined, { isProduction });
                            reject(new Error("トークンの生成に失敗しました"));
                        },
                    );
                })
                .catch((e: unknown) => {
                    logger.error("Fincode form data retrieval failed", toError(e), { isProduction });
                    reject(new Error("カード情報の取得に失敗しました"));
                });
        });
    }, [isProduction]);

    return {
        isReady,
        isLoading,
        error,
        mountUI,
        unmountUI,
        createToken,
        clearForm,
    };
}
