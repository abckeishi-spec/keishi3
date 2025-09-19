<?php
/**
 * ACFフィールド定義ファイル（改善版）
 * 
 * @package Grant_Insight_Perfect
 * @version 2.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACFフィールドグループをPHPで定義
 * JSONファイルに依存せず、コードベースで管理
 */
function gi_register_acf_field_groups() {
    
    // ACFが有効でない場合は処理しない
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    /**
     * 助成金詳細フィールドグループ
     */
    acf_add_local_field_group(array(
        'key' => 'group_grant_details',
        'title' => '助成金詳細情報',
        'fields' => array(
            
            // ========== 基本情報 ==========
            array(
                'key' => 'field_ai_summary',
                'label' => 'AIによる3行要約',
                'name' => 'ai_summary',
                'type' => 'wysiwyg',
                'instructions' => '公募要領などの要点を3行程度でまとめたものを入力してください。',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 0,
                'delay' => 0,
            ),
            
            // ========== 金額情報 ==========
            array(
                'key' => 'field_max_amount',
                'label' => '最大金額（表示用）',
                'name' => 'max_amount',
                'type' => 'text',
                'instructions' => '例: 200万円, 50万円/人',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '200万円',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
            ),
            
            array(
                'key' => 'field_max_amount_numeric',
                'label' => '最大金額（数値）',
                'name' => 'max_amount_numeric',
                'type' => 'number',
                'instructions' => '検索・ソート用の数値（円単位）',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 0,
                'placeholder' => '2000000',
                'prepend' => '¥',
                'append' => '',
                'min' => 0,
                'max' => '',
                'step' => 1,
            ),
            
            array(
                'key' => 'field_subsidy_rate',
                'label' => '補助率',
                'name' => 'subsidy_rate',
                'type' => 'text',
                'instructions' => '例: 2/3以内, 1/2以内, 定額',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '2/3',
                'placeholder' => '2/3以内',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
            ),
            
            // ========== 締切情報（統一版） ==========
            array(
                'key' => 'field_deadline',
                'label' => '締切日',
                'name' => 'deadline',
                'type' => 'date_picker',
                'instructions' => '助成金の申請締切日を選択してください',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'Y/m/d',
                'return_format' => 'Ymd',
                'first_day' => 0,
            ),
            
            array(
                'key' => 'field_deadline_date',
                'label' => '締切日（数値）',
                'name' => 'deadline_date',
                'type' => 'number',
                'instructions' => '自動計算されます（UNIXタイムスタンプ）',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => 'hidden',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'min' => '',
                'max' => '',
                'step' => '',
                'readonly' => 1,
            ),
            
            array(
                'key' => 'field_deadline_text',
                'label' => '締切（表示用テキスト）',
                'name' => 'deadline_text',
                'type' => 'text',
                'instructions' => '例: 2024年12月27日, 通年, 随時受付',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '34',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '2024年12月27日',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
            ),
            
            // ========== ステータス情報 ==========
            array(
                'key' => 'field_application_status',
                'label' => '公募ステータス',
                'name' => 'application_status',
                'type' => 'select',
                'instructions' => '現在の公募状態を選択してください',
                'required' => 1,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'open' => '募集中',
                    'closed' => '募集終了',
                    'upcoming' => '募集開始前',
                    'suspended' => '一時停止'
                ),
                'default_value' => array(
                    0 => 'open',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'ajax' => 0,
                'return_format' => 'value',
                'placeholder' => '',
            ),
            
            array(
                'key' => 'field_grant_difficulty',
                'label' => '申請難易度',
                'name' => 'grant_difficulty',
                'type' => 'select',
                'instructions' => '申請の難易度を選択してください',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'easy' => '易しい',
                    'normal' => '普通',
                    'hard' => '難しい',
                    'expert' => '専門的'
                ),
                'default_value' => array(
                    0 => 'normal',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'ajax' => 0,
                'return_format' => 'value',
                'placeholder' => '',
            ),
            
            array(
                'key' => 'field_grant_success_rate',
                'label' => '採択率（%）',
                'name' => 'grant_success_rate',
                'type' => 'number',
                'instructions' => '過去の採択率を0-100で入力',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '34',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 50,
                'placeholder' => '',
                'prepend' => '',
                'append' => '%',
                'min' => 0,
                'max' => 100,
                'step' => 1,
            ),
            
            // ========== 組織情報 ==========
            array(
                'key' => 'field_organization',
                'label' => '実施組織',
                'name' => 'organization',
                'type' => 'text',
                'instructions' => '助成金を実施する組織名を入力してください',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '経済産業省',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
            ),
            
            array(
                'key' => 'field_grant_target',
                'label' => '対象事業者',
                'name' => 'grant_target',
                'type' => 'text',
                'instructions' => '対象となる事業者の種類',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '中小企業',
                'placeholder' => '中小企業・小規模事業者',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
            ),
            
            // ========== 申請期間 ==========
            array(
                'key' => 'field_application_period',
                'label' => '申請期間',
                'name' => 'application_period',
                'type' => 'text',
                'instructions' => '申請受付期間を入力してください',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '2024年4月1日 ～ 2024年12月31日',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
            ),
            
            // ========== URL情報 ==========
            array(
                'key' => 'field_official_url',
                'label' => '公式サイトURL',
                'name' => 'official_url',
                'type' => 'url',
                'instructions' => '助成金の公式サイトURLを入力してください',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => 'https://example.com',
            ),
            
            // ========== 詳細情報 ==========
            array(
                'key' => 'field_eligible_expenses',
                'label' => '対象経費',
                'name' => 'eligible_expenses',
                'type' => 'textarea',
                'instructions' => '補助対象となる経費の種類',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '設備費、システム導入費、コンサルティング費等',
                'maxlength' => '',
                'rows' => 3,
                'new_lines' => 'br',
            ),
            
            array(
                'key' => 'field_application_method',
                'label' => '申請方法',
                'name' => 'application_method',
                'type' => 'select',
                'instructions' => '申請方法を選択してください',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'online' => 'オンライン申請',
                    'mail' => '郵送',
                    'both' => 'オンライン・郵送両方',
                    'other' => 'その他'
                ),
                'default_value' => array(
                    0 => 'online',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'ajax' => 0,
                'return_format' => 'label',
                'placeholder' => '',
            ),
            
            array(
                'key' => 'field_required_documents',
                'label' => '必要書類',
                'name' => 'required_documents',
                'type' => 'textarea',
                'instructions' => '申請に必要な書類一覧',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '申請書、事業計画書、見積書、会社概要等',
                'maxlength' => '',
                'rows' => 3,
                'new_lines' => 'br',
            ),
            
            // ========== 連絡先情報 ==========
            array(
                'key' => 'field_contact_info',
                'label' => '問い合わせ先',
                'name' => 'contact_info',
                'type' => 'textarea',
                'instructions' => '問い合わせ先の情報を入力してください',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '担当部署名、電話番号、メールアドレスなど',
                'maxlength' => '',
                'rows' => 2,
                'new_lines' => 'br',
            ),
            
            // ========== 管理用フィールド ==========
            array(
                'key' => 'field_is_featured',
                'label' => '注目の助成金',
                'name' => 'is_featured',
                'type' => 'true_false',
                'instructions' => 'トップページなどで優先表示する場合はチェック',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'message' => '',
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => '注目',
                'ui_off_text' => '通常',
            ),
            
            array(
                'key' => 'field_views_count',
                'label' => '閲覧数',
                'name' => 'views_count',
                'type' => 'number',
                'instructions' => '自動的にカウントされます',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 0,
                'placeholder' => '',
                'prepend' => '',
                'append' => '回',
                'min' => 0,
                'max' => '',
                'step' => 1,
                'readonly' => 1,
            ),
            
            array(
                'key' => 'field_priority_order',
                'label' => '表示優先度',
                'name' => 'priority_order',
                'type' => 'number',
                'instructions' => '数値が小さいほど優先的に表示されます',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '34',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 100,
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'min' => 0,
                'max' => 999,
                'step' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'grant',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '助成金・補助金の詳細情報を管理します',
        'show_in_rest' => 0,
    ));
    
}

// ACFが有効な場合のみ実行
add_action('acf/init', 'gi_register_acf_field_groups');

/**
 * ACFフィールドの初期値設定
 */
function gi_set_acf_default_values($post_id, $post, $update) {
    // 新規投稿の場合のみ
    if ($update) {
        return;
    }
    
    // 助成金投稿タイプの場合
    if ($post->post_type === 'grant') {
        // デフォルト値を設定
        update_field('grant_difficulty', 'normal', $post_id);
        update_field('grant_success_rate', 50, $post_id);
        update_field('subsidy_rate', '2/3', $post_id);
        update_field('grant_target', '中小企業', $post_id);
        update_field('application_status', 'open', $post_id);
        update_field('application_method', 'online', $post_id);
        update_field('views_count', 0, $post_id);
        update_field('priority_order', 100, $post_id);
    }
    

}
add_action('wp_insert_post', 'gi_set_acf_default_values', 10, 3);

/**
 * 閲覧数の自動カウントアップ
 */
function gi_increment_views_count() {
    if (is_singular()) {
        global $post;
        $post_type = get_post_type($post->ID);
        
        // 助成金投稿タイプのみ対応
        $field_name = '';
        switch ($post_type) {
            case 'grant':
                $field_name = 'views_count';
                break;
        }
        
        if ($field_name) {
            $current_views = intval(get_field($field_name, $post->ID));
            update_field($field_name, $current_views + 1, $post->ID);
        }
    }
}
add_action('wp_head', 'gi_increment_views_count');

/**
 * 締切日の自動同期
 * deadline フィールドが更新されたら deadline_date（数値）も自動更新
 */
function gi_sync_deadline_fields($value, $post_id, $field) {
    if ($field['name'] === 'deadline' && !empty($value)) {
        // Ymd形式をUNIXタイムスタンプに変換
        $timestamp = strtotime($value);
        if ($timestamp) {
            update_field('deadline_date', $timestamp, $post_id);
        }
    }
    return $value;
}
add_filter('acf/update_value/name=deadline', 'gi_sync_deadline_fields', 10, 3);

/**
 * ACFフィールドの検証
 */
function gi_validate_acf_fields() {
    if (!function_exists('acf_get_field_groups')) {
        return;
    }
    
    $required_fields = array(
        'grant' => array(
            'max_amount',
            'max_amount_numeric',
            'deadline',
            'organization',
            'application_status',
            'grant_difficulty',
            'grant_success_rate',
            'subsidy_rate',
            'grant_target'
        )
    );
    
    $issues = array();
    
    foreach ($required_fields as $post_type => $fields) {
        foreach ($fields as $field_name) {
            $field = acf_get_field($field_name);
            if (!$field) {
                $issues[] = "Missing ACF field: {$field_name} for post type: {$post_type}";
            }
        }
    }
    
    if (!empty($issues)) {
        error_log('ACF Field Validation Issues: ' . implode(', ', $issues));
    }
    
    return empty($issues);
}
add_action('acf/init', 'gi_validate_acf_fields', 20);

/**
 * ACFフィールドのエクスポート設定
 * 管理画面でのフィールド管理を容易にする
 */
function gi_acf_json_save_point($path) {
    $path = get_stylesheet_directory() . '/acf-json';
    if (!file_exists($path)) {
        wp_mkdir_p($path);
    }
    return $path;
}
add_filter('acf/settings/save_json', 'gi_acf_json_save_point');

/**
 * ACFフィールドの読み込み設定
 */
function gi_acf_json_load_point($paths) {
    unset($paths[0]);
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
}
add_filter('acf/settings/load_json', 'gi_acf_json_load_point');