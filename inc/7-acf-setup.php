<?php
/**
 * Grant Insight Perfect - 7. ACF Setup File
 *
 * Advanced Custom Fields (ACF) プラグインに関する設定や連携処理を
 * ここにまとめます。
 *
 * @package Grant_Insight_Perfect
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// ACFプラグインが存在しない場合は、以降の処理を中断
if (!function_exists('acf_add_local_field_group')) {
    return;
}


/*
 * --------------------------------------------------------------------------
 * 1. ACF Local JSON の設定
 * --------------------------------------------------------------------------
 * フィールドグループの設定をテーマ内の /acf-json フォルダに保存・読込します。
 */
add_filter('acf/settings/save_json', function($path) {
    $theme_path = get_stylesheet_directory() . '/acf-json';
    if (!file_exists($theme_path)) {
        wp_mkdir_p($theme_path);
    }
    return $theme_path;
});

add_filter('acf/settings/load_json', function($paths) {
    $theme_path = get_stylesheet_directory() . '/acf-json';
    if (!in_array($theme_path, $paths, true)) {
        $paths[] = $theme_path;
    }
    return $paths;
});


/*
 * --------------------------------------------------------------------------
 * 2. PHPによるフィールドグループの登録
 * --------------------------------------------------------------------------
 * /acf-fields.json ファイルからフィールド定義を読み込み、プログラム的に登録します。
 */
add_action('acf/init', function() {
    $json_file = get_stylesheet_directory() . '/acf-fields.json';
    if (!file_exists($json_file)) {
        return;
    }

    $raw_data = file_get_contents($json_file);
    if (!$raw_data) {
        return;
    }

    $json_data = json_decode($raw_data, true);
    if (!is_array($json_data)) {
        return;
    }

    $groups = $json_data['groups'] ?? [];
    foreach ($groups as $group) {
        if (!empty($group['key']) && !empty($group['title']) && !empty($group['fields']) && !empty($group['location'])) {
            acf_add_local_field_group($group);
        }
    }
});


/*
 * --------------------------------------------------------------------------
 * 3. ACFフィールドとタクソノミーの同期
 * --------------------------------------------------------------------------
 * 投稿保存時にACFの都道府県フィールドの値をgrant_prefectureタクソноミーに同期します。
 */
function gi_sync_grant_prefectures_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'grant') return;

    $meta_values = array();
    $candidates = array('prefecture', 'prefectures', 'grant_prefecture');
    foreach ($candidates as $key) {
        $val = gi_safe_get_meta($post_id, $key, '');
        if (!empty($val)) {
            $meta_values = is_array($val) ? $val : preg_split('/[,|]/u', $val);
            break;
        }
    }
    if (empty($meta_values)) return;

    $term_ids = array();
    foreach ($meta_values as $raw) {
        $name = trim(wp_strip_all_tags($raw));
        if ($name === '') continue;
        
        $term = get_term_by('name', $name, 'grant_prefecture');
        if (!$term) {
            $term = get_term_by('slug', sanitize_title($name), 'grant_prefecture');
        }
        
        if ($term && !is_wp_error($term)) {
            $term_ids[] = intval($term->term_id);
        }
    }
    
    if (!empty($term_ids)) {
        wp_set_post_terms($post_id, $term_ids, 'grant_prefecture', false);
    }
}
add_action('save_post', 'gi_sync_grant_prefectures_on_save', 20, 3);
