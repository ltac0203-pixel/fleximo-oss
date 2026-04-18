import React from 'react';

/**
 * CartButton のスタイル定数
 * - 視覚的な階層を保ちつつ、変更時の影響範囲を局所化
 */
const BUTTON_BASE_CLASSES =
    'w-full bg-sky-500 text-white font-medium py-3.5 px-4 flex items-center justify-center gap-2 shadow-md';

const BUTTON_INTERACTION_CLASSES =
    'hover:bg-sky-600 hover:shadow-lg active:bg-sky-700 active:shadow-sm active:scale-[0.98] transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2';

interface CartButtonProps {
    /** カート内のアイテム数 */
    itemCount: number;
    /** クリック時のハンドラ */
    onClick: () => void;
    /** 追加のCSSクラス（親コンポーネントからのスタイル上書き用） */
    className?: string;
}

/**
 * カートボタンコンポーネント
 *
 * 用途:
 * - モバイルオーダーのカート表示ボタン
 * - カート内のアイテム数をバッジで表示
 *
 * レイアウト責務:
 * - このコンポーネントはボタン本体のみを描画
 * - 固定配置（fixed）やpadding等のレイアウトは親コンポーネント（CustomerLayout）が管理
 *
 * @param itemCount - カート内のアイテム数
 * @param onClick - クリック時のハンドラ
 * @param className - 追加のCSSクラス
 */
export default function CartButton({ itemCount, onClick, className = '' }: CartButtonProps) {
    return (
        <button
            onClick={onClick}
            className={`${BUTTON_BASE_CLASSES} ${BUTTON_INTERACTION_CLASSES} ${className}`}
        >
            {/* カートアイコン */}
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                />
            </svg>

            {/* ボタンテキスト */}
            カートを見る

            {/* カウントバッジ（アイテムがある場合のみ表示） */}
            {itemCount > 0 && (
                <span className="min-w-[24px] h-6 flex items-center justify-center bg-white text-sky-600 text-sm font-bold px-2 py-0.5 rounded-full">
                    {itemCount}
                </span>
            )}
        </button>
    );
}
