<?php
/**
 * Grant Insight Perfect - Performance Helpers
 * パフォーマンス最適化用のヘルパー関数
 * N+1クエリの解消とキャッシュ機能を提供
 *
 * @package Grant_Insight_Perfect
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 投稿のメタデータとタームを一括プリフェッチ
 * N+1クエリを解消するための関数
 */
function gi_prefetch_post_data($post_ids) {
    if (empty($post_ids) || !is_array($post_ids)) {
        return;
    }
    
    // メタデータを一括取得
    update_post_caches($post_ids, 'grant');
    
    // タームデータを一括取得
    update_object_term_cache($post_ids, 'grant');
    
    // グローバル変数にタームデータを格納（最適化されたカードテンプレート用）
    global $gi_prefetched_terms;
    $gi_prefetched_terms = array();
    
    foreach ($post_ids as $post_id) {
        $gi_prefetched_terms[$post_id] = array(
            'grant_prefecture' => wp_get_post_terms($post_id, 'grant_prefecture', array('fields' => 'names')),
            'grant_category' => wp_get_post_terms($post_id, 'grant_category', array('fields' => 'names'))
        );
    }
}

/**
 * 統計情報をキャッシュ付きで取得
 * 重いクエリをトランジェントでキャッシュ
 */
function gi_get_cached_stats() {
    $cache_key = 'gi_site_stats_v2';
    $cached_stats = get_transient($cache_key);
    
    if ($cached_stats !== false) {
        return $cached_stats;
    }
    
    // 統計情報を計算
    $stats = array();
    
    // 助成金総数
    $stats['total_grants'] = wp_count_posts('grant')->publish;
    
    // 募集中の助成金数（最適化されたクエリ）
    global $wpdb;
    $active_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT p.ID) 
        FROM {$wpdb->posts} p 
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
        WHERE p.post_type = %s 
        AND p.post_status = 'publish' 
        AND pm.meta_key = 'application_status' 
        AND pm.meta_value = 'open'
    ", 'grant'));
    $stats['active_grants'] = intval($active_count);
    
    // 都道府県数
    $stats['prefecture_count'] = wp_count_terms(array(
        'taxonomy' => 'grant_prefecture', 
        'hide_empty' => false
    ));
    
    // 平均採択率（最適化されたクエリ）
    $avg_rate = $wpdb->get_var($wpdb->prepare("
        SELECT AVG(CAST(pm.meta_value AS UNSIGNED)) 
        FROM {$wpdb->postmeta} pm 
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
        WHERE p.post_type = %s 
        AND p.post_status = 'publish' 
        AND pm.meta_key = 'grant_success_rate' 
        AND pm.meta_value > 0
    ", 'grant'));
    $stats['avg_success_rate'] = round(floatval($avg_rate));
    
    // 24時間キャッシュ
    set_transient($cache_key, $stats, DAY_IN_SECONDS);
    
    return $stats;
}

/**
 * ユーザーのお気に入り情報をキャッシュ付きで取得（最適化版）
 * 既存のgi_get_user_favorites()関数にキャッシュ機能を追加
 */
function gi_get_user_favorites_cached($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        // ログインしていない場合は既存の関数を使用
        return gi_get_user_favorites();
    }
    
    $cache_key = "gi_user_favorites_{$user_id}";
    $cached_favorites = wp_cache_get($cache_key, 'gi_favorites');
    
    if ($cached_favorites !== false) {
        return $cached_favorites;
    }
    
    // 既存の関数を使用してお気に入りを取得
    $favorites = gi_get_user_favorites($user_id);
    
    // 1時間キャッシュ
    wp_cache_set($cache_key, $favorites, 'gi_favorites', HOUR_IN_SECONDS);
    
    return $favorites;
}

/**
 * 検索クエリの最適化
 * インデックスを活用した効率的な検索
 */
function gi_optimize_grant_query($args) {
    // メタクエリの最適化
    if (isset($args['meta_query']) && is_array($args['meta_query'])) {
        foreach ($args['meta_query'] as &$meta_query) {
            if (isset($meta_query['key']) && isset($meta_query['value'])) {
                // 数値比較の場合はtype指定を追加
                if (in_array($meta_query['key'], array('max_amount_numeric', 'grant_success_rate'))) {
                    $meta_query['type'] = 'NUMERIC';
                }
            }
        }
    }
    
    // 不要なフィールドを除外してパフォーマンス向上
    if (!isset($args['fields'])) {
        $args['no_found_rows'] = true; // ページネーション不要な場合
    }
    
    return $args;
}

/**
 * 画像の遅延読み込み属性を追加
 */
function gi_add_lazy_loading($html, $post_id, $size, $attr) {
    // すでにloading属性がある場合はスキップ
    if (strpos($html, 'loading=') !== false) {
        return $html;
    }
    
    // loading="lazy"を追加
    $html = str_replace('<img ', '<img loading="lazy" ', $html);
    
    return $html;
}
add_filter('wp_get_attachment_image', 'gi_add_lazy_loading', 10, 4);

/**
 * WebP画像のサポート
 */
function gi_webp_support($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    $upload_dir = wp_upload_dir();
    $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_src);
    $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $image_path);
    
    if (file_exists($webp_path)) {
        $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $image_src);
        $sources[] = array(
            'url' => $webp_url,
            'descriptor' => 'type',
            'value' => 'image/webp',
        );
    }
    
    return $sources;
}
add_filter('wp_calculate_image_srcset', 'gi_webp_support', 10, 5);

/**
 * キャッシュクリア関数
 */
function gi_clear_performance_cache() {
    // 統計情報キャッシュをクリア
    delete_transient('gi_site_stats_v2');
    
    // お気に入りキャッシュをクリア
    wp_cache_flush_group('gi_favorites');
    
    // オブジェクトキャッシュをクリア
    wp_cache_flush();
}

/**
 * 投稿更新時にキャッシュをクリア
 */
function gi_clear_cache_on_post_update($post_id) {
    if (get_post_type($post_id) === 'grant') {
        gi_clear_performance_cache();
    }
}
add_action('save_post', 'gi_clear_cache_on_post_update');
add_action('delete_post', 'gi_clear_cache_on_post_update');

/**
 * データベースクエリの監視（デバッグ用）
 */
function gi_query_monitor() {
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('administrator')) {
        global $wpdb;
        echo "<!-- Database Queries: " . $wpdb->num_queries . " -->";
        
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            $slow_queries = array_filter($wpdb->queries, function($query) {
                return $query[1] > 0.01; // 0.01秒以上のクエリ
            });
            
            if (!empty($slow_queries)) {
                echo "<!-- Slow Queries: " . count($slow_queries) . " -->";
            }
        }
    }
}
add_action('wp_footer', 'gi_query_monitor');

/**
 * メモリ使用量の監視（デバッグ用）
 */
function gi_memory_monitor() {
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('administrator')) {
        $memory_usage = memory_get_peak_usage(true) / 1024 / 1024;
        echo "<!-- Peak Memory Usage: " . round($memory_usage, 2) . " MB -->";
    }
}
add_action('wp_footer', 'gi_memory_monitor');

