<?php
/**
 * Grant Insight Perfect - Theme Setup File (Clean Version)
 *
 * 助成金・補助金関連機能に特化したテーマの基本設定ファイル
 * 不要な機能を削除し、必要最小限の設定のみを含みます
 *
 * @package Grant_Insight_Perfect
 * @version 7.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * テーマバージョン定数
 */
if (!defined('GI_THEME_VERSION')) {
    define('GI_THEME_VERSION', '7.0.0');
}

/**
 * テーマ基本設定
 */
function gi_setup() {
    // 基本的なテーマサポート
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption'
    ));
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-width'  => true,
        'flex-height' => true,
    ));
    add_theme_support('automatic-feed-links');
    
    // 助成金関連の画像サイズ
    add_image_size('grant-thumbnail', 400, 300, true);
    add_image_size('grant-featured', 800, 450, true);
    
    // 言語ファイル
    load_theme_textdomain('grant-insight', get_template_directory() . '/languages');
    
    // メニュー登録
    register_nav_menus(array(
        'primary' => 'メインメニュー',
        'footer' => 'フッターメニュー'
    ));
}
add_action('after_setup_theme', 'gi_setup');

/**
 * コンテンツ幅設定
 */
function gi_content_width() {
    $GLOBALS['content_width'] = 1200;
}
add_action('after_setup_theme', 'gi_content_width', 0);

/**
 * スクリプト・スタイルの読み込み（最小限）
 */
function gi_enqueue_scripts() {
    // メインスタイルシート
    wp_enqueue_style('gi-style', get_stylesheet_uri(), array(), GI_THEME_VERSION);
    
    // 最適化されたCSS
    wp_enqueue_style('gi-optimized', get_template_directory_uri() . '/assets/css/optimized.css', array(), GI_THEME_VERSION);
    
    // Google Fonts（日本語フォント）
    wp_enqueue_style('google-fonts-noto', 'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap', array(), null);
    
    // メインJavaScript
    wp_enqueue_script('gi-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), GI_THEME_VERSION, true);
    
    // AJAX設定（必要に応じてmain.jsで使用）
    wp_localize_script('gi-main', 'gi_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'gi_enqueue_scripts');

/**
 * ウィジェットエリア登録（シンプル版）
 */
function gi_widgets_init() {
    // サイドバー
    register_sidebar(array(
        'name'          => 'サイドバー',
        'id'            => 'sidebar-1',
        'description'   => 'サイドバーウィジェットエリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    // フッター
    register_sidebar(array(
        'name'          => 'フッター',
        'id'            => 'footer-1',
        'description'   => 'フッターウィジェットエリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'gi_widgets_init');

/**
 * カスタマイザー設定（最小限）
 */
function gi_customize_register($wp_customize) {
    // 助成金表示設定セクション
    $wp_customize->add_section('gi_grant_display', array(
        'title' => '助成金表示設定',
        'priority' => 30,
    ));
    
    // 1ページあたりの表示件数
    $wp_customize->add_setting('gi_grants_per_page', array(
        'default' => 12,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('gi_grants_per_page', array(
        'label' => '1ページあたりの表示件数',
        'section' => 'gi_grant_display',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 6,
            'max' => 30,
            'step' => 3,
        ),
    ));
    
    // グリッド表示の列数
    $wp_customize->add_setting('gi_grid_columns', array(
        'default' => 3,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('gi_grid_columns', array(
        'label' => 'グリッド表示の列数',
        'section' => 'gi_grant_display',
        'type' => 'select',
        'choices' => array(
            2 => '2列',
            3 => '3列',
            4 => '4列',
        ),
    ));
}
add_action('customize_register', 'gi_customize_register');

/**
 * 助成金検索機能の強化
 */
function gi_enhance_grant_search($query) {
    if (!is_admin() && $query->is_main_query()) {
        // 助成金アーカイブページの表示件数
        if (is_post_type_archive('grant') || is_tax('grant_category') || is_tax('grant_prefecture')) {
            $per_page = get_theme_mod('gi_grants_per_page', 12);
            $query->set('posts_per_page', $per_page);
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
        
        // 検索結果に助成金を含める
        if ($query->is_search()) {
            $post_types = $query->get('post_type');
            if (empty($post_types)) {
                $query->set('post_type', array('post', 'grant'));
            }
        }
    }
}
add_action('pre_get_posts', 'gi_enhance_grant_search');

/**
 * パンくずリスト生成関数
 */
function gi_breadcrumbs() {
    if (is_front_page()) return;
    
    echo '<nav class="breadcrumbs">';
    echo '<a href="' . home_url() . '">ホーム</a>';
    
    if (is_post_type_archive('grant')) {
        echo ' > 助成金・補助金一覧';
    } elseif (is_tax('grant_category')) {
        echo ' > <a href="' . get_post_type_archive_link('grant') . '">助成金・補助金一覧</a>';
        echo ' > ' . single_term_title('', false);
    } elseif (is_tax('grant_prefecture')) {
        echo ' > <a href="' . get_post_type_archive_link('grant') . '">助成金・補助金一覧</a>';
        echo ' > ' . single_term_title('', false);
    } elseif (is_singular('grant')) {
        echo ' > <a href="' . get_post_type_archive_link('grant') . '">助成金・補助金一覧</a>';
        echo ' > ' . get_the_title();
    } elseif (is_page()) {
        echo ' > ' . get_the_title();
    } elseif (is_single()) {
        $categories = get_the_category();
        if ($categories) {
            echo ' > <a href="' . get_category_link($categories[0]->term_id) . '">' . $categories[0]->name . '</a>';
        }
        echo ' > ' . get_the_title();
    }
    
    echo '</nav>';
}

/**
 * 助成金関連のヘルパー関数
 */

// 助成金の締切日を取得
function gi_get_grant_deadline($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $deadline = get_post_meta($post_id, 'grant_deadline', true);
    return $deadline ? date('Y年n月j日', strtotime($deadline)) : '未定';
}

// 助成金の金額を取得
function gi_get_grant_amount($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $amount = get_post_meta($post_id, 'grant_amount', true);
    return $amount ? number_format($amount) . '円' : '要問合せ';
}

// 助成金のステータスを取得
function gi_get_grant_status($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $deadline = get_post_meta($post_id, 'grant_deadline', true);
    
    if (!$deadline) return 'active';
    
    $deadline_time = strtotime($deadline);
    $current_time = current_time('timestamp');
    
    if ($deadline_time < $current_time) {
        return 'expired';
    } elseif ($deadline_time - $current_time < 7 * 24 * 60 * 60) {
        return 'soon';
    } else {
        return 'active';
    }
}

/**
 * 管理画面用のスタイル
 */
function gi_admin_styles() {
    echo '<style>
        #adminmenu .menu-icon-grant div.wp-menu-image:before {
            content: "\f155";
        }
        .grant-status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .grant-status-active { background: #7ad03a; color: white; }
        .grant-status-soon { background: #ffba00; color: white; }
        .grant-status-expired { background: #dd3333; color: white; }
    </style>';
}
add_action('admin_head', 'gi_admin_styles');

/**
 * セキュリティ強化（最小限）
 */
function gi_security_headers() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}
add_action('send_headers', 'gi_security_headers');

// WordPressバージョン情報を隠す
remove_action('wp_head', 'wp_generator');

// 不要なヘッダー情報を削除
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');

/**
 * ページネーション関数
 */
function gi_pagination($pages = '') {
    global $paged;
    
    if (empty($paged)) $paged = 1;
    
    if ($pages == '') {
        global $wp_query;
        $pages = $wp_query->max_num_pages;
        if (!$pages) $pages = 1;
    }
    
    if ($pages != 1) {
        echo '<div class="pagination">';
        
        if ($paged > 1) {
            echo '<a href="' . get_pagenum_link($paged - 1) . '" class="prev">前へ</a>';
        }
        
        for ($i = 1; $i <= $pages; $i++) {
            if ($paged == $i) {
                echo '<span class="current">' . $i . '</span>';
            } else {
                echo '<a href="' . get_pagenum_link($i) . '">' . $i . '</a>';
            }
        }
        
        if ($paged < $pages) {
            echo '<a href="' . get_pagenum_link($paged + 1) . '" class="next">次へ</a>';
        }
        
        echo '</div>';
    }
}

// デバッグログ
if (WP_DEBUG) {
    error_log('Grant Insight Perfect (Clean) - Version: ' . GI_THEME_VERSION);
}