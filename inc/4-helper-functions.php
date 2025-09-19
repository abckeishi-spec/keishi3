<?php
/**
 * Grant Insight Perfect - 4. Helper Functions File（改善版）
 *
 * サイト全体で再利用可能な、汎用的なヘルパー関数やユーティリティ関数を
 * ここにまとめます。ACFとの連携を強化し、フィールド名の統一に対応。
 *
 * @package Grant_Insight_Perfect
 * @version 2.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 統一金額フォーマット関数（強化版）
 * 数値とテキストの両方に対応し、正確な表示を提供
 */
function gi_format_amount_unified($amount_numeric, $amount_text = '') {
    $numeric = intval($amount_numeric);
    
    // 数値が0以下で、テキストが存在する場合はパース
    if ($numeric <= 0 && !empty($amount_text)) {
        $numeric = gi_parse_amount_from_text($amount_text);
    }
    
    // フォーマット処理
    if ($numeric >= 100000000) {
        $oku = $numeric / 100000000;
        return $oku == floor($oku) ? number_format($oku) . '億円' : number_format($oku, 1) . '億円';
    } elseif ($numeric >= 10000) {
        $man = $numeric / 10000;
        return $man == floor($man) ? number_format($man) . '万円' : number_format($man, 1) . '万円';
    } elseif ($numeric > 0) {
        return number_format($numeric) . '円';
    }
    
    return !empty($amount_text) ? $amount_text : '金額未設定';
}

/**
 * テキストから金額数値を抽出（強化版）
 */
function gi_parse_amount_from_text($text) {
    if (empty($text)) return 0;
    
    // 全角数字を半角に変換
    $text = mb_convert_kana($text, 'n');
    $text = str_replace([',', '，', ' ', '　'], '', $text);
    
    // パターンマッチング（優先順位順）
    $patterns = [
        '/([0-9]+(?:\.[0-9]+)?)\s*億/u' => 100000000,
        '/([0-9]+(?:\.[0-9]+)?)\s*万/u' => 10000,
        '/([0-9]+)円?/u' => 1,
    ];
    
    foreach ($patterns as $pattern => $multiplier) {
        if (preg_match($pattern, $text, $matches)) {
            return floatval($matches[1]) * $multiplier;
        }
    }
    
    return 0;
}

/**
 * 金額同期処理（ACFフィールド間の同期）
 */
function gi_sync_amount_fields($post_id) {
    if (!$post_id || get_post_type($post_id) !== 'grant') {
        return;
    }
    
    $text_amount = get_field('max_amount', $post_id);
    $numeric_amount = get_field('max_amount_numeric', $post_id);
    
    // テキストから数値を生成
    if (!empty($text_amount) && empty($numeric_amount)) {
        $parsed = gi_parse_amount_from_text($text_amount);
        if ($parsed > 0) {
            update_field('max_amount_numeric', $parsed, $post_id);
        }
    }
    
    // 数値からテキストを生成
    if (!empty($numeric_amount) && empty($text_amount)) {
        $formatted = gi_format_amount_unified($numeric_amount);
        update_field('max_amount', $formatted, $post_id);
    }
}

/**
 * ACFフィールドを安全に取得する統一関数
 * ACFとメタフィールドの両方をチェックし、後方互換性も保持
 */
function gi_get_acf_field_safely($post_id, $field_name, $default = '') {
    if (!$post_id || !is_numeric($post_id)) {
        return $default;
    }
    
    // まずACFフィールドから取得を試みる
    if (function_exists('get_field')) {
        $value = get_field($field_name, $post_id);
        if ($value !== null && $value !== false && $value !== '') {
            return $value;
        }
    }
    
    // 次に通常のメタフィールドから取得
    $value = get_post_meta($post_id, $field_name, true);
    if ($value !== null && $value !== false && $value !== '') {
        return $value;
    }
    
    // 旧フィールド名での後方互換性チェック
    $legacy_field_mappings = array(
        'deadline_date' => array('deadline', 'deadline_timestamp'),
        'deadline' => array('deadline_date', 'deadline_text'),
        'max_amount_numeric' => array('max_amount_num', 'amount_numeric'),
        'grant_target' => array('target_business', 'target'),
        'target_business' => array('grant_target', 'target'), // 逆方向マッピング追加
        'application_status' => array('status', 'grant_status'),
        'grant_difficulty' => array('difficulty', 'application_difficulty'),
        'grant_success_rate' => array('success_rate', 'adoption_rate'),
    );
    
    if (isset($legacy_field_mappings[$field_name])) {
        foreach ($legacy_field_mappings[$field_name] as $legacy_field) {
            // ACFから取得
            if (function_exists('get_field')) {
                $value = get_field($legacy_field, $post_id);
                if ($value !== null && $value !== false && $value !== '') {
                    return $value;
                }
            }
            // メタフィールドから取得
            $value = get_post_meta($post_id, $legacy_field, true);
            if ($value !== null && $value !== false && $value !== '') {
                return $value;
            }
        }
    }
    
    return $default;
}

/**
 * 安全なメタ取得（既存関数の改善版）
 * gi_get_acf_field_safelyをラップして既存コードとの互換性を保持
 */
function gi_safe_get_meta($post_id, $key, $default = '') {
    return gi_get_acf_field_safely($post_id, $key, $default);
}

/**
 * 締切日のフォーマット関数（改善版）
 * 複数のフィールド名と形式に対応
 */
function gi_get_formatted_deadline($post_id) {
    if (!$post_id) {
        return '';
    }
    
    // 優先順位: deadline > deadline_date > deadline_text
    $deadline = gi_get_acf_field_safely($post_id, 'deadline');
    
    if (!$deadline) {
        $deadline = gi_get_acf_field_safely($post_id, 'deadline_date');
    }
    
    if (!$deadline) {
        $deadline = gi_get_acf_field_safely($post_id, 'deadline_text');
        if ($deadline) {
            return $deadline; // テキスト形式の場合はそのまま返す
        }
    }
    
    if (!$deadline) {
        return '未定';
    }
    
    // 数値形式の処理
    if (is_numeric($deadline)) {
        // 8桁の数字（YYYYMMDD）の場合
        if (strlen($deadline) == 8) {
            $year = substr($deadline, 0, 4);
            $month = substr($deadline, 4, 2);
            $day = substr($deadline, 6, 2);
            return sprintf('%s年%d月%d日', $year, intval($month), intval($day));
        }
        // UNIXタイムスタンプの場合
        elseif ($deadline > 19700101 && $deadline < 21000101) {
            return date('Y年n月j日', intval($deadline));
        }
    }
    
    // 文字列形式の日付処理
    $timestamp = strtotime($deadline);
    if ($timestamp !== false) {
        return date('Y年n月j日', $timestamp);
    }
    
    // そのまま返す
    return $deadline;
}

/**
 * メタフィールドの同期処理（改善版）
 * ACFフィールドと通常のメタフィールドを同期
 */
function gi_sync_grant_meta_on_save($post_id, $post, $update) {
    // 自動保存時はスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 助成金投稿タイプ以外はスキップ
    if ($post->post_type !== 'grant') {
        return;
    }
    
    // 権限チェック
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // ===== 金額の同期処理 =====
    $amount_text = gi_get_acf_field_safely($post_id, 'max_amount');
    if ($amount_text) {
        // 数値のみを抽出して保存
        $amount_numeric = preg_replace('/[^0-9]/', '', $amount_text);
        if ($amount_numeric) {
            update_post_meta($post_id, 'max_amount_numeric', intval($amount_numeric));
            if (function_exists('update_field')) {
                update_field('max_amount_numeric', intval($amount_numeric), $post_id);
            }
        }
    }
    
    // ===== 締切日の同期処理 =====
    $deadline = gi_get_acf_field_safely($post_id, 'deadline');
    if ($deadline) {
        $deadline_timestamp = 0;
        
        // YYYYMMDD形式の場合
        if (is_numeric($deadline) && strlen($deadline) == 8) {
            $deadline_timestamp = strtotime($deadline);
        }
        // その他の日付形式
        elseif (!is_numeric($deadline)) {
            $deadline_timestamp = strtotime($deadline);
        }
        // すでにタイムスタンプの場合
        else {
            $deadline_timestamp = intval($deadline);
        }
        
        if ($deadline_timestamp && $deadline_timestamp > 0) {
            update_post_meta($post_id, 'deadline_date', $deadline_timestamp);
            if (function_exists('update_field')) {
                update_field('deadline_date', $deadline_timestamp, $post_id);
            }
            
            // 表示用テキストも更新
            $deadline_text = date('Y年n月j日', $deadline_timestamp);
            update_post_meta($post_id, 'deadline_text', $deadline_text);
            if (function_exists('update_field')) {
                update_field('deadline_text', $deadline_text, $post_id);
            }
        }
    }
    
    // ===== ステータスの同期処理 =====
    $status = gi_get_acf_field_safely($post_id, 'application_status');
    if (!$status) {
        $status = gi_get_acf_field_safely($post_id, 'status');
    }
    
    if ($status) {
        update_post_meta($post_id, 'application_status', $status);
        if (function_exists('update_field')) {
            update_field('application_status', $status, $post_id);
        }
    } else {
        // デフォルトステータスを設定
        update_post_meta($post_id, 'application_status', 'open');
        if (function_exists('update_field')) {
            update_field('application_status', 'open', $post_id);
        }
    }
    
    // ===== その他の重要フィールドの同期 =====
    $sync_fields = array(
        'organization',
        'grant_target',
        'subsidy_rate',
        'grant_difficulty',
        'grant_success_rate',
        'eligible_expenses',
        'required_documents',
        'application_method',
        'contact_info',
        'official_url',
        'is_featured',
        'priority_order'
    );
    
    foreach ($sync_fields as $field) {
        $value = gi_get_acf_field_safely($post_id, $field);
        if ($value !== null && $value !== false && $value !== '') {
            update_post_meta($post_id, $field, $value);
        }
    }
}
add_action('save_post', 'gi_sync_grant_meta_on_save', 20, 3);

/**
 * 全ての助成金メタフィールドを取得する統一関数
 */
function gi_get_all_grant_meta($post_id) {
    if (!$post_id) {
        return array();
    }
    
    $meta_fields = array(
        // 基本情報
        'ai_summary' => gi_get_acf_field_safely($post_id, 'ai_summary', ''),
        
        // 金額情報
        'max_amount' => gi_get_acf_field_safely($post_id, 'max_amount', ''),
        'max_amount_numeric' => gi_get_acf_field_safely($post_id, 'max_amount_numeric', 0),
        'subsidy_rate' => gi_get_acf_field_safely($post_id, 'subsidy_rate', ''),
        
        // 締切情報
        'deadline' => gi_get_acf_field_safely($post_id, 'deadline', ''),
        'deadline_date' => gi_get_acf_field_safely($post_id, 'deadline_date', ''),
        'deadline_text' => gi_get_acf_field_safely($post_id, 'deadline_text', ''),
        'deadline_formatted' => gi_get_formatted_deadline($post_id),
        
        // ステータス情報
        'application_status' => gi_get_acf_field_safely($post_id, 'application_status', 'open'),
        'grant_difficulty' => gi_get_acf_field_safely($post_id, 'grant_difficulty', 'normal'),
        'grant_success_rate' => gi_get_acf_field_safely($post_id, 'grant_success_rate', 0),
        
        // 組織情報
        'organization' => gi_get_acf_field_safely($post_id, 'organization', ''),
        'grant_target' => gi_get_acf_field_safely($post_id, 'grant_target', ''),
        'application_period' => gi_get_acf_field_safely($post_id, 'application_period', ''),
        
        // URL情報
        'official_url' => gi_get_acf_field_safely($post_id, 'official_url', ''),
        
        // 詳細情報
        'eligible_expenses' => gi_get_acf_field_safely($post_id, 'eligible_expenses', ''),
        'application_method' => gi_get_acf_field_safely($post_id, 'application_method', 'オンライン申請'),
        'required_documents' => gi_get_acf_field_safely($post_id, 'required_documents', ''),
        'contact_info' => gi_get_acf_field_safely($post_id, 'contact_info', ''),
        
        // 管理用フィールド
        'is_featured' => gi_get_acf_field_safely($post_id, 'is_featured', false),
        'views_count' => gi_get_acf_field_safely($post_id, 'views_count', 0),
        'priority_order' => gi_get_acf_field_safely($post_id, 'priority_order', 100),
    );
    
    return $meta_fields;
}

/**
 * セキュリティ・ヘルパー関数群（既存のまま）
 */

// 安全な属性出力
function gi_safe_attr($value) {
    if (is_array($value)) {
        $value = implode(' ', $value);
    }
    return esc_attr($value);
}

// 安全なHTML出力
function gi_safe_escape($value) {
    if (is_array($value)) {
        return array_map('esc_html', $value);
    }
    return esc_html($value);
}

// 安全な数値フォーマット
function gi_safe_number_format($value, $decimals = 0) {
    if (!is_numeric($value)) {
        return '0';
    }
    $num = floatval($value);
    return number_format($num, $decimals);
}

// 安全な日付フォーマット
function gi_safe_date_format($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return '';
    }
    
    if (is_numeric($date)) {
        // 8桁の数字（YYYYMMDD）の場合
        if (strlen($date) == 8) {
            $year = substr($date, 0, 4);
            $month = substr($date, 4, 2);
            $day = substr($date, 6, 2);
            $timestamp = mktime(0, 0, 0, $month, $day, $year);
            return date($format, $timestamp);
        }
        // タイムスタンプの場合
        else {
            return date($format, $date);
        }
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

// 安全なパーセント表示
function gi_safe_percent_format($value, $decimals = 1) {
    if (!is_numeric($value)) {
        return '0%';
    }
    $num = floatval($value);
    return number_format($num, $decimals) . '%';
}

// 安全なURL出力
function gi_safe_url($url) {
    if (empty($url)) {
        return '';
    }
    return esc_url($url);
}

// 安全なJSON出力
function gi_safe_json($data) {
    return wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

// 安全なテキスト切り取り
function gi_safe_excerpt($text, $length = 100, $more = '...') {
    if (mb_strlen($text) <= $length) {
        return esc_html($text);
    }
    
    $excerpt = mb_substr($text, 0, $length);
    $last_space = mb_strrpos($excerpt, ' ');
    
    if ($last_space !== false) {
        $excerpt = mb_substr($excerpt, 0, $last_space);
    }
    
    return esc_html($excerpt . $more);
}

/**
 * 動的パス取得関数（既存のまま）
 */

// アセットURL取得
function gi_get_asset_url($path) {
    $path = ltrim($path, '/');
    return get_template_directory_uri() . '/' . $path;
}

// アップロードURL取得
function gi_get_upload_url($filename) {
    $upload_dir = wp_upload_dir();
    $filename = ltrim($filename, '/');
    return $upload_dir['baseurl'] . '/' . $filename;
}

// メディアURL取得（自動検出機能付き）
function gi_get_media_url($filename, $fallback = true) {
    if (empty($filename)) {
        return $fallback ? gi_get_asset_url('assets/images/placeholder.jpg') : '';
    }
    
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }
    
    $filename = str_replace([
        'http://keishi0804.xsrv.jp/wp-content/uploads/',
        'https://keishi0804.xsrv.jp/wp-content/uploads/',
        '/wp-content/uploads/'
    ], '', $filename);
    
    $filename = ltrim($filename, '/');
    
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/' . $filename;
    
    if (file_exists($file_path)) {
        return $upload_dir['baseurl'] . '/' . $filename;
    }
    
    $current_year = date('Y');
    $current_month = date('m');
    
    $possible_paths = [
        $current_year . '/' . $current_month . '/' . $filename,
        $current_year . '/' . $filename,
        'uploads/' . $filename,
        'media/' . $filename
    ];
    
    foreach ($possible_paths as $path) {
        $full_path = $upload_dir['basedir'] . '/' . $path;
        if (file_exists($full_path)) {
            return $upload_dir['baseurl'] . '/' . $path;
        }
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/images/placeholder.jpg');
    }
    
    return '';
}

// 動画URL取得
function gi_get_video_url($filename, $fallback = true) {
    $url = gi_get_media_url($filename, false);
    
    if (!empty($url)) {
        return $url;
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/videos/placeholder.mp4');
    }
    
    return '';
}

// ロゴURL取得
function gi_get_logo_url($fallback = true) {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        return wp_get_attachment_image_url($custom_logo_id, 'full');
    }
    
    $hero_logo = get_theme_mod('gi_hero_logo');
    if ($hero_logo) {
        return gi_get_media_url($hero_logo, false);
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/images/logo.png');
    }
    
    return '';
}

/**
 * 金額フォーマット関数（改善版）
 */
function gi_format_amount_man($amount_yen, $amount_text = '') {
    // 数値の場合
    if (is_numeric($amount_yen)) {
        $yen = intval($amount_yen);
        if ($yen >= 100000000) { // 1億円以上
            $oku = $yen / 100000000;
            if ($oku == floor($oku)) {
                return number_format($oku) . '億';
            } else {
                return number_format($oku, 1) . '億';
            }
        } elseif ($yen >= 10000) { // 1万円以上
            $man = $yen / 10000;
            if ($man == floor($man)) {
                return number_format($man) . '万';
            } else {
                return number_format($man, 1) . '万';
            }
        } else {
            return number_format($yen);
        }
    }
    
    // テキストから金額を抽出
    if (!empty($amount_text)) {
        // 億円の抽出
        if (preg_match('/([0-9,\.]+)\s*億円?/u', $amount_text, $m)) {
            $oku = floatval(str_replace(',', '', $m[1]));
            return number_format($oku, ($oku == floor($oku) ? 0 : 1)) . '億';
        }
        // 万円の抽出
        if (preg_match('/([0-9,\.]+)\s*万円?/u', $amount_text, $m)) {
            $man = floatval(str_replace(',', '', $m[1]));
            return number_format($man, ($man == floor($man) ? 0 : 1)) . '万';
        }
        // 円の抽出
        if (preg_match('/([0-9,]+)\s*円/u', $amount_text, $m)) {
            return gi_format_amount_man(str_replace(',', '', $m[1]));
        }
    }
    
    return '未定';
}

/**
 * ステータスマッピング関数（UI表示用）
 */
function gi_map_application_status_ui($app_status) {
    $status_map = array(
        'open' => '募集中',
        'closed' => '募集終了',
        'upcoming' => '募集予定',
        'suspended' => '一時停止',
        'active' => '募集中',  // 別名
        'ended' => '募集終了',  // 別名
    );
    
    return isset($status_map[$app_status]) ? $status_map[$app_status] : $app_status;
}

/**
 * お気に入り一覧取得（改善版）
 */
function gi_get_user_favorites($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        // 非ログインユーザーの場合はCookieから取得
        $cookie_name = 'gi_favorites';
        $favorites = isset($_COOKIE[$cookie_name]) ? array_filter(explode(',', $_COOKIE[$cookie_name])) : array();
    } else {
        // ログインユーザーの場合はユーザーメタから取得
        $favorites = get_user_meta($user_id, 'gi_favorites', true);
        if (!is_array($favorites)) {
            $favorites = array();
        }
    }
    
    // 整数値に変換して返す
    return array_map('intval', array_filter($favorites));
}

/**
 * 投稿カテゴリー取得（改善版）
 */
function gi_get_post_categories($post_id, $taxonomy = null) {
    if (!$post_id) {
        return array();
    }
    
    $post_type = get_post_type($post_id);
    
    // タクソノミーが指定されていない場合は自動判定
    if (!$taxonomy) {
        $taxonomy_map = array(
            'grant' => 'grant_category',
            'post' => 'category'
        );
        
        $taxonomy = isset($taxonomy_map[$post_type]) ? $taxonomy_map[$post_type] : 'category';
    }
    
    if (!taxonomy_exists($taxonomy)) {
        return array();
    }
    
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return array();
    }
    
    $categories = array();
    foreach ($terms as $term) {
        $categories[] = array(
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'link' => get_term_link($term),
            'count' => $term->count,
            'description' => $term->description
        );
    }
    
    return $categories;
}

/**
 * 都道府県名取得
 */
function gi_get_prefecture_name($prefecture_id) {
    $prefectures = array(
        1 => '北海道', 2 => '青森県', 3 => '岩手県', 4 => '宮城県', 5 => '秋田県',
        6 => '山形県', 7 => '福島県', 8 => '茨城県', 9 => '栃木県', 10 => '群馬県',
        11 => '埼玉県', 12 => '千葉県', 13 => '東京都', 14 => '神奈川県', 15 => '新潟県',
        16 => '富山県', 17 => '石川県', 18 => '福井県', 19 => '山梨県', 20 => '長野県',
        21 => '岐阜県', 22 => '静岡県', 23 => '愛知県', 24 => '三重県', 25 => '滋賀県',
        26 => '京都府', 27 => '大阪府', 28 => '兵庫県', 29 => '奈良県', 30 => '和歌山県',
        31 => '鳥取県', 32 => '島根県', 33 => '岡山県', 34 => '広島県', 35 => '山口県',
        36 => '徳島県', 37 => '香川県', 38 => '愛媛県', 39 => '高知県', 40 => '福岡県',
        41 => '佐賀県', 42 => '長崎県', 43 => '熊本県', 44 => '大分県', 45 => '宮崎県',
        46 => '鹿児島県', 47 => '沖縄県'
    );
    
    return isset($prefectures[$prefecture_id]) ? $prefectures[$prefecture_id] : '';
}

/**
 * 助成金カテゴリ名取得
 */
function gi_get_category_name($category_id) {
    $categories = array(
        'startup' => '起業・創業支援',
        'research' => '研究開発',
        'employment' => '雇用促進',
        'training' => '人材育成',
        'export' => '輸出促進',
        'digital' => 'デジタル化',
        'environment' => '環境・エネルギー',
        'regional' => '地域活性化',
        'it' => 'IT・デジタル化支援',
        'equipment' => '設備投資・機械導入',
        'education' => '人材育成・教育訓練',
        'innovation' => '研究開発・技術革新',
        'energy' => '省エネ・環境対策',
        'succession' => '事業承継・M&A',
        'overseas' => '海外展開・輸出促進',
        'founding' => '創業・起業支援',
        'marketing' => '販路開拓・マーケティング',
        'workstyle' => '働き方改革・労働環境',
        'tourism' => '観光・地域振興',
        'agriculture' => '農業・林業・水産業',
        'manufacturing' => '製造業・ものづくり',
        'service' => 'サービス業・小売業',
        'covid' => 'コロナ対策・事業継続',
        'diversity' => '女性・若者・シニア支援',
        'disability' => '障がい者雇用支援',
        'intellectual' => '知的財産・特許',
        'bcp' => 'BCP・リスク管理',
        'other' => 'その他・汎用'
    );
    
    return isset($categories[$category_id]) ? $categories[$category_id] : $category_id;
}

/**
 * 助成金ステータス名取得
 */
function gi_get_status_name($status) {
    $statuses = array(
        'active' => '募集中',
        'open' => '募集中',
        'upcoming' => '募集予定',
        'closed' => '募集終了',
        'ended' => '募集終了',
        'suspended' => '一時停止'
    );
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

/**
 * 検索統計データ更新・キャッシュ機能
 */
function gi_update_search_stats_cache() {
    // キャッシュから取得を試行
    $stats = wp_cache_get('grant_search_stats', 'grant_insight');
    
    if (false === $stats) {
        // キャッシュがない場合は新しく生成
        $stats = array(
            'total_grants' => wp_count_posts('grant')->publish ?? 0,
            'last_updated' => current_time('timestamp')
        );
        
        // キャッシュに保存（1時間）
        wp_cache_set('grant_search_stats', $stats, 'grant_insight', 3600);
        
        // オプションにもバックアップ保存
        update_option('gi_search_stats_backup', $stats);
    }
    
    return $stats;
}

/**
 * 検索統計データ取得（フォールバック機能付き）
 */
function gi_get_search_stats() {
    $stats = gi_update_search_stats_cache();
    
    // フォールバック用のデフォルト値
    $defaults = array(
        'total_grants' => 0,
        'last_updated' => current_time('timestamp')
    );
    
    return wp_parse_args($stats, $defaults);
}

/**
 * デバッグ用ヘルパー関数
 */
function gi_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[Grant Insight Debug] ' . $message;
        if ($data !== null) {
            $log_message .= ' | Data: ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

/**
 * ACFフィールドの存在チェック
 */
function gi_check_acf_field_exists($field_name, $post_id = null) {
    if (!function_exists('get_field_object')) {
        return false;
    }
    
    $field = get_field_object($field_name, $post_id);
    return !empty($field) && isset($field['key']);
}

/**
 * 投稿タイプごとの必須フィールドチェック
 */
function gi_validate_required_fields($post_id) {
    $post_type = get_post_type($post_id);
    $errors = array();
    
    $required_fields = array(
        'grant' => array(
            'organization' => '実施組織',
            'application_status' => '公募ステータス',
            'max_amount' => '最大金額'
        )
    );
    
    if (isset($required_fields[$post_type])) {
        foreach ($required_fields[$post_type] as $field => $label) {
            $value = gi_get_acf_field_safely($post_id, $field);
            if (empty($value)) {
                $errors[] = sprintf('%s が未入力です', $label);
            }
        }
    }
    
    return $errors;
}

/**
 * フィールド値の型変換ヘルパー
 */
function gi_convert_field_type($value, $type = 'string') {
    switch ($type) {
        case 'int':
        case 'integer':
            return intval($value);
            
        case 'float':
        case 'double':
            return floatval($value);
            
        case 'bool':
        case 'boolean':
            return (bool) $value;
            
        case 'array':
            if (!is_array($value)) {
                return array($value);
            }
            return $value;
            
        case 'json':
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return $decoded ?: $value;
            }
            return $value;
            
        default:
            return strval($value);
    }
}

/**
 * メタデータの一括更新
 */
function gi_bulk_update_meta($post_ids, $meta_data) {
    if (!is_array($post_ids) || empty($post_ids)) {
        return false;
    }
    
    $success_count = 0;
    
    foreach ($post_ids as $post_id) {
        foreach ($meta_data as $key => $value) {
            if (update_post_meta($post_id, $key, $value)) {
                $success_count++;
            }
            
            // ACFフィールドも更新
            if (function_exists('update_field')) {
                update_field($key, $value, $post_id);
            }
        }
    }
    
    return $success_count;
}

// ファイルの最後に以下を追加

/**
 * 統一された金額表示を取得
 */
function gi_get_grant_amount_display($post_id) {
    // 数値形式を優先
    $amount_numeric = intval(gi_safe_get_meta($post_id, 'max_amount_numeric', 0));
    
    if ($amount_numeric > 0) {
        if ($amount_numeric >= 100000000) {
            $oku = $amount_numeric / 100000000;
            return number_format($oku, ($oku == floor($oku) ? 0 : 1)) . '億円';
        } elseif ($amount_numeric >= 10000) {
            $man = $amount_numeric / 10000;
            return number_format($man, ($man == floor($man) ? 0 : 0)) . '万円';
        } else {
            return number_format($amount_numeric) . '円';
        }
    }
    
    // テキスト形式をフォールバック
    $amount_text = gi_safe_get_meta($post_id, 'max_amount', '');
    return !empty($amount_text) ? $amount_text : '未定';
}



/**
 * 既存データの一括修正（管理画面から実行）
 */
function gi_fix_all_grant_amounts() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $grants = get_posts([
        'post_type' => 'grant',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ]);
    
    $fixed_count = 0;
    foreach ($grants as $grant) {
        gi_sync_amount_fields($grant->ID);
        $fixed_count++;
    }
    
    return $fixed_count;
}

// 投稿保存時の自動同期フック
add_action('save_post', function($post_id) {
    if (get_post_type($post_id) !== 'grant') return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    // 金額フィールドの同期
    gi_sync_amount_fields($post_id);
}, 20, 1);

// ACFフィールド更新時の同期フック
if (function_exists('acf_add_local_field_group')) {
    add_action('acf/save_post', function($post_id) {
        if (!$post_id || !is_numeric($post_id)) return;
        if (get_post_type($post_id) !== 'grant') return;
        
        // 少し遅延させて同期
        wp_schedule_single_event(time() + 5, 'gi_delayed_sync_amount', [$post_id]);
    }, 25);
    
    // 遅延同期処理
    add_action('gi_delayed_sync_amount', 'gi_sync_amount_fields');
}
