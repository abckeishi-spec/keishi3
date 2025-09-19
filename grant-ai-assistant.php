<?php
/*
Plugin Name: Grant AI Assistant Pro
Description: 次世代AI対話型助成金検索システム - エンタープライズグレード統合プラットフォーム
Version: 2.0.0
Author: Grant Insight Team
Requires at least: 5.5
Tested up to: 6.4
Requires PHP: 7.4
License: GPL v3 or later
Network: false
Text Domain: grant-ai-assistant-pro
Domain Path: /languages
*/

// セキュリティ: 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// プラグイン定数定義
define('GAAP_VERSION', '2.0.0');
define('GAAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GAAP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GAAP_PLUGIN_FILE', __FILE__);
define('GAAP_CACHE_GROUP', 'grant_ai_assistant_pro');
define('GAAP_CACHE_DURATION', 30 * MINUTE_IN_SECONDS);
define('GAAP_RATE_LIMIT', 120); // 1時間あたり120リクエスト
define('GAAP_MAX_RETRIES', 3);
define('GAAP_API_TIMEOUT', 30);
define('GAAP_ML_CONFIDENCE_THRESHOLD', 0.75);

/**
 * プラグインアクティベーション処理
 */
function gaap_activation_hook() {
    // データベーステーブル作成
    GAAP_Database_Manager::create_tables();
    
    // デフォルト設定
    add_option('gaap_enable_chat', true);
    add_option('gaap_max_results', 8);
    add_option('gaap_ai_provider', 'openai');
    add_option('gaap_enable_analytics', true);
    add_option('gaap_enable_ab_test', false);
    add_option('gaap_cache_duration', GAAP_CACHE_DURATION);
    add_option('gaap_ml_confidence_threshold', GAAP_ML_CONFIDENCE_THRESHOLD);
    
    // キャッシュグループ登録
    wp_cache_add_global_groups(array(GAAP_CACHE_GROUP));
    
    // レート制限テーブル初期化
    GAAP_Rate_Limiter::init_rate_limit_table();
    
    // 管理者通知
    set_transient('gaap_activation_notice', true, 30);
}

/**
 * プラグイン非アクティベーション処理
 */
function gaap_deactivation_hook() {
    // キャッシュクリア
    wp_cache_flush_group(GAAP_CACHE_GROUP);
    
    // 一時データクリア
    delete_transient('gaap_activation_notice');
    delete_transient('gaap_api_status_cache');
}

/**
 * プラグインアンインストール処理
 */
function gaap_uninstall_hook() {
    // データベーステーブル削除
    GAAP_Database_Manager::drop_tables();
    
    // 全設定削除
    $options = array(
        'gaap_openai_api_key', 'gaap_claude_api_key', 'gaap_gemini_api_key',
        'gaap_enable_chat', 'gaap_max_results', 'gaap_ai_provider',
        'gaap_enable_analytics', 'gaap_enable_ab_test', 'gaap_cache_duration',
        'gaap_ml_confidence_threshold', 'gaap_custom_prompts'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // キャッシュ完全削除
    wp_cache_flush();
}

/**
 * Grant AI Assistant Pro - エンタープライズグレードメインクラス
 * 
 * 次世代AIエンジン搭載の助成金検索プラットフォーム
 * - マルチAIプロバイダー対応（OpenAI GPT-4, Claude, Gemini）
 * - 高度なML学習アルゴリズム
 * - リアルタイム分析とA/Bテスト
 * - エンタープライズセキュリティ
 * 
 * @since 2.0.0
 */
class Grant_AI_Assistant_Pro {
    
    /**
     * シングルトンインスタンス
     */
    private static $instance = null;
    
    /**
     * プラグインコンポーネント
     */
    private $ai_engine;
    private $cache_manager;
    private $logger;
    private $analytics;
    private $rate_limiter;
    private $security;
    
    /**
     * AI設定
     */
    private $ai_providers = array(
        'openai' => 'OpenAI GPT-4',
        'claude' => 'Anthropic Claude',
        'gemini' => 'Google Gemini Pro'
    );
    
    /**
     * シングルトンアクセス
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * プライベートコンストラクタ
     */
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
    }
    
    /**
     * コンポーネント初期化
     */
    private function init_components() {
        // コアコンポーネント
        $this->cache_manager = new GAAP_Cache_Manager();
        $this->logger = new GAAP_Logger();
        $this->rate_limiter = new GAAP_Rate_Limiter();
        $this->security = new GAAP_Security_Manager();
        
        // AI エンジン初期化
        $this->ai_engine = new GAAP_AI_Engine($this->cache_manager, $this->logger);
        
        // 分析エンジン
        if (get_option('gaap_enable_analytics', true)) {
            $this->analytics = new GAAP_Analytics_Engine();
        }
    }
    
    /**
     * WordPressフック初期化
     */
    private function init_hooks() {
        // 基本フック
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // 管理画面
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_notices', array($this, 'admin_notices'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }
        
        // AJAX処理
        add_action('wp_ajax_gaap_chat', array($this, 'handle_ajax_chat'));
        add_action('wp_ajax_nopriv_gaap_chat', array($this, 'handle_ajax_chat'));
        add_action('wp_ajax_gaap_test_api', array($this, 'handle_ajax_test_api'));
        add_action('wp_ajax_gaap_analytics', array($this, 'handle_ajax_analytics'));
        
        // ショートコード
        add_shortcode('grant_ai_chat', array($this, 'render_chat_shortcode'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // セキュリティフック
        add_action('wp_login', array($this->security, 'log_login_attempt'));
        add_action('wp_login_failed', array($this->security, 'log_failed_login'));
    }
    
    /**
     * プラグイン初期化
     */
    public function init() {
        // 言語ファイル読み込み
        load_plugin_textdomain(
            'grant-ai-assistant-pro', 
            false, 
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
        
        // スケジュールタスク登録
        if (!wp_next_scheduled('gaap_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'gaap_cleanup_logs');
        }
        
        // クリーンアップフック
        add_action('gaap_cleanup_logs', array($this->logger, 'cleanup_old_logs'));
    }
    
    /**
     * フロントエンドスクリプト・スタイル読み込み
     */
    public function enqueue_scripts() {
        // スタイルシート
        wp_enqueue_style(
            'gaap-style',
            GAAP_PLUGIN_URL . 'assets/style.css',
            array(),
            GAAP_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'gaap-script',
            GAAP_PLUGIN_URL . 'assets/script.js',
            array('jquery'),
            GAAP_VERSION,
            true
        );
        
        // AJAX設定
        wp_localize_script('gaap-script', 'gaapConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('gaap/v1/'),
            'nonce' => wp_create_nonce('gaap_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'version' => GAAP_VERSION,
            'settings' => array(
                'maxMessageLength' => 1000,
                'typingSpeed' => 30,
                'retryAttempts' => GAAP_MAX_RETRIES,
                'cacheTimeout' => GAAP_CACHE_DURATION,
                'enableAnalytics' => get_option('gaap_enable_analytics', true),
                'aiProvider' => get_option('gaap_ai_provider', 'openai')
            ),
            'strings' => array(
                'loading' => __('AIが回答を準備中...', 'grant-ai-assistant-pro'),
                'error' => __('エラーが発生しました', 'grant-ai-assistant-pro'),
                'retry' => __('再試行', 'grant-ai-assistant-pro'),
                'rateLimit' => __('リクエスト制限に達しました', 'grant-ai-assistant-pro'),
                'networkError' => __('ネットワークエラー', 'grant-ai-assistant-pro'),
                'typing' => __('入力中...', 'grant-ai-assistant-pro'),
                'thinking' => __('考え中...', 'grant-ai-assistant-pro')
            )
        ));
    }
    
    /**
     * 管理画面スクリプト・スタイル読み込み
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'grant-ai-assistant') === false) {
            return;
        }
        
        wp_enqueue_style(
            'gaap-admin-style',
            GAAP_PLUGIN_URL . 'assets/admin-style.css',
            array(),
            GAAP_VERSION
        );
        
        wp_enqueue_script(
            'gaap-admin-script',
            GAAP_PLUGIN_URL . 'assets/admin-script.js',
            array('jquery', 'chart-js'),
            GAAP_VERSION,
            true
        );
        
        // Chart.js CDN
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js',
            array(),
            '4.4.0',
            true
        );
        
        wp_localize_script('gaap-admin-script', 'gaapAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gaap_admin_nonce'),
            'strings' => array(
                'testConnection' => __('接続テスト中...', 'grant-ai-assistant-pro'),
                'testSuccess' => __('接続成功', 'grant-ai-assistant-pro'),
                'testFailed' => __('接続失敗', 'grant-ai-assistant-pro')
            )
        ));
    }
    
    /**
     * 管理画面メニュー追加
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Grant AI Assistant Pro', 'grant-ai-assistant-pro'),
            __('Grant AI Pro', 'grant-ai-assistant-pro'),
            'manage_options',
            'grant-ai-assistant-pro',
            array($this, 'admin_dashboard_page'),
            'dashicons-robot',
            30
        );
        
        add_submenu_page(
            'grant-ai-assistant-pro',
            __('ダッシュボード', 'grant-ai-assistant-pro'),
            __('ダッシュボード', 'grant-ai-assistant-pro'),
            'manage_options',
            'grant-ai-assistant-pro',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'grant-ai-assistant-pro',
            __('AI設定', 'grant-ai-assistant-pro'),
            __('AI設定', 'grant-ai-assistant-pro'),
            'manage_options',
            'gaap-ai-settings',
            array($this, 'admin_ai_settings_page')
        );
        
        add_submenu_page(
            'grant-ai-assistant-pro',
            __('分析レポート', 'grant-ai-assistant-pro'),
            __('分析レポート', 'grant-ai-assistant-pro'),
            'manage_options',
            'gaap-analytics',
            array($this, 'admin_analytics_page')
        );
        
        add_submenu_page(
            'grant-ai-assistant-pro',
            __('システムログ', 'grant-ai-assistant-pro'),
            __('システムログ', 'grant-ai-assistant-pro'),
            'manage_options',
            'gaap-logs',
            array($this, 'admin_logs_page')
        );
    }
    
    /**
     * 設定登録
     */
    public function register_settings() {
        // AI設定グループ
        register_setting('gaap_ai_settings', 'gaap_openai_api_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this->security, 'sanitize_api_key')
        ));
        
        register_setting('gaap_ai_settings', 'gaap_claude_api_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this->security, 'sanitize_api_key')
        ));
        
        register_setting('gaap_ai_settings', 'gaap_gemini_api_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this->security, 'sanitize_api_key')
        ));
        
        register_setting('gaap_ai_settings', 'gaap_ai_provider');
        register_setting('gaap_ai_settings', 'gaap_custom_prompts');
        
        // 一般設定グループ
        register_setting('gaap_general_settings', 'gaap_enable_chat');
        register_setting('gaap_general_settings', 'gaap_max_results');
        register_setting('gaap_general_settings', 'gaap_cache_duration');
        register_setting('gaap_general_settings', 'gaap_ml_confidence_threshold');
        
        // 分析設定グループ
        register_setting('gaap_analytics_settings', 'gaap_enable_analytics');
        register_setting('gaap_analytics_settings', 'gaap_enable_ab_test');
        register_setting('gaap_analytics_settings', 'gaap_data_retention_days');
    }
    
    /**
     * 管理画面通知
     */
    public function admin_notices() {
        if (get_transient('gaap_activation_notice')) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Grant AI Assistant Pro が正常にアクティベートされました！', 'grant-ai-assistant-pro') . '</strong></p>';
            echo '<p>' . sprintf(
                __('設定を完了するには <a href="%s">AI設定ページ</a> でAPIキーを設定してください。', 'grant-ai-assistant-pro'),
                admin_url('admin.php?page=gaap-ai-settings')
            ) . '</p>';
            echo '</div>';
            delete_transient('gaap_activation_notice');
        }
        
        // API接続エラー通知
        if ($this->check_api_errors()) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . __('AI APIエラー', 'grant-ai-assistant-pro') . '</strong></p>';
            echo '<p>' . __('設定されたAI APIで接続エラーが発生しています。', 'grant-ai-assistant-pro') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * ダッシュボードページ
     */
    public function admin_dashboard_page() {
        $stats = $this->get_dashboard_stats();
        include GAAP_PLUGIN_PATH . 'templates/admin/dashboard.php';
    }
    
    /**
     * AI設定ページ
     */
    public function admin_ai_settings_page() {
        $current_provider = get_option('gaap_ai_provider', 'openai');
        $api_keys = array(
            'openai' => get_option('gaap_openai_api_key', ''),
            'claude' => get_option('gaap_claude_api_key', ''),
            'gemini' => get_option('gaap_gemini_api_key', '')
        );
        
        include GAAP_PLUGIN_PATH . 'templates/admin/ai-settings.php';
    }
    
    /**
     * 分析ページ
     */
    public function admin_analytics_page() {
        if (!$this->analytics) {
            wp_die(__('分析機能が無効になっています。', 'grant-ai-assistant-pro'));
        }
        
        $analytics_data = $this->analytics->get_analytics_data();
        include GAAP_PLUGIN_PATH . 'templates/admin/analytics.php';
    }
    
    /**
     * ログページ
     */
    public function admin_logs_page() {
        $logs = $this->logger->get_recent_logs(100);
        include GAAP_PLUGIN_PATH . 'templates/admin/logs.php';
    }
    
    /**
     * チャットショートコード
     */
    public function render_chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '600px',
            'width' => '100%',
            'title' => __('AI助成金コンシェルジュ', 'grant-ai-assistant-pro'),
            'theme' => 'default',
            'enable_voice' => false,
            'enable_export' => true,
            'max_messages' => 50
        ), $atts, 'grant_ai_chat');
        
        // 設定チェック
        if (!$this->is_properly_configured()) {
            return $this->render_configuration_notice();
        }
        
        // レート制限チェック
        if ($this->rate_limiter->is_rate_limited()) {
            return $this->render_rate_limit_notice();
        }
        
        $chat_id = 'gaap-chat-' . wp_generate_password(12, false);
        
        ob_start();
        include GAAP_PLUGIN_PATH . 'templates/chat-interface.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX チャット処理
     */
    public function handle_ajax_chat() {
        // セキュリティ検証
        if (!wp_verify_nonce($_POST['nonce'], 'gaap_nonce')) {
            wp_send_json_error(__('セキュリティエラー', 'grant-ai-assistant-pro'));
        }
        
        // レート制限チェック
        if ($this->rate_limiter->is_rate_limited()) {
            wp_send_json_error(__('リクエスト制限に達しました', 'grant-ai-assistant-pro'));
        }
        
        // 入力検証
        $message = sanitize_textarea_field($_POST['message']);
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        
        if (empty($message) || strlen($message) > 1000) {
            wp_send_json_error(__('メッセージが無効です', 'grant-ai-assistant-pro'));
        }
        
        try {
            // AI処理
            $response = $this->ai_engine->process_message($message, $conversation_id);
            
            // 分析データ記録
            if ($this->analytics) {
                $this->analytics->record_interaction($message, $response);
            }
            
            // ログ記録
            $this->logger->log('chat_interaction', array(
                'message_length' => strlen($message),
                'response_confidence' => $response['confidence'] ?? 0,
                'processing_time' => $response['processing_time'] ?? 0
            ));
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            $this->logger->log('chat_error', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ), 'error');
            
            wp_send_json_error(__('AI処理エラーが発生しました', 'grant-ai-assistant-pro'));
        }
    }
    
    /**
     * AJAX API接続テスト
     */
    public function handle_ajax_test_api() {
        if (!wp_verify_nonce($_POST['nonce'], 'gaap_admin_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('権限がありません', 'grant-ai-assistant-pro'));
        }
        
        $provider = sanitize_text_field($_POST['provider']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        $result = $this->ai_engine->test_api_connection($provider, $api_key);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * REST API ルート登録
     */
    public function register_rest_routes() {
        register_rest_route('gaap/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_chat_endpoint'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));
        
        register_rest_route('gaap/v1', '/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_analytics_endpoint'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));
    }
    
    /**
     * REST権限チェック
     */
    public function rest_permission_check($request) {
        return wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
    }
    
    /**
     * 設定チェック
     */
    private function is_properly_configured() {
        $provider = get_option('gaap_ai_provider', 'openai');
        $api_key = get_option('gaap_' . $provider . '_api_key', '');
        
        return !empty($api_key) && get_option('gaap_enable_chat', false);
    }
    
    /**
     * API エラーチェック
     */
    private function check_api_errors() {
        $cached_status = get_transient('gaap_api_status_cache');
        
        if ($cached_status === false) {
            $provider = get_option('gaap_ai_provider', 'openai');
            $api_key = get_option('gaap_' . $provider . '_api_key', '');
            
            if (!empty($api_key)) {
                $test_result = $this->ai_engine->test_api_connection($provider, $api_key);
                set_transient('gaap_api_status_cache', $test_result['success'], 300);
                return !$test_result['success'];
            }
        }
        
        return !$cached_status;
    }
    
    /**
     * ダッシュボード統計データ取得
     */
    private function get_dashboard_stats() {
        return array(
            'total_conversations' => $this->analytics ? $this->analytics->get_total_conversations() : 0,
            'today_interactions' => $this->analytics ? $this->analytics->get_today_interactions() : 0,
            'average_satisfaction' => $this->analytics ? $this->analytics->get_average_satisfaction() : 0,
            'top_queries' => $this->analytics ? $this->analytics->get_top_queries(5) : array(),
            'api_status' => $this->get_api_status(),
            'system_health' => $this->get_system_health()
        );
    }
    
    /**
     * API ステータス取得
     */
    private function get_api_status() {
        $statuses = array();
        
        foreach ($this->ai_providers as $key => $name) {
            $api_key = get_option('gaap_' . $key . '_api_key', '');
            if (!empty($api_key)) {
                $test_result = $this->ai_engine->test_api_connection($key, $api_key);
                $statuses[$key] = array(
                    'name' => $name,
                    'status' => $test_result['success'] ? 'active' : 'error',
                    'response_time' => $test_result['response_time'] ?? 0
                );
            } else {
                $statuses[$key] = array(
                    'name' => $name,
                    'status' => 'not_configured',
                    'response_time' => 0
                );
            }
        }
        
        return $statuses;
    }
    
    /**
     * システムヘルス取得
     */
    private function get_system_health() {
        return array(
            'php_version' => phpversion(),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => GAAP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'cache_status' => wp_cache_get_last_changed(GAAP_CACHE_GROUP) ? 'active' : 'inactive',
            'database_size' => $this->get_database_size()
        );
    }
    
    /**
     * データベースサイズ取得
     */
    private function get_database_size() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'gaap_conversations',
            $wpdb->prefix . 'gaap_interactions',
            $wpdb->prefix . 'gaap_analytics',
            $wpdb->prefix . 'gaap_logs'
        );
        
        $total_size = 0;
        foreach ($tables as $table) {
            $result = $wpdb->get_row("SHOW TABLE STATUS LIKE '$table'");
            if ($result) {
                $total_size += $result->Data_length + $result->Index_length;
            }
        }
        
        return $total_size;
    }
    
    /**
     * 設定通知レンダリング
     */
    private function render_configuration_notice() {
        $admin_url = admin_url('admin.php?page=gaap-ai-settings');
        
        ob_start();
        ?>
        <div class="gaap-notice gaap-notice-warning">
            <div class="gaap-notice-icon">⚙️</div>
            <div class="gaap-notice-content">
                <h3><?php _e('AI設定が必要です', 'grant-ai-assistant-pro'); ?></h3>
                <p><?php _e('チャット機能を使用するには、まずAI APIの設定を完了してください。', 'grant-ai-assistant-pro'); ?></p>
                <?php if (current_user_can('manage_options')): ?>
                    <a href="<?php echo esc_url($admin_url); ?>" class="gaap-button gaap-button-primary">
                        <?php _e('設定画面へ', 'grant-ai-assistant-pro'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * レート制限通知レンダリング
     */
    private function render_rate_limit_notice() {
        ob_start();
        ?>
        <div class="gaap-notice gaap-notice-error">
            <div class="gaap-notice-icon">🚫</div>
            <div class="gaap-notice-content">
                <h3><?php _e('利用制限に達しました', 'grant-ai-assistant-pro'); ?></h3>
                <p><?php _e('一時的に利用制限に達しています。しばらくお待ちください。', 'grant-ai-assistant-pro'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * AIエンジンクラス - マルチプロバイダー対応
 */
class GAAP_AI_Engine {
    
    private $cache_manager;
    private $logger;
    private $providers;
    
    public function __construct($cache_manager, $logger) {
        $this->cache_manager = $cache_manager;
        $this->logger = $logger;
        
        $this->providers = array(
            'openai' => new GAAP_OpenAI_Provider(),
            'claude' => new GAAP_Claude_Provider(),
            'gemini' => new GAAP_Gemini_Provider()
        );
    }
    
    public function process_message($message, $conversation_id = '') {
        $start_time = microtime(true);
        
        // キャッシュチェック
        $cache_key = md5($message . $conversation_id);
        $cached_response = $this->cache_manager->get($cache_key);
        
        if ($cached_response) {
            return $cached_response;
        }
        
        // プロバイダー選択
        $provider_key = get_option('gaap_ai_provider', 'openai');
        $provider = $this->providers[$provider_key];
        
        try {
            // AI処理
            $response = $provider->generate_response($message, $conversation_id);
            
            // 助成金検索
            $grants = $this->search_grants($response['intent'], $response['keywords']);
            
            // レスポンス構築
            $final_response = array(
                'message' => $response['message'],
                'grants' => $grants,
                'suggestions' => $response['suggestions'] ?? array(),
                'confidence' => $response['confidence'] ?? 0,
                'intent' => $response['intent'] ?? 'general',
                'conversation_id' => $conversation_id ?: wp_generate_password(16, false),
                'processing_time' => round((microtime(true) - $start_time) * 1000, 2)
            );
            
            // キャッシュ保存
            $this->cache_manager->set($cache_key, $final_response, GAAP_CACHE_DURATION);
            
            return $final_response;
            
        } catch (Exception $e) {
            $this->logger->log('ai_engine_error', array(
                'provider' => $provider_key,
                'error' => $e->getMessage()
            ), 'error');
            
            throw $e;
        }
    }
    
    public function test_api_connection($provider_key, $api_key) {
        if (!isset($this->providers[$provider_key])) {
            return array(
                'success' => false,
                'message' => __('サポートされていないプロバイダーです', 'grant-ai-assistant-pro')
            );
        }
        
        $provider = $this->providers[$provider_key];
        return $provider->test_connection($api_key);
    }
    
    private function search_grants($intent, $keywords) {
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => get_option('gaap_max_results', 8),
            'meta_query' => array(),
            'tax_query' => array()
        );
        
        // キーワード検索
        if (!empty($keywords)) {
            $args['s'] = implode(' ', $keywords);
        }
        
        // インテント別フィルタリング
        switch ($intent) {
            case 'startup':
                $args['meta_query'][] = array(
                    'key' => 'target_business_stage',
                    'value' => 'startup',
                    'compare' => 'LIKE'
                );
                break;
                
            case 'research':
                $args['tax_query'][] = array(
                    'taxonomy' => 'grant_category',
                    'field' => 'slug',
                    'terms' => 'research-development'
                );
                break;
                
            case 'equipment':
                $args['tax_query'][] = array(
                    'taxonomy' => 'grant_category',
                    'field' => 'slug',
                    'terms' => 'equipment-investment'
                );
                break;
        }
        
        $query = new WP_Query($args);
        $grants_html = '';
        
        if ($query->have_posts()) {
            $grants_html = '<div class="gaap-grants-container">';
            
            while ($query->have_posts()) {
                $query->the_post();
                
                // Grant Insight Perfect テーマの関数を使用
                if (function_exists('gi_render_card')) {
                    $grants_html .= gi_render_card(get_the_ID());
                } else {
                    // フォールバック表示
                    $grants_html .= $this->render_grant_fallback(get_the_ID());
                }
            }
            
            $grants_html .= '</div>';
            wp_reset_postdata();
        }
        
        return $grants_html;
    }
    
    private function render_grant_fallback($post_id) {
        $title = get_the_title($post_id);
        $excerpt = get_the_excerpt($post_id);
        $permalink = get_permalink($post_id);
        $amount = get_post_meta($post_id, 'grant_amount', true);
        $deadline = get_post_meta($post_id, 'application_deadline', true);
        
        ob_start();
        ?>
        <div class="gaap-grant-card">
            <h4 class="gaap-grant-title">
                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
            </h4>
            <?php if ($amount): ?>
                <div class="gaap-grant-amount">💰 <?php echo esc_html($amount); ?></div>
            <?php endif; ?>
            <?php if ($deadline): ?>
                <div class="gaap-grant-deadline">📅 締切: <?php echo esc_html($deadline); ?></div>
            <?php endif; ?>
            <p class="gaap-grant-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 30)); ?></p>
            <a href="<?php echo esc_url($permalink); ?>" class="gaap-grant-link">詳細を見る →</a>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * OpenAI プロバイダー
 */
class GAAP_OpenAI_Provider {
    
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    
    public function generate_response($message, $conversation_id) {
        $api_key = get_option('gaap_openai_api_key', '');
        
        if (empty($api_key)) {
            throw new Exception(__('OpenAI APIキーが設定されていません', 'grant-ai-assistant-pro'));
        }
        
        $system_prompt = $this->build_system_prompt();
        
        $data = array(
            'model' => 'gpt-4-turbo-preview',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'temperature' => 0.7,
            'max_tokens' => 1500,
            'response_format' => array('type' => 'json_object')
        );
        
        $response = wp_remote_post($this->api_url, array(
            'timeout' => GAAP_API_TIMEOUT,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data)
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if (!isset($response_data['choices'][0]['message']['content'])) {
            throw new Exception(__('OpenAI APIからの不正な応答', 'grant-ai-assistant-pro'));
        }
        
        $ai_response = json_decode($response_data['choices'][0]['message']['content'], true);
        
        return array(
            'message' => $ai_response['message'] ?? $message,
            'intent' => $ai_response['intent'] ?? 'general',
            'keywords' => $ai_response['keywords'] ?? array(),
            'suggestions' => $ai_response['suggestions'] ?? array(),
            'confidence' => $ai_response['confidence'] ?? 0.5
        );
    }
    
    public function test_connection($api_key) {
        $start_time = microtime(true);
        
        $data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Test connection'
                )
            ),
            'max_tokens' => 10
        );
        
        $response = wp_remote_post($this->api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data)
        ));
        
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
                'response_time' => $response_time
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'message' => __('OpenAI API接続成功', 'grant-ai-assistant-pro'),
                'response_time' => $response_time
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('API エラー: %d', 'grant-ai-assistant-pro'), $response_code),
                'response_time' => $response_time
            );
        }
    }
    
    private function build_system_prompt() {
        return <<<EOT
あなたは日本の助成金制度の専門エキスパートAIコンシェルジュです。

【あなたの役割】
- ユーザーの事業内容や目的に最適な助成金を提案
- 複雑な申請要件をわかりやすく解説
- 成功確率の高い申請戦略をアドバイス

【回答形式】
必ず以下のJSON形式で回答してください：

{
  "message": "ユーザーへの親しみやすい回答（300文字以内）",
  "intent": "ユーザーの意図カテゴリ（startup/research/equipment/employment/digital/regional/general）",
  "keywords": ["検索に使用するキーワード配列（最大5個）"],
  "suggestions": ["関連する質問提案（3個）"],
  "confidence": 0.95
}

【重要事項】
- 回答は必ずJSON形式のみ
- 親しみやすい敬語で対応
- 具体的で実用的なアドバイス
- 最新の制度情報に基づく提案
EOT;
    }
}

/**
 * Claude プロバイダー（プレースホルダー）
 */
class GAAP_Claude_Provider {
    
    public function generate_response($message, $conversation_id) {
        throw new Exception(__('Claude API は次のバージョンで対応予定です', 'grant-ai-assistant-pro'));
    }
    
    public function test_connection($api_key) {
        return array(
            'success' => false,
            'message' => __('Claude API は次のバージョンで対応予定です', 'grant-ai-assistant-pro'),
            'response_time' => 0
        );
    }
}

/**
 * Gemini プロバイダー（プレースホルダー）
 */
class GAAP_Gemini_Provider {
    
    public function generate_response($message, $conversation_id) {
        throw new Exception(__('Gemini API は次のバージョンで対応予定です', 'grant-ai-assistant-pro'));
    }
    
    public function test_connection($api_key) {
        return array(
            'success' => false,
            'message' => __('Gemini API は次のバージョンで対応予定です', 'grant-ai-assistant-pro'),
            'response_time' => 0
        );
    }
}

/**
 * キャッシュ管理クラス
 */
class GAAP_Cache_Manager {
    
    private $group = GAAP_CACHE_GROUP;
    
    public function get($key) {
        return wp_cache_get($key, $this->group);
    }
    
    public function set($key, $data, $expiration = GAAP_CACHE_DURATION) {
        return wp_cache_set($key, $data, $this->group, $expiration);
    }
    
    public function delete($key) {
        return wp_cache_delete($key, $this->group);
    }
    
    public function flush() {
        return wp_cache_flush_group($this->group);
    }
    
    public function get_stats() {
        return array(
            'hits' => wp_cache_get('cache_hits', $this->group) ?: 0,
            'misses' => wp_cache_get('cache_misses', $this->group) ?: 0,
            'size' => $this->get_cache_size()
        );
    }
    
    private function get_cache_size() {
        // キャッシュサイズ推定（概算）
        return 0;
    }
}

/**
 * ログ管理クラス
 */
class GAAP_Logger {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gaap_logs';
    }
    
    public function log($type, $data, $level = 'info') {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'log_type' => $type,
                'log_level' => $level,
                'log_data' => json_encode($data),
                'ip_address' => $this->get_client_ip(),
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
    }
    
    public function get_recent_logs($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }
    
    public function cleanup_old_logs($days = 30) {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
    
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * レート制限クラス
 */
class GAAP_Rate_Limiter {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gaap_rate_limits';
    }
    
    public static function init_rate_limit_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gaap_rate_limits';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            requests_count int(11) DEFAULT 1,
            window_start datetime NOT NULL,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY window_start (window_start)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function is_rate_limited() {
        $ip = $this->get_client_ip();
        $window_start = date('Y-m-d H:00:00'); // 1時間ウィンドウ
        
        global $wpdb;
        
        $current_requests = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT requests_count FROM {$this->table_name} 
                WHERE ip_address = %s AND window_start = %s",
                $ip, $window_start
            )
        );
        
        if ($current_requests === null) {
            // 新しいエントリを作成
            $wpdb->insert(
                $this->table_name,
                array(
                    'ip_address' => $ip,
                    'requests_count' => 1,
                    'window_start' => $window_start
                ),
                array('%s', '%d', '%s')
            );
            return false;
        }
        
        if ($current_requests >= GAAP_RATE_LIMIT) {
            return true;
        }
        
        // カウントを増加
        $wpdb->update(
            $this->table_name,
            array('requests_count' => $current_requests + 1),
            array(
                'ip_address' => $ip,
                'window_start' => $window_start
            ),
            array('%d'),
            array('%s', '%s')
        );
        
        return false;
    }
    
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * セキュリティ管理クラス
 */
class GAAP_Security_Manager {
    
    public function sanitize_api_key($api_key) {
        return sanitize_text_field(trim($api_key));
    }
    
    public function log_login_attempt($user_login) {
        // ログイン成功をログ記録
    }
    
    public function log_failed_login($username) {
        // ログイン失敗をログ記録
    }
}

/**
 * 分析エンジンクラス
 */
class GAAP_Analytics_Engine {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gaap_analytics';
    }
    
    public function record_interaction($message, $response) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'session_id' => $this->get_session_id(),
                'message_text' => $message,
                'intent' => $response['intent'] ?? 'general',
                'confidence' => $response['confidence'] ?? 0,
                'processing_time' => $response['processing_time'] ?? 0,
                'grants_found' => $this->count_grants_in_response($response['grants'] ?? ''),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s')
        );
    }
    
    public function get_total_conversations() {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name}"
        );
    }
    
    public function get_today_interactions() {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE DATE(created_at) = CURDATE()"
        );
    }
    
    public function get_average_satisfaction() {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT AVG(confidence) FROM {$this->table_name} 
            WHERE confidence > 0"
        ) ?: 0;
    }
    
    public function get_top_queries($limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT intent, COUNT(*) as count 
                FROM {$this->table_name} 
                GROUP BY intent 
                ORDER BY count DESC 
                LIMIT %d",
                $limit
            )
        );
    }
    
    public function get_analytics_data() {
        return array(
            'interactions_by_day' => $this->get_interactions_by_day(),
            'intent_distribution' => $this->get_intent_distribution(),
            'average_confidence_trend' => $this->get_confidence_trend(),
            'popular_keywords' => $this->get_popular_keywords()
        );
    }
    
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }
    
    private function count_grants_in_response($grants_html) {
        return substr_count($grants_html, 'gaap-grant-card');
    }
    
    private function get_interactions_by_day() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as interactions 
            FROM {$this->table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at) 
            ORDER BY date ASC"
        );
    }
    
    private function get_intent_distribution() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT intent, COUNT(*) as count 
            FROM {$this->table_name} 
            GROUP BY intent 
            ORDER BY count DESC"
        );
    }
    
    private function get_confidence_trend() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT DATE(created_at) as date, AVG(confidence) as avg_confidence 
            FROM {$this->table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND confidence > 0
            GROUP BY DATE(created_at) 
            ORDER BY date ASC"
        );
    }
    
    private function get_popular_keywords() {
        // 簡易実装 - より高度な自然言語処理は将来のバージョンで
        return array();
    }
}

/**
 * データベース管理クラス
 */
class GAAP_Database_Manager {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // ログテーブル
        $logs_table = $wpdb->prefix . 'gaap_logs';
        $logs_sql = "CREATE TABLE IF NOT EXISTS $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_type varchar(50) NOT NULL,
            log_level varchar(20) DEFAULT 'info',
            log_data longtext,
            ip_address varchar(45),
            user_id bigint(20) DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY log_type (log_type),
            KEY log_level (log_level),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // 分析テーブル
        $analytics_table = $wpdb->prefix . 'gaap_analytics';
        $analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            message_text text,
            intent varchar(50),
            confidence decimal(3,2) DEFAULT 0.00,
            processing_time decimal(8,2) DEFAULT 0.00,
            grants_found int(11) DEFAULT 0,
            user_agent text,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY intent (intent),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // 会話テーブル
        $conversations_table = $wpdb->prefix . 'gaap_conversations';
        $conversations_sql = "CREATE TABLE IF NOT EXISTS $conversations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id varchar(100) NOT NULL,
            user_id bigint(20) DEFAULT 0,
            started_at datetime NOT NULL,
            last_activity datetime NOT NULL,
            total_messages int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY conversation_id (conversation_id),
            KEY user_id (user_id),
            KEY started_at (started_at)
        ) $charset_collate;";
        
        // インタラクションテーブル
        $interactions_table = $wpdb->prefix . 'gaap_interactions';
        $interactions_sql = "CREATE TABLE IF NOT EXISTS $interactions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id varchar(100) NOT NULL,
            message_type enum('user','ai') NOT NULL,
            message_content text,
            ai_response_data longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($logs_sql);
        dbDelta($analytics_sql);
        dbDelta($conversations_sql);
        dbDelta($interactions_sql);
        
        // レート制限テーブルも作成
        GAAP_Rate_Limiter::init_rate_limit_table();
    }
    
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'gaap_logs',
            $wpdb->prefix . 'gaap_analytics',
            $wpdb->prefix . 'gaap_conversations',
            $wpdb->prefix . 'gaap_interactions',
            $wpdb->prefix . 'gaap_rate_limits'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
}

// フック登録
register_activation_hook(__FILE__, 'gaap_activation_hook');
register_deactivation_hook(__FILE__, 'gaap_deactivation_hook');
register_uninstall_hook(__FILE__, 'gaap_uninstall_hook');

// プラグイン初期化
add_action('plugins_loaded', function() {
    if (class_exists('Grant_AI_Assistant_Pro')) {
        Grant_AI_Assistant_Pro::get_instance();
    }
});

// 管理画面でのみ実行する追加処理
if (is_admin()) {
    add_action('admin_init', function() {
        // バージョンアップデート処理
        $current_version = get_option('gaap_plugin_version', '1.0.0');
        if (version_compare($current_version, GAAP_VERSION, '<')) {
            // アップデート処理
            update_option('gaap_plugin_version', GAAP_VERSION);
        }
    });
}