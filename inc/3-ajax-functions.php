<?php
/**
 * Grant Insight Perfect - 3. AJAX Functions File (Complete Unified Edition)
 *
 * サイトの動的な機能（検索、フィルタリング、お気に入りなど）を
 * 担当する全てのAJAX処理をここにまとめます。
 * template-parts/grant-card-unified.phpとの統一連携版
 *
 * @package Grant_Insight_Perfect
 * @version 3.0.0 - Complete Unified Edition
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * =============================================================================
 * 1. 統一カードレンダリングシステム
 * =============================================================================
 */

/**
 * 統一カードレンダリング関数（最優先：template-parts/grant-card-unified.php使用）
 */
function gi_render_card_unified($post_id, $view = 'grid', $additional_data = []) {
    if (!$post_id || !get_post($post_id)) {
        return gi_render_error_card('投稿が見つかりません');
    }

    // 1. まずテンプレートファイルをチェック（最優先）
    $template_path = get_template_directory() . '/template-parts/grant-card-unified.php';
    if (file_exists($template_path)) {
        return gi_render_via_template($post_id, $view, $template_path, $additional_data);
    }

    // 2. 統一テンプレート関数をチェック
    if (function_exists('render_grant_card_unified')) {
        $user_favorites = gi_get_user_favorites_safe();
        return render_grant_card_unified($post_id, $view, $user_favorites);
    }

    // 3. GrantCardRendererクラスをチェック
    if (class_exists('GrantCardRenderer')) {
        $renderer = GrantCardRenderer::getInstance();
        return $renderer->render($post_id, $view);
    }

    // 4. フォールバック用の内蔵カード
    return gi_render_builtin_card($post_id, $view);
}

/**
 * テンプレートファイル経由でのレンダリング
 */
function gi_render_via_template($post_id, $view, $template_path, $additional_data = []) {
    // テンプレートで使用する変数を準備
    $grant_data = gi_get_complete_grant_data($post_id);
    $user_favorites = gi_get_user_favorites_safe();
    $current_view = $view;
    
    // 追加データをマージ
    if (!empty($additional_data)) {
        $grant_data = array_merge($grant_data, $additional_data);
    }

    // グローバル$postを一時保存・設定
    global $post;
    $original_post = $post;
    $post = get_post($post_id);
    
    if ($post) {
        setup_postdata($post);
    }

    // テンプレートを読み込み
    ob_start();
    
    // エラーハンドリング付きでinclude
    try {
        include $template_path;
    } catch (Exception $e) {
        ob_end_clean();
        error_log('Grant Insight Template Error: ' . $e->getMessage());
        return gi_render_error_card('テンプレート読み込みエラー');
    } catch (Error $e) {
        ob_end_clean();
        error_log('Grant Insight Template Fatal Error: ' . $e->getMessage());
        return gi_render_error_card('テンプレート実行エラー');
    }
    
    $html = ob_get_clean();

    // $postを復元
    $post = $original_post;
    if ($post) {
        setup_postdata($post);
    } else {
        wp_reset_postdata();
    }

    return !empty($html) ? $html : gi_render_builtin_card($post_id, $view);
}

/**
 * 完全なデータ取得関数（ACF連携強化版）
 */
function gi_get_complete_grant_data($post_id) {
    static $cache = [];
    
    // キャッシュチェック
    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }
    
    $post = get_post($post_id);
    if (!$post) {
        return [];
    }
    
    // 基本データ
    $data = [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'permalink' => get_permalink($post_id),
        'excerpt' => get_the_excerpt($post_id),
        'content' => get_post_field('post_content', $post_id),
        'date' => get_the_date('Y-m-d', $post_id),
        'modified' => get_the_modified_date('Y-m-d H:i:s', $post_id),
        'status' => get_post_status($post_id),
        'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
    ];

    // ACFフィールドデータ
    $acf_fields = [
        // 基本情報
        'ai_summary' => '',
        'organization' => '',
        'organization_type' => '',
        
        // 金額情報
        'max_amount' => '',
        'max_amount_numeric' => 0,
        'min_amount' => 0,
        'subsidy_rate' => '',
        'amount_note' => '',
        
        // 締切・ステータス
        'deadline' => '',
        'deadline_date' => '',
        'deadline_timestamp' => '',
        'application_status' => 'active',
        'application_period' => '',
        'deadline_note' => '',
        
        // 対象・条件
        'grant_target' => '',
        'eligible_expenses' => '',
        'grant_difficulty' => 'normal',
        'grant_success_rate' => 0,
        'required_documents' => '',
        
        // 申請・連絡先
        'application_method' => 'online',
        'contact_info' => '',
        'official_url' => '',
        'external_link' => '',
        
        // 管理設定
        'is_featured' => false,
        'priority_order' => 100,
        'views_count' => 0,
        'last_updated' => '',
        'admin_notes' => '',
    ];

    foreach ($acf_fields as $field => $default) {
        $value = gi_get_field_safe($field, $post_id, $default);
        $data[$field] = $value;
    }

    // タクソノミーデータ
    $taxonomies = ['grant_category', 'grant_prefecture', 'grant_tag'];
    foreach ($taxonomies as $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        $data[$taxonomy] = [];
        $data[$taxonomy . '_names'] = [];
        
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $data[$taxonomy][] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'link' => get_term_link($term),
                ];
                $data[$taxonomy . '_names'][] = $term->name;
            }
        }
    }

    // 表示用の値を設定
    $data['main_category'] = !empty($data['grant_category_names']) ? $data['grant_category_names'][0] : '';
    $data['prefecture'] = !empty($data['grant_prefecture_names']) ? $data['grant_prefecture_names'][0] : '';
    
    // 金額の表示形式
    $data['amount_formatted'] = gi_format_amount_display($data['max_amount_numeric'], $data['max_amount']);
    
    // 締切日の処理
    if (!empty($data['deadline_date'])) {
        $data['deadline_formatted'] = gi_format_deadline_display($data['deadline_date']);
        $data['deadline_timestamp'] = strtotime($data['deadline_date']);
        $data['days_remaining'] = gi_calculate_days_remaining($data['deadline_date']);
    }
    
    // ステータス表示
    $data['status_display'] = gi_map_status_for_display($data['application_status']);
    
    // 難易度情報
    $data['difficulty_info'] = gi_get_difficulty_info($data['grant_difficulty']);
    
    // キャッシュに保存
    $cache[$post_id] = $data;
    
    return $data;
}

/**
 * 安全なACFフィールド取得
 */
function gi_get_field_safe($field_name, $post_id, $default = '') {
    // ACF関数が利用可能な場合
    if (function_exists('get_field')) {
        $value = get_field($field_name, $post_id);
        if ($value !== false && $value !== null) {
            return $value;
        }
    }
    
    // 通常のメタフィールドから取得
    $value = get_post_meta($post_id, $field_name, true);
    if ($value !== false && $value !== '') {
        return $value;
    }
    
    // ヘルパー関数が利用可能な場合
    if (function_exists('gi_safe_get_meta')) {
        return gi_safe_get_meta($post_id, $field_name, $default);
    }
    
    return $default;
}

/**
 * 安全なお気に入りリスト取得
 */
function gi_get_user_favorites_safe() {
    if (function_exists('gi_get_user_favorites')) {
        return gi_get_user_favorites();
    }
    
    $user_id = get_current_user_id();
    if ($user_id) {
        return get_user_meta($user_id, 'gi_favorites', true) ?: [];
    }
    
    // Cookieから取得
    $cookie_name = 'gi_favorites';
    if (isset($_COOKIE[$cookie_name])) {
        return array_filter(array_map('intval', explode(',', $_COOKIE[$cookie_name])));
    }
    
    return [];
}

/**
 * エラーカード表示
 */
function gi_render_error_card($message) {
    return "
    <div class='grant-card-error' style='
        padding: 20px; 
        background: #fee; 
        border: 1px solid #fcc; 
        border-radius: 4px;
        color: #c33;
    '>
        <p><strong>エラー:</strong> " . esc_html($message) . "</p>
    </div>";
}

/**
 * 内蔵フォールバックカード
 */
function gi_render_builtin_card($post_id, $view = 'grid') {
    $data = gi_get_complete_grant_data($post_id);
    $user_favorites = gi_get_user_favorites_safe();
    $is_favorite = in_array($post_id, $user_favorites);
    
    $title = esc_html($data['title']);
    $permalink = esc_url($data['permalink']);
    $excerpt = esc_html($data['excerpt']);
    $amount = esc_html($data['amount_formatted']);
    $organization = esc_html($data['organization']);
    $status = esc_html($data['status_display']);
    $prefecture = esc_html($data['prefecture']);
    
    if ($view === 'grid') {
        return "
        <div class='grant-card-builtin grant-card-grid' data-post-id='{$post_id}' style='
            background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        '>
            <div style='display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;'>
                <span style='background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>
                    {$status}
                </span>
                <button class='favorite-btn' data-post-id='{$post_id}' style='
                    background: none; border: none; color: " . ($is_favorite ? '#e53e3e' : '#ccc') . "; 
                    font-size: 18px; cursor: pointer;
                '>♥</button>
            </div>
            
            <h3 style='margin: 0 0 10px 0; font-size: 16px; line-height: 1.4;'>
                <a href='{$permalink}' style='text-decoration: none; color: #333;'>{$title}</a>
            </h3>
            
            <div style='font-size: 18px; font-weight: bold; color: #10b981; margin-bottom: 10px;'>
                最大 {$amount}
            </div>
            
            <div style='font-size: 12px; color: #666; margin-bottom: 15px;'>
                実施: {$organization} | 地域: {$prefecture}
            </div>
            
            <p style='font-size: 14px; color: #555; line-height: 1.5; margin-bottom: 15px;'>
                {$excerpt}
            </p>
            
            <a href='{$permalink}' style='
                display: block; 
                background: #10b981; 
                color: white; 
                text-align: center; 
                padding: 10px; 
                text-decoration: none; 
                border-radius: 4px;
                font-weight: bold;
            '>詳細を見る</a>
        </div>";
    } else {
        return "
        <div class='grant-card-builtin grant-card-list' data-post-id='{$post_id}' style='
            background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 15px; 
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        '>
            <div style='flex: 1;'>
                <h3 style='margin: 0 0 5px 0; font-size: 16px;'>
                    <a href='{$permalink}' style='text-decoration: none; color: #333;'>{$title}</a>
                </h3>
                <div style='font-size: 12px; color: #666;'>{$organization}</div>
            </div>
            
            <div style='text-align: center; min-width: 100px;'>
                <div style='font-size: 14px; font-weight: bold; color: #10b981;'>{$amount}</div>
                <div style='font-size: 10px; color: #999;'>{$status}</div>
            </div>
            
            <button class='favorite-btn' data-post-id='{$post_id}' style='
                background: none; border: none; color: " . ($is_favorite ? '#e53e3e' : '#ccc') . "; 
                font-size: 16px; cursor: pointer; padding: 5px;
            '>♥</button>
        </div>";
    }
}

/**
 * =============================================================================
 * 2. メイン検索・フィルタリング AJAX 処理
 * =============================================================================
 */

/**
 * 助成金読み込み処理（完全版・統一カード対応）
 */
function gi_ajax_load_grants() {
    // nonceチェック
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }

    // ===== パラメータ取得と検証 =====
    $search = sanitize_text_field($_POST['search'] ?? '');
    $categories = json_decode(stripslashes($_POST['categories'] ?? '[]'), true) ?: [];
    $prefectures = json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true) ?: [];
    $tags = json_decode(stripslashes($_POST['tags'] ?? '[]'), true) ?: [];
    $status = json_decode(stripslashes($_POST['status'] ?? '[]'), true) ?: [];
    $difficulty = json_decode(stripslashes($_POST['difficulty'] ?? '[]'), true) ?: [];
    $success_rate = json_decode(stripslashes($_POST['success_rate'] ?? '[]'), true) ?: [];
    
    // 金額・数値フィルター
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $amount_min = intval($_POST['amount_min'] ?? 0);
    $amount_max = intval($_POST['amount_max'] ?? 0);
    
    // 新しいフィルター項目
    $subsidy_rate = sanitize_text_field($_POST['subsidy_rate'] ?? '');
    $organization = sanitize_text_field($_POST['organization'] ?? '');
    $organization_type = sanitize_text_field($_POST['organization_type'] ?? '');
    $target_business = sanitize_text_field($_POST['target_business'] ?? '');
    $application_method = sanitize_text_field($_POST['application_method'] ?? '');
    $only_featured = sanitize_text_field($_POST['only_featured'] ?? '');
    $deadline_range = sanitize_text_field($_POST['deadline_range'] ?? '');
    
    // 表示・ソート設定
    $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
    $view = sanitize_text_field($_POST['view'] ?? 'grid');
    $page = max(1, intval($_POST['page'] ?? 1));
    $posts_per_page = max(6, min(30, intval($_POST['posts_per_page'] ?? 12)));

    // ===== WP_Queryの引数構築 =====
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'post_status' => 'publish'
    ];

    // ===== 検索クエリ（拡張版：ACFフィールドも検索対象） =====
    if (!empty($search)) {
        $args['s'] = $search;
        
        // メタフィールドも検索対象に追加
        add_filter('posts_search', function($search_sql, $wp_query) use ($search) {
            global $wpdb;
            
            if (!$wp_query->is_main_query() || empty($search)) {
                return $search_sql;
            }
            
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            
            $meta_search = $wpdb->prepare("
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} pm 
                    WHERE pm.post_id = {$wpdb->posts}.ID 
                    AND pm.meta_key IN ('ai_summary', 'organization', 'grant_target', 'eligible_expenses', 'required_documents')
                    AND pm.meta_value LIKE %s
                )
            ", $search_term);
            
            // 既存の検索SQLに追加
            $search_sql = str_replace('))) AND', '))) ' . $meta_search . ' AND', $search_sql);
            return $search_sql;
        }, 10, 2);
    }

    // ===== タクソノミークエリ =====
    $tax_query = ['relation' => 'AND'];
    
    if (!empty($categories)) {
        $tax_query[] = [
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        ];
    }
    
    if (!empty($prefectures)) {
        $tax_query[] = [
            'taxonomy' => 'grant_prefecture',
            'field' => 'slug', 
            'terms' => $prefectures,
            'operator' => 'IN'
        ];
    }
    
    if (!empty($tags)) {
        $tax_query[] = [
            'taxonomy' => 'grant_tag',
            'field' => 'slug',
            'terms' => $tags,
            'operator' => 'IN'
        ];
    }
    
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    // ===== メタクエリ（カスタムフィールド） =====
    $meta_query = ['relation' => 'AND'];
    
    // ステータスフィルター
    if (!empty($status)) {
        // UIステータスをDBの値にマッピング
        $db_status = array_map(function($s) {
            return $s === 'active' ? 'open' : ($s === 'upcoming' ? 'upcoming' : $s);
        }, $status);
        
        $meta_query[] = [
            'key' => 'application_status',
            'value' => $db_status,
            'compare' => 'IN'
        ];
    }
    
    // 難易度フィルター
    if (!empty($difficulty)) {
        $meta_query[] = [
            'key' => 'grant_difficulty',
            'value' => $difficulty,
            'compare' => 'IN'
        ];
    }
    
    // 採択率フィルター
    if (!empty($success_rate)) {
        $rate_query = ['relation' => 'OR'];
        
        if (in_array('high', $success_rate, true)) {
            $rate_query[] = [
                'key' => 'grant_success_rate',
                'value' => 70,
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }
        
        if (in_array('medium', $success_rate, true)) {
            $rate_query[] = [
                'key' => 'grant_success_rate',
                'value' => [50, 69],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC'
            ];
        }
        
        if (in_array('low', $success_rate, true)) {
            $rate_query[] = [
                'key' => 'grant_success_rate',
                'value' => 50,
                'compare' => '<',
                'type' => 'NUMERIC'
            ];
        }
        
        if (count($rate_query) > 1) {
            $meta_query[] = $rate_query;
        }
    }
    
    // 金額フィルター（範囲指定）
    if (!empty($amount) || $amount_min > 0 || $amount_max > 0) {
        if (!empty($amount)) {
            // プリセット金額範囲
            switch ($amount) {
                case '0-100':
                    $meta_query[] = [
                        'key' => 'max_amount_numeric',
                        'value' => 1000000,
                        'compare' => '<=',
                        'type' => 'NUMERIC'
                    ];
                    break;
                case '100-500':
                    $meta_query[] = [
                        'key' => 'max_amount_numeric',
                        'value' => [1000000, 5000000],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ];
                    break;
                case '500-1000':
                    $meta_query[] = [
                        'key' => 'max_amount_numeric',
                        'value' => [5000000, 10000000],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ];
                    break;
                case '1000-3000':
                    $meta_query[] = [
                        'key' => 'max_amount_numeric',
                        'value' => [10000000, 30000000],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ];
                    break;
                case '3000+':
                    $meta_query[] = [
                        'key' => 'max_amount_numeric',
                        'value' => 30000000,
                        'compare' => '>=',
                        'type' => 'NUMERIC'
                    ];
                    break;
            }
        } else {
            // カスタム金額範囲
            if ($amount_min > 0 && $amount_max > 0) {
                $meta_query[] = [
                    'key' => 'max_amount_numeric',
                    'value' => [$amount_min, $amount_max],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
            } elseif ($amount_min > 0) {
                $meta_query[] = [
                    'key' => 'max_amount_numeric',
                    'value' => $amount_min,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ];
            } elseif ($amount_max > 0) {
                $meta_query[] = [
                    'key' => 'max_amount_numeric',
                    'value' => $amount_max,
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                ];
            }
        }
    }
    
    // 補助率フィルター
    if (!empty($subsidy_rate)) {
        $meta_query[] = [
            'key' => 'subsidy_rate',
            'value' => $subsidy_rate,
            'compare' => 'LIKE'
        ];
    }
    
    // 実施組織フィルター
    if (!empty($organization)) {
        $meta_query[] = [
            'key' => 'organization',
            'value' => $organization,
            'compare' => 'LIKE'
        ];
    }
    
    // 組織タイプフィルター
    if (!empty($organization_type)) {
        $meta_query[] = [
            'key' => 'organization_type',
            'value' => $organization_type,
            'compare' => '='
        ];
    }
    
    // 対象事業者フィルター
    if (!empty($target_business)) {
        $meta_query[] = [
            'key' => 'grant_target',
            'value' => $target_business,
            'compare' => 'LIKE'
        ];
    }
    
    // 申請方法フィルター
    if (!empty($application_method)) {
        $meta_query[] = [
            'key' => 'application_method',
            'value' => $application_method,
            'compare' => '='
        ];
    }
    
    // 注目の助成金フィルター
    if ($only_featured === 'true') {
        $meta_query[] = [
            'key' => 'is_featured',
            'value' => '1',
            'compare' => '='
        ];
    }
    
    // 締切日範囲フィルター
    if (!empty($deadline_range)) {
        $current_time = current_time('timestamp');
        
        switch ($deadline_range) {
            case '1week':
                $end_time = strtotime('+1 week', $current_time);
                break;
            case '1month':
                $end_time = strtotime('+1 month', $current_time);
                break;
            case '3months':
                $end_time = strtotime('+3 months', $current_time);
                break;
            case '6months':
                $end_time = strtotime('+6 months', $current_time);
                break;
            default:
                $end_time = null;
        }
        
        if ($end_time) {
            $meta_query[] = [
                'key' => 'deadline_timestamp',
                'value' => [$current_time, $end_time],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC'
            ];
        }
    }
    
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    // ===== ソート順 =====
    switch ($sort) {
        case 'date_asc':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'date_desc':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'amount_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'DESC';
            break;
        case 'amount_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'ASC';
            break;
        case 'deadline_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'deadline_timestamp';
            $args['order'] = 'ASC';
            break;
        case 'deadline_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'deadline_timestamp';
            $args['order'] = 'DESC';
            break;
        case 'success_rate_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'grant_success_rate';
            $args['order'] = 'DESC';
            break;
        case 'success_rate_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'grant_success_rate';
            $args['order'] = 'ASC';
            break;
        case 'priority':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'priority_order';
            $args['order'] = 'ASC';
            break;
        case 'title_asc':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        case 'title_desc':
            $args['orderby'] = 'title';
            $args['order'] = 'DESC';
            break;
        case 'featured':
            $args['orderby'] = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
            $args['meta_key'] = 'is_featured';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    // ===== クエリ実行 =====
    $query = new WP_Query($args);
    $grants = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // 統一カードレンダリングを使用
            $html = gi_render_card_unified($post_id, $view);

            $grants[] = [
                'id' => $post_id,
                'html' => $html,
                'title' => get_the_title($post_id),
                'permalink' => get_permalink($post_id)
            ];
        }
        wp_reset_postdata();
    }

    // ===== ページネーション生成 =====
    $pagination_html = '';
    if ($query->max_num_pages > 1) {
        ob_start();
        gi_render_ajax_pagination($page, $query->max_num_pages);
        $pagination_html = ob_get_clean();
    }

    // ===== 統計情報 =====
    $stats = [
        'total_found' => $query->found_posts,
        'current_page' => $page,
        'total_pages' => $query->max_num_pages,
        'posts_per_page' => $posts_per_page,
        'showing_from' => (($page - 1) * $posts_per_page) + 1,
        'showing_to' => min($page * $posts_per_page, $query->found_posts),
    ];

    // ===== レスポンス送信 =====
    wp_send_json_success([
        'grants' => $grants,
        'pagination' => [
            'html' => $pagination_html,
            'current_page' => $page,
            'total_pages' => $query->max_num_pages,
            'total_posts' => $query->found_posts,
            'posts_per_page' => $posts_per_page,
        ],
        'stats' => $stats,
        'view' => $view,
        'query_info' => [
            'search' => $search,
            'filters_applied' => !empty($categories) || !empty($prefectures) || !empty($tags) || !empty($status) || !empty($difficulty) || !empty($success_rate) || !empty($amount) || $amount_min > 0 || $amount_max > 0,
            'sort' => $sort,
        ],
        'debug' => defined('WP_DEBUG') && WP_DEBUG ? $args : null,
    ]);
}
add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');

/**
 * AJAX用ページネーション生成
 */
function gi_render_ajax_pagination($current_page, $total_pages) {
    if ($total_pages <= 1) return;
    
    echo '<div class="gi-pagination flex items-center justify-center space-x-2 mt-8">';
    
    // 前のページ
    if ($current_page > 1) {
        echo '<button class="pagination-btn px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-gray-700" data-page="' . ($current_page - 1) . '">';
        echo '<i class="fas fa-chevron-left mr-1"></i>前へ';
        echo '</button>';
    }
    
    // ページ番号
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    // 最初のページ
    if ($start > 1) {
        echo '<button class="pagination-btn px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-colors text-gray-700" data-page="1">1</button>';
        if ($start > 2) {
            echo '<span class="px-2 text-gray-500">...</span>';
        }
    }
    
    // 中間のページ
    for ($i = $start; $i <= $end; $i++) {
        $active_class = ($i === $current_page) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50';
        echo '<button class="pagination-btn px-4 py-2 border border-gray-300 rounded-lg transition-colors ' . $active_class . '" data-page="' . $i . '">';
        echo $i;
        echo '</button>';
    }
    
    // 最後のページ
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            echo '<span class="px-2 text-gray-500">...</span>';
        }
        echo '<button class="pagination-btn px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-colors text-gray-700" data-page="' . $total_pages . '">' . $total_pages . '</button>';
    }
    
    // 次のページ
    if ($current_page < $total_pages) {
        echo '<button class="pagination-btn px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-gray-700" data-page="' . ($current_page + 1) . '">';
        echo '次へ<i class="fas fa-chevron-right ml-1"></i>';
        echo '</button>';
    }
    
    echo '</div>';
}

/**
 * =============================================================================
 * 3. お気に入り機能
 * =============================================================================
 */

/**
 * お気に入りの追加・削除処理
 */
function gi_ajax_toggle_favorite() {
    $nonce_check1 = wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce');
    $nonce_check2 = wp_verify_nonce($_POST['nonce'] ?? '', 'grant_insight_search_nonce');
    
    if (!$nonce_check1 && !$nonce_check2) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id || !get_post($post_id)) {
        wp_send_json_error('無効な投稿IDです');
    }
    
    $user_id = get_current_user_id();
    
    if ($user_id) {
        // ログインユーザーの場合
        $favorites = get_user_meta($user_id, 'gi_favorites', true) ?: [];
        
        if (in_array($post_id, $favorites)) {
            $favorites = array_diff($favorites, [$post_id]);
            $action = 'removed';
            $is_favorite = false;
        } else {
            $favorites[] = $post_id;
            $action = 'added';
            $is_favorite = true;
        }
        
        update_user_meta($user_id, 'gi_favorites', array_values($favorites));
    } else {
        // 非ログインユーザーの場合（Cookie使用）
        $cookie_name = 'gi_favorites';
        $favorites = isset($_COOKIE[$cookie_name]) ? 
            array_filter(array_map('intval', explode(',', $_COOKIE[$cookie_name]))) : [];
        
        if (in_array($post_id, $favorites)) {
            $favorites = array_diff($favorites, [$post_id]);
            $action = 'removed';
            $is_favorite = false;
        } else {
            $favorites[] = $post_id;
            $action = 'added';
            $is_favorite = true;
        }
        
        // Cookie設定（30日間）
        setcookie($cookie_name, implode(',', $favorites), time() + (86400 * 30), '/');
    }
    
    wp_send_json_success([
        'action' => $action,
        'post_id' => $post_id,
        'post_title' => get_the_title($post_id),
        'is_favorite' => $is_favorite,
        'count' => count($favorites),
        'message' => $action === 'added' ? 'お気に入りに追加しました' : 'お気に入りから削除しました',
        'favorites_list' => $favorites,
    ]);
}
add_action('wp_ajax_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_toggle_favorite', 'gi_ajax_toggle_favorite');

/**
 * お気に入り一覧取得
 */
function gi_ajax_get_favorites() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $user_id = get_current_user_id();
    $page = max(1, intval($_POST['page'] ?? 1));
    $per_page = 12;
    
    if ($user_id) {
        $favorite_ids = get_user_meta($user_id, 'gi_favorites', true) ?: [];
    } else {
        $cookie_name = 'gi_favorites';
        $favorite_ids = isset($_COOKIE[$cookie_name]) ? 
            array_filter(array_map('intval', explode(',', $_COOKIE[$cookie_name]))) : [];
    }
    
    $favorites = [];
    $total_count = count($favorite_ids);
    
    if (!empty($favorite_ids)) {
        // ページネーション処理
        $offset = ($page - 1) * $per_page;
        $paged_ids = array_slice($favorite_ids, $offset, $per_page);
        
        $args = [
            'post_type' => 'grant',
            'post__in' => $paged_ids,
            'orderby' => 'post__in',
            'posts_per_page' => $per_page,
            'post_status' => 'publish'
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $favorites[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                    'excerpt' => get_the_excerpt(),
                    'html' => gi_render_card_unified($post_id, 'grid'),
                    'date_added' => get_the_date('Y-m-d')
                ];
            }
            wp_reset_postdata();
        }
    }
    
    $total_pages = ceil($total_count / $per_page);
    
    wp_send_json_success([
        'favorites' => $favorites,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_count' => $total_count,
            'per_page' => $per_page,
        ],
        'user_type' => $user_id ? 'logged_in' : 'guest'
    ]);
}
add_action('wp_ajax_gi_get_favorites', 'gi_ajax_get_favorites');
add_action('wp_ajax_nopriv_gi_get_favorites', 'gi_ajax_get_favorites');

/**
 * =============================================================================
 * 4. 検索候補・オートコンプリート
 * =============================================================================
 */

/**
 * 検索候補取得
 */
function gi_ajax_get_search_suggestions() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $query = sanitize_text_field($_POST['query'] ?? '');
    $limit = min(10, intval($_POST['limit'] ?? 5));
    
    if (strlen($query) < 2) {
        wp_send_json_success([]);
    }
    
    $suggestions = [];
    
    // 1. 投稿タイトルから検索
    $posts = get_posts([
        's' => $query,
        'post_type' => 'grant',
        'posts_per_page' => $limit,
        'post_status' => 'publish'
    ]);
    
    foreach ($posts as $post) {
        $suggestions[] = [
            'type' => 'grant',
            'label' => $post->post_title,
            'value' => $post->post_title,
            'url' => get_permalink($post->ID),
            'id' => $post->ID
        ];
    }
    
    // 2. 組織名から検索
    if (count($suggestions) < $limit) {
        global $wpdb;
        $organizations = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT meta_value as org_name 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'organization' 
            AND meta_value LIKE %s 
            ORDER BY meta_value 
            LIMIT %d
        ", '%' . $wpdb->esc_like($query) . '%', $limit - count($suggestions)));
        
        foreach ($organizations as $org) {
            $suggestions[] = [
                'type' => 'organization',
                'label' => $org->org_name . ' (実施組織)',
                'value' => $org->org_name,
                'filter' => 'organization'
            ];
        }
    }
    
    // 3. タクソノミーから検索
    if (count($suggestions) < $limit) {
        $taxonomies = ['grant_category', 'grant_prefecture', 'grant_tag'];
        
        foreach ($taxonomies as $taxonomy) {
            if (count($suggestions) >= $limit) break;
            
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'name__like' => $query,
                'number' => $limit - count($suggestions),
                'hide_empty' => true
            ]);
            
            foreach ($terms as $term) {
                $type_label = $taxonomy === 'grant_category' ? 'カテゴリー' : 
                             ($taxonomy === 'grant_prefecture' ? '都道府県' : 'タグ');
                
                $suggestions[] = [
                    'type' => $taxonomy,
                    'label' => $term->name . " ({$type_label})",
                    'value' => $term->name,
                    'slug' => $term->slug,
                    'filter' => $taxonomy
                ];
            }
        }
    }
    
    wp_send_json_success($suggestions);
}
add_action('wp_ajax_gi_get_search_suggestions', 'gi_ajax_get_search_suggestions');
add_action('wp_ajax_nopriv_gi_get_search_suggestions', 'gi_ajax_get_search_suggestions');

/**
 * =============================================================================
 * 5. 関連助成金取得
 * =============================================================================
 */

/**
 * 関連助成金取得
 */
function gi_ajax_get_related_grants() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $post_id = intval($_POST['post_id'] ?? 0);
    $category = sanitize_text_field($_POST['category'] ?? '');
    $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
    $limit = min(6, intval($_POST['limit'] ?? 3));
    
    if (!$post_id) {
        wp_send_json_error('無効な投稿IDです');
    }
    
    // 関連助成金の検索条件を構築
    $args = [
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'post__not_in' => [$post_id],
        'meta_query' => [
            [
                'key' => 'application_status',
                'value' => 'open',
                'compare' => '='
            ]
        ]
    ];
    
    $tax_query = ['relation' => 'OR'];
    
    // カテゴリーで関連付け
    if (!empty($category)) {
        $category_term = get_term_by('name', $category, 'grant_category');
        if ($category_term) {
            $tax_query[] = [
                'taxonomy' => 'grant_category',
                'field' => 'term_id',
                'terms' => [$category_term->term_id]
            ];
        }
    }
    
    // 都道府県で関連付け
    if (!empty($prefecture)) {
        $prefecture_term = get_term_by('name', $prefecture, 'grant_prefecture');
        if ($prefecture_term) {
            $tax_query[] = [
                'taxonomy' => 'grant_prefecture',
                'field' => 'term_id',
                'terms' => [$prefecture_term->term_id]
            ];
        }
    }
    
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }
    
    $query = new WP_Query($args);
    $related_grants = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $related_post_id = get_the_ID();
            
            $related_grants[] = [
                'id' => $related_post_id,
                'html' => gi_render_card_unified($related_post_id, 'grid'),
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'data' => gi_get_complete_grant_data($related_post_id)
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success([
        'related_grants' => $related_grants,
        'count' => count($related_grants)
    ]);
}
add_action('wp_ajax_gi_get_related_grants', 'gi_ajax_get_related_grants');
add_action('wp_ajax_nopriv_gi_get_related_grants', 'gi_ajax_get_related_grants');

/**
 * =============================================================================
 * 6. 統計・分析機能
 * =============================================================================
 */

/**
 * 検索統計取得
 */
function gi_ajax_get_search_stats() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    // キャッシュチェック
    $stats = get_transient('gi_search_stats');
    if (false === $stats) {
        
        // 全体統計
        $total_grants = wp_count_posts('grant')->publish;
        
        // ステータス別統計
        $active_grants = get_posts([
            'post_type' => 'grant',
            'meta_query' => [
                [
                    'key' => 'application_status',
                    'value' => 'open',
                    'compare' => '='
                ]
            ],
            'fields' => 'ids',
            'posts_per_page' => -1
        ]);
        
        // 平均採択率
        global $wpdb;
        $avg_success_rate = $wpdb->get_var("
            SELECT AVG(CAST(meta_value AS UNSIGNED)) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'grant_success_rate' 
            AND meta_value > 0 AND meta_value <= 100
        ");
        
        // 平均助成額
        $avg_amount = $wpdb->get_var("
            SELECT AVG(CAST(meta_value AS UNSIGNED)) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'max_amount_numeric' 
            AND meta_value > 0
        ");
        
        // カテゴリー別統計
        $categories = get_terms([
            'taxonomy' => 'grant_category',
            'hide_empty' => true,
            'number' => 10
        ]);
        
        $category_stats = [];
        foreach ($categories as $category) {
            $category_stats[] = [
                'name' => $category->name,
                'count' => $category->count,
                'slug' => $category->slug
            ];
        }
        
        // 都道府県別統計
        $prefectures = get_terms([
            'taxonomy' => 'grant_prefecture',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 10
        ]);
        
        $prefecture_stats = [];
        foreach ($prefectures as $prefecture) {
            $prefecture_stats[] = [
                'name' => $prefecture->name,
                'count' => $prefecture->count,
                'slug' => $prefecture->slug
            ];
        }
        
        $stats = [
            'total_grants' => intval($total_grants),
            'active_grants' => count($active_grants),
            'avg_success_rate' => round(floatval($avg_success_rate), 1),
            'avg_amount' => intval($avg_amount),
            'avg_amount_formatted' => gi_format_amount_display(intval($avg_amount)),
            'categories' => $category_stats,
            'prefectures' => $prefecture_stats,
            'last_updated' => current_time('Y-m-d H:i:s')
        ];
        
        // 1時間キャッシュ
        set_transient('gi_search_stats', $stats, 3600);
    }
    
    wp_send_json_success($stats);
}
add_action('wp_ajax_gi_get_search_stats', 'gi_ajax_get_search_stats');
add_action('wp_ajax_nopriv_gi_get_search_stats', 'gi_ajax_get_search_stats');

/**
 * =============================================================================
 * 7. エクスポート・共有機能
 * =============================================================================
 */

/**
 * 検索結果のCSVエクスポート
 */
function gi_ajax_export_search_results() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    // 検索パラメータを取得（gi_ajax_load_grantsと同様）
    $search = sanitize_text_field($_POST['search'] ?? '');
    $categories = json_decode(stripslashes($_POST['categories'] ?? '[]'), true) ?: [];
    $prefectures = json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true) ?: [];
    
    // 検索条件でクエリを実行
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => 200, // エクスポート上限
        'post_status' => 'publish'
    ];
    
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    // タクソノミークエリ
    $tax_query = ['relation' => 'AND'];
    if (!empty($categories)) {
        $tax_query[] = [
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        ];
    }
    if (!empty($prefectures)) {
        $tax_query[] = [
            'taxonomy' => 'grant_prefecture',
            'field' => 'slug',
            'terms' => $prefectures,
            'operator' => 'IN'
        ];
    }
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }
    
    $query = new WP_Query($args);
    
    // CSVファイルを生成
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="grant_search_results_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM付きでUTF-8エンコード
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // ヘッダー行
    fputcsv($output, [
        'ID',
        'タイトル',
        'URL',
        '実施組織',
        '最大助成額',
        '締切日',
        'ステータス',
        '都道府県',
        'カテゴリー',
        '採択率',
        '難易度',
        '更新日'
    ]);
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $data = gi_get_complete_grant_data($post_id);
            
            fputcsv($output, [
                $post_id,
                get_the_title(),
                get_permalink(),
                $data['organization'],
                $data['amount_formatted'],
                $data['deadline_formatted'],
                $data['status_display'],
                $data['prefecture'],
                $data['main_category'],
                $data['grant_success_rate'] . '%',
                $data['difficulty_info']['label'] ?? $data['grant_difficulty'],
                get_the_modified_date('Y-m-d')
            ]);
        }
        wp_reset_postdata();
    }
    
    fclose($output);
    exit;
}
add_action('wp_ajax_gi_export_search_results', 'gi_ajax_export_search_results');
add_action('wp_ajax_nopriv_gi_export_search_results', 'gi_ajax_export_search_results');

/**
 * =============================================================================
 * 8. ユーティリティ関数群
 * =============================================================================
 */

/**
 * 金額の表示用フォーマット
 */
function gi_format_amount_display($amount_numeric, $amount_text = '') {
    if (function_exists('gi_format_amount_unified')) {
        return gi_format_amount_unified($amount_numeric, $amount_text);
    }
    
    $amount = intval($amount_numeric);
    
    if ($amount >= 100000000) {
        $oku = $amount / 100000000;
        return $oku == floor($oku) ? number_format($oku) . '億円' : number_format($oku, 1) . '億円';
    } elseif ($amount >= 10000) {
        $man = $amount / 10000;
        return $man == floor($man) ? number_format($man) . '万円' : number_format($man, 1) . '万円';
    } elseif ($amount > 0) {
        return number_format($amount) . '円';
    }
    
    return !empty($amount_text) ? $amount_text : '未定';
}

/**
 * 締切日の表示用フォーマット
 */
function gi_format_deadline_display($deadline_date) {
    if (empty($deadline_date)) return '未定';
    
    if (function_exists('gi_format_deadline_for_display')) {
        return gi_format_deadline_for_display($deadline_date);
    }
    
    $timestamp = is_numeric($deadline_date) ? intval($deadline_date) : strtotime($deadline_date);
    if ($timestamp) {
        return date('Y年n月j日', $timestamp);
    }
    
    return $deadline_date;
}

/**
 * 残り日数計算
 */
function gi_calculate_days_remaining($deadline_date) {
    if (empty($deadline_date)) return null;
    
    $deadline_timestamp = is_numeric($deadline_date) ? intval($deadline_date) : strtotime($deadline_date);
    if (!$deadline_timestamp) return null;
    
    $current_timestamp = current_time('timestamp');
    $diff = $deadline_timestamp - $current_timestamp;
    
    return $diff > 0 ? ceil($diff / (60 * 60 * 24)) : 0;
}

/**
 * ステータス表示マッピング
 */
function gi_map_status_for_display($status) {
    if (function_exists('gi_map_application_status_ui')) {
        return gi_map_application_status_ui($status);
    }
    
    $status_map = [
        'open' => '募集中',
        'active' => '募集中',
        'upcoming' => '募集予定',
        'closed' => '募集終了',
        'suspended' => '一時停止'
    ];
    
    return $status_map[$status] ?? $status;
}

/**
 * 難易度情報取得
 */
function gi_get_difficulty_info($difficulty) {
    $difficulty_config = [
        'easy' => ['label' => '易しい', 'color' => 'green', 'stars' => 1],
        'normal' => ['label' => '普通', 'color' => 'blue', 'stars' => 2],
        'hard' => ['label' => '難しい', 'color' => 'orange', 'stars' => 3],
        'expert' => ['label' => '専門的', 'color' => 'red', 'stars' => 4]
    ];
    
    return $difficulty_config[$difficulty] ?? $difficulty_config['normal'];
}

/**
 * =============================================================================
 * 9. デバッグ・ログ機能
 * =============================================================================
 */

/**
 * AJAX処理のデバッグ情報出力
 */
function gi_ajax_debug_info() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        wp_send_json_error('Debug mode disabled');
    }
    
    $debug_info = [
        'template_path' => get_template_directory() . '/template-parts/grant-card-unified.php',
        'template_exists' => file_exists(get_template_directory() . '/template-parts/grant-card-unified.php'),
        'functions_available' => [
            'render_grant_card_unified' => function_exists('render_grant_card_unified'),
            'gi_get_complete_grant_data' => function_exists('gi_get_complete_grant_data'),
            'gi_get_user_favorites' => function_exists('gi_get_user_favorites'),
            'gi_format_amount_unified' => function_exists('gi_format_amount_unified'),
        ],
        'classes_available' => [
            'GrantCardRenderer' => class_exists('GrantCardRenderer'),
        ],
        'acf_available' => function_exists('get_field'),
        'grants_count' => wp_count_posts('grant'),
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
        'theme_version' => wp_get_theme()->get('Version'),
    ];
    
    wp_send_json_success($debug_info);
}
add_action('wp_ajax_gi_debug_info', 'gi_ajax_debug_info');

/**
 * AJAX エラーログ記録
 */
function gi_ajax_log_error($message, $data = null) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) return;
    
    $log_message = '[Grant Insight AJAX] ' . $message;
    if ($data !== null) {
        $log_message .= ' | Data: ' . print_r($data, true);
    }
    
    error_log($log_message);
}

// デバッグモード時のグローバルエラーハンドリング
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_ajax_gi_load_grants', function() {
        gi_ajax_log_error('AJAX gi_load_grants called', $_POST);
    }, 1);
}