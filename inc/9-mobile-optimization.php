<?php
/**
 * Grant Insight Perfect - Simple Mobile Optimization Functions
 * シンプルなモバイル最適化機能
 * 
 * @package Grant_Insight_Perfect
 * @version 7.0-simplified
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * シンプルなモバイル判定（WordPressの標準機能を使用）
 */
if (!function_exists('gi_is_mobile_device')) {
    function gi_is_mobile_device() {
        return wp_is_mobile();
    }
}

/**
 * レスポンシブグリッドクラス生成
 */
if (!function_exists('gi_get_responsive_grid_classes')) {
    function gi_get_responsive_grid_classes($desktop_cols = 3, $gap = 6) {
        return "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{$desktop_cols} gap-{$gap}";
    }
}

/**
 * モバイル向けのコンテンツ最適化
 */
function gi_mobile_content_optimizations() {
    if (!gi_is_mobile_device()) {
        return;
    }
    
    // モバイル用のスタイル調整
    add_action('wp_head', function() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">';
        echo '<style>
            body { font-size: 16px; line-height: 1.6; }
            .mobile-hidden { display: none !important; }
            .grant-card { margin-bottom: 1rem; }
            .search-filters { padding: 1rem; }
        </style>';
    });
}
add_action('init', 'gi_mobile_content_optimizations');