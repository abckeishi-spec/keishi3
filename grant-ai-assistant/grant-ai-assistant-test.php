<?php
/**
 * Plugin Name: Grant AI Assistant (Test Version)
 * Description: AI対話型助成金検索機能 - テスト版
 * Version: 1.0.1-test
 * Author: Grant Insight Team
 * Requires at least: 5.8
 * Requires PHP: 7.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// 基本的な動作テスト
function gaa_test_activation() {
    // 単純な動作テスト
    if (!function_exists('gi_render_card')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Grant Insight Perfectテーマが必要です。');
    }
    
    // テスト成功メッセージ
    add_option('gaa_test_activated', true);
}
register_activation_hook(__FILE__, 'gaa_test_activation');

// 管理画面通知
add_action('admin_notices', function() {
    if (get_option('gaa_test_activated')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Grant AI Assistant (Test)</strong>: プラグインが正常に有効化されました。</p>';
        echo '</div>';
        delete_option('gaa_test_activated');
    }
});

// 簡単なショートコードテスト
add_shortcode('grant_ai_test', function($atts) {
    $atts = shortcode_atts(array(
        'message' => 'Grant AI Assistant テスト版が動作しています'
    ), $atts);
    
    return '<div style="padding: 20px; background: #f0f8ff; border: 2px solid #0073aa; border-radius: 8px; margin: 20px 0;">' .
           '<h3>🤖 ' . esc_html($atts['message']) . '</h3>' .
           '<p>✅ WordPress統合: 正常</p>' .
           '<p>✅ gi_render_card関数: ' . (function_exists('gi_render_card') ? '利用可能' : '未検出') . '</p>' .
           '<p>✅ gi_safe_get_meta関数: ' . (function_exists('gi_safe_get_meta') ? '利用可能' : '未検出') . '</p>' .
           '<p>✅ grant投稿タイプ: ' . (post_type_exists('grant') ? '検出済み' : '未検出') . '</p>' .
           '</div>';
});

// デバッグ情報表示
add_action('wp_footer', function() {
    if (current_user_can('manage_options') && isset($_GET['gaa_debug'])) {
        echo '<!-- Grant AI Assistant Debug Info -->';
        echo '<div style="position: fixed; bottom: 20px; right: 20px; background: white; padding: 15px; border: 2px solid #0073aa; border-radius: 8px; z-index: 9999; max-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">';
        echo '<h4>🔧 Debug Info</h4>';
        echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
        echo '<p><strong>WordPress:</strong> ' . get_bloginfo('version') . '</p>';
        echo '<p><strong>Theme:</strong> ' . wp_get_theme()->get('Name') . '</p>';
        echo '<p><strong>Plugin Path:</strong> ' . plugin_dir_path(__FILE__) . '</p>';
        echo '</div>';
    }
});