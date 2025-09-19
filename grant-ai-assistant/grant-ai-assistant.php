<?php
/*
Plugin Name: Grant AI Assistant Pro
Description: 次世代AI対話型助成金検索システム - エンタープライズグレード統合プラットフォーム v2.1
Version: 2.1.0
Author: Grant Insight Team
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 8.0
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
define('GAAP_VERSION', '2.1.0');
define('GAAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GAAP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GAAP_PLUGIN_FILE', __FILE__);
define('GAAP_CACHE_GROUP', 'grant_ai_assistant_pro');
define('GAAP_CACHE_DURATION', 30 * MINUTE_IN_SECONDS);
define('GAAP_RATE_LIMIT', 120); // 1時間あたり120リクエスト
define('GAAP_MAX_RETRIES', 5);
define('GAAP_API_TIMEOUT', 45);
define('GAAP_ML_CONFIDENCE_THRESHOLD', 0.75);
define('GAAP_SECURITY_NONCE', 'gaap_security_nonce');

/**
 * プラグインアクティベーション処理
 */
function gaap_activation_hook() {
    try {
        // データベーステーブル作成
        GAAP_Database_Manager::create_tables();
        
        // デフォルト設定
        add_option('gaap_enable_chat', true);
        add_option('gaap_max_results', 10);
        add_option('gaap_ai_provider', 'openai');
        add_option('gaap_enable_analytics', true);
        add_option('gaap_enable_ab_test', false);
        add_option('gaap_cache_duration', GAAP_CACHE_DURATION);
        add_option('gaap_ml_confidence_threshold', GAAP_ML_CONFIDENCE_THRESHOLD);
        add_option('gaap_rate_limit_enabled', true);
        add_option('gaap_auto_error_recovery', true);
        add_option('gaap_security_level', 'high');
        
        // キャッシュグループ登録
        wp_cache_add_global_groups(array(GAAP_CACHE_GROUP));
        
        // スケジュール設定
        if (!wp_next_scheduled('gaap_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'gaap_cleanup_logs');
        }
        
        if (!wp_next_scheduled('gaap_performance_check')) {
            wp_schedule_event(time(), 'hourly', 'gaap_performance_check');
        }
        
    } catch (Exception $e) {
        error_log('GAAP Activation Error: ' . $e->getMessage());
        wp_die('プラグインのアクティベーションに失敗しました: ' . $e->getMessage());
    }
}

/**
 * プラグインディアクティベーション処理
 */
function gaap_deactivation_hook() {
    wp_clear_scheduled_hook('gaap_cleanup_logs');
    wp_clear_scheduled_hook('gaap_performance_check');
    wp_cache_flush_group(GAAP_CACHE_GROUP);
}

register_activation_hook(__FILE__, 'gaap_activation_hook');
register_deactivation_hook(__FILE__, 'gaap_deactivation_hook');

/**
 * Grant AI Assistant Pro メインクラス - エンタープライズグレード
 */
class Grant_AI_Assistant_Pro {
    
    private static $instance = null;
    private $ai_engine = null;
    private $cache_manager = null;
    private $logger = null;
    private $security_manager = null;
    private $analytics = null;
    private $performance_monitor = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
        $this->init_admin();
        $this->init_ajax();
        $this->init_error_handling();
    }
    
    private function init_components() {
        try {
            $this->cache_manager = new GAAP_Advanced_Cache_Manager();
            $this->logger = new GAAP_Enhanced_Logger();
            $this->security_manager = new GAAP_Security_Manager();
            $this->ai_engine = new GAAP_AI_Engine($this->cache_manager, $this->logger, $this->security_manager);
            $this->analytics = new GAAP_Advanced_Analytics($this->logger);
            $this->performance_monitor = new GAAP_Performance_Monitor($this->logger);
            
            $this->logger->info('GAAP Components initialized successfully');
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize GAAP components', ['error' => $e->getMessage()]);
            // フォールバック処理
            $this->init_fallback_components();
        }
    }
    
    private function init_fallback_components() {
        $this->cache_manager = new GAAP_Fallback_Cache();
        $this->logger = new GAAP_Simple_Logger();
        $this->security_manager = new GAAP_Basic_Security();
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>Grant AI Assistantが縮小モードで動作しています。管理者にお問い合わせください。</p></div>';
        });
    }
    
    private function init_error_handling() {
        set_error_handler(array($this, 'handle_php_errors'));
        register_shutdown_function(array($this, 'handle_fatal_errors'));
    }
    
    public function handle_php_errors($severity, $message, $file, $line) {
        if (strpos($file, 'grant-ai-assistant') !== false) {
            $this->logger->error('PHP Error in GAAP', [
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line
            ]);
            
            // 自動復旧処理
            if (get_option('gaap_auto_error_recovery', true)) {
                $this->attempt_error_recovery($severity, $message);
            }
        }
        return false; // PHP の標準エラーハンドリングも実行
    }
    
    public function handle_fatal_errors() {
        $error = error_get_last();
        if ($error && strpos($error['file'], 'grant-ai-assistant') !== false) {
            $this->logger->error('Fatal Error in GAAP', $error);
            
            // エマージェンシーモードの活性化
            update_option('gaap_emergency_mode', true);
            wp_cache_set('gaap_emergency_mode', true, GAAP_CACHE_GROUP, 300);
        }
    }
    
    private function attempt_error_recovery($severity, $message) {
        try {
            // API接続エラーの場合
            if (strpos($message, 'API') !== false) {
                wp_cache_delete('gaap_api_status', GAAP_CACHE_GROUP);
                $this->logger->info('Attempting API error recovery');
            }
            
            // キャッシュエラーの場合
            if (strpos($message, 'cache') !== false) {
                wp_cache_flush_group(GAAP_CACHE_GROUP);
                $this->logger->info('Cache flushed for error recovery');
            }
            
            // データベースエラーの場合
            if (strpos($message, 'database') !== false || strpos($message, 'SQL') !== false) {
                $this->logger->info('Attempting database error recovery');
                // データベース接続の再初期化
                wp_cache_delete('gaap_db_status', GAAP_CACHE_GROUP);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Error recovery failed', ['error' => $e->getMessage()]);
        }
    }
    
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_shortcode('grant_ai_assistant', array($this, 'render_chat_interface'));
        
        // パフォーマンス監視
        add_action('gaap_performance_check', array($this->performance_monitor, 'run_performance_check'));
        add_action('gaap_cleanup_logs', array($this, 'cleanup_old_logs'));
        
        // セキュリティフック
        add_action('wp_login_failed', array($this->security_manager, 'log_failed_login'));
        add_filter('authenticate', array($this->security_manager, 'check_brute_force'), 30, 3);
    }
    
    private function init_admin() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_pages'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_notices', array($this, 'admin_notices'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        }
    }
    
    private function init_ajax() {
        $ajax_actions = array(
            'gaap_chat_message' => array($this, 'handle_chat_message'),
            'gaap_test_api' => array($this, 'test_api_connection'),
            'gaap_clear_cache' => array($this, 'clear_cache'),
            'gaap_get_analytics' => array($this, 'get_analytics_data'),
            'gaap_export_logs' => array($this, 'export_logs'),
            'gaap_system_check' => array($this, 'run_system_check'),
            'gaap_emergency_reset' => array($this, 'emergency_reset')
        );
        
        foreach ($ajax_actions as $action => $callback) {
            add_action('wp_ajax_' . $action, $callback);
            if (strpos($action, 'chat') !== false) {
                add_action('wp_ajax_nopriv_' . $action, $callback);
            }
        }
    }
    
    public function add_admin_pages() {
        $capability = 'manage_options';
        
        add_menu_page(
            'Grant AI Assistant Pro',
            'Grant AI Assistant',
            $capability,
            'grant-ai-assistant',
            array($this, 'render_dashboard_page'),
            'dashicons-admin-comments',
            30
        );
        
        add_submenu_page(
            'grant-ai-assistant',
            'ダッシュボード',
            'ダッシュボード',
            $capability,
            'grant-ai-assistant',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'grant-ai-assistant',
            'AI設定',
            'AI設定',
            $capability,
            'grant-ai-settings',
            array($this, 'render_ai_settings_page')
        );
        
        add_submenu_page(
            'grant-ai-assistant',
            '分析・レポート',
            '分析・レポート',
            $capability,
            'grant-ai-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'grant-ai-assistant',
            'システムログ',
            'システムログ',
            $capability,
            'grant-ai-logs',
            array($this, 'render_logs_page')
        );
    }
    
    public function render_dashboard_page() {
        if (!$this->security_manager->verify_admin_access()) {
            wp_die('アクセス権限がありません。');
        }
        
        try {
            $stats = $this->get_dashboard_stats();
            $template_path = GAAP_PLUGIN_PATH . 'templates/admin/dashboard.php';
            
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                $this->render_fallback_admin_page('dashboard', compact('stats'));
            }
        } catch (Exception $e) {
            $this->logger->error('Dashboard rendering error', ['error' => $e->getMessage()]);
            echo '<div class="notice notice-error"><p>ダッシュボードの読み込みに失敗しました。システム管理者にお問い合わせください。</p></div>';
        }
    }
    
    public function render_ai_settings_page() {
        if (!$this->security_manager->verify_admin_access()) {
            wp_die('アクセス権限がありません。');
        }
        
        try {
            $providers = $this->ai_engine->get_available_providers();
            $current_settings = $this->ai_engine->get_current_settings();
            
            $template_path = GAAP_PLUGIN_PATH . 'templates/admin/ai-settings.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                $this->render_fallback_admin_page('ai-settings', compact('providers', 'current_settings'));
            }
        } catch (Exception $e) {
            $this->logger->error('AI Settings rendering error', ['error' => $e->getMessage()]);
            echo '<div class="notice notice-error"><p>AI設定の読み込みに失敗しました。</p></div>';
        }
    }
    
    public function render_analytics_page() {
        if (!$this->security_manager->verify_admin_access()) {
            wp_die('アクセス権限がありません。');
        }
        
        try {
            $analytics_data = $this->analytics->get_comprehensive_analytics();
            
            $template_path = GAAP_PLUGIN_PATH . 'templates/admin/analytics.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                $this->render_fallback_admin_page('analytics', compact('analytics_data'));
            }
        } catch (Exception $e) {
            $this->logger->error('Analytics rendering error', ['error' => $e->getMessage()]);
            echo '<div class="notice notice-error"><p>分析データの読み込みに失敗しました。</p></div>';
        }
    }
    
    public function render_logs_page() {
        if (!$this->security_manager->verify_admin_access()) {
            wp_die('アクセス権限がありません。');
        }
        
        try {
            $logs = $this->logger->get_recent_logs(100);
            
            $template_path = GAAP_PLUGIN_PATH . 'templates/admin/logs.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                $this->render_fallback_admin_page('logs', compact('logs'));
            }
        } catch (Exception $e) {
            $this->logger->error('Logs rendering error', ['error' => $e->getMessage()]);
            $logs = array();
            echo '<div class="notice notice-error"><p>ログの読み込みに失敗しました。フォールバックモードで表示します。</p></div>';
            $this->render_fallback_admin_page('logs', compact('logs'));
        }
    }
    
    private function render_fallback_admin_page($page_type, $data = array()) {
        echo '<div class="wrap">';
        echo '<h1>Grant AI Assistant Pro - ' . ucfirst($page_type) . '</h1>';
        echo '<div class="notice notice-warning"><p>テンプレートファイルが見つかりません。フォールバックモードで表示しています。</p></div>';
        
        switch ($page_type) {
            case 'dashboard':
                $this->render_fallback_dashboard($data);
                break;
            case 'ai-settings':
                $this->render_fallback_ai_settings($data);
                break;
            case 'analytics':
                $this->render_fallback_analytics($data);
                break;
            case 'logs':
                $this->render_fallback_logs($data);
                break;
        }
        
        echo '</div>';
    }
    
    private function render_fallback_dashboard($data) {
        $stats = $data['stats'] ?? $this->get_fallback_stats();
        
        echo '<div class="gaap-dashboard-fallback">';
        echo '<h2>システム状況</h2>';
        echo '<div class="gaap-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">';
        
        foreach ($stats as $key => $value) {
            echo '<div class="gaap-stat-card" style="background: white; padding: 20px; border-left: 4px solid #0073aa; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
            echo '<h3 style="margin: 0 0 10px 0; color: #555;">' . esc_html($key) . '</h3>';
            echo '<p style="font-size: 24px; font-weight: bold; margin: 0; color: #0073aa;">' . esc_html($value) . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '<p><a href="?page=grant-ai-settings" class="button button-primary">AI設定</a> ';
        echo '<a href="?page=grant-ai-analytics" class="button">分析レポート</a> ';
        echo '<a href="?page=grant-ai-logs" class="button">システムログ</a></p>';
        echo '</div>';
    }
    
    private function render_fallback_ai_settings($data) {
        echo '<form method="post" action="options.php">';
        settings_fields('gaap_ai_settings');
        echo '<table class="form-table">';
        echo '<tr><th scope="row">AIプロバイダー</th>';
        echo '<td><select name="gaap_ai_provider">';
        echo '<option value="openai"' . selected(get_option('gaap_ai_provider'), 'openai', false) . '>OpenAI</option>';
        echo '<option value="claude"' . selected(get_option('gaap_ai_provider'), 'claude', false) . '>Claude</option>';
        echo '<option value="gemini"' . selected(get_option('gaap_ai_provider'), 'gemini', false) . '>Gemini</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row">API キー</th>';
        echo '<td><input type="password" name="gaap_api_key" value="' . str_repeat('*', 20) . '" class="regular-text" /></td></tr>';
        echo '</table>';
        submit_button('設定を保存');
        echo '</form>';
    }
    
    private function render_fallback_analytics($data) {
        echo '<h2>分析データ</h2>';
        echo '<p>分析機能は現在縮小モードで動作しています。</p>';
        echo '<div class="gaap-analytics-fallback">';
        echo '<h3>基本統計</h3>';
        echo '<ul>';
        echo '<li>総チャット数: ' . get_option('gaap_total_chats', 0) . '</li>';
        echo '<li>本日のチャット数: ' . get_transient('gaap_daily_chats') ?: 0 . '</li>';
        echo '<li>アクティブユーザー数: ' . get_transient('gaap_active_users') ?: 0 . '</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    private function render_fallback_logs($data) {
        $logs = $data['logs'] ?? array();
        
        echo '<h2>システムログ</h2>';
        if (empty($logs)) {
            echo '<p>表示できるログがありません。</p>';
            return;
        }
        
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>時刻</th><th>レベル</th><th>メッセージ</th></tr></thead><tbody>';
        
        foreach (array_slice($logs, 0, 20) as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log['timestamp'] ?? date('Y-m-d H:i:s')) . '</td>';
            echo '<td>' . esc_html($log['level'] ?? 'INFO') . '</td>';
            echo '<td>' . esc_html($log['message'] ?? 'ログエントリ') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function get_fallback_stats() {
        return array(
            'システム状態' => get_option('gaap_emergency_mode') ? '縮小モード' : '正常',
            '総チャット数' => get_option('gaap_total_chats', 0),
            '本日のエラー' => get_transient('gaap_daily_errors') ?: 0,
            'キャッシュ使用率' => '不明'
        );
    }
    
    public function handle_chat_message() {
        try {
            if (!$this->security_manager->verify_nonce()) {
                wp_send_json_error('セキュリティ認証に失敗しました。', 403);
                return;
            }
            
            if (!$this->security_manager->check_rate_limit()) {
                wp_send_json_error('リクエスト制限に達しました。しばらくお待ちください。', 429);
                return;
            }
            
            $message = sanitize_text_field($_POST['message'] ?? '');
            if (empty($message)) {
                wp_send_json_error('メッセージが空です。');
                return;
            }
            
            // パフォーマンス監視開始
            $start_time = microtime(true);
            
            // AI応答を取得
            $response = $this->ai_engine->process_chat_message($message);
            
            // パフォーマンスデータ記録
            $processing_time = microtime(true) - $start_time;
            $this->performance_monitor->record_chat_performance($processing_time, strlen($message));
            
            // 分析データ記録
            $this->analytics->record_chat_interaction($message, $response);
            
            wp_send_json_success($response);
            
        } catch (GAAP_API_Exception $e) {
            $this->logger->error('API Error in chat', ['error' => $e->getMessage(), 'message' => $message ?? '']);
            wp_send_json_error('AI サービスに一時的な問題が発生しています。しばらくしてからお試しください。');
        } catch (GAAP_Rate_Limit_Exception $e) {
            wp_send_json_error('アクセス制限に達しました。' . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('Unexpected error in chat', ['error' => $e->getMessage()]);
            wp_send_json_error('予期しないエラーが発生しました。');
        }
    }
    
    public function test_api_connection() {
        if (!current_user_can('manage_options') || !$this->security_manager->verify_nonce()) {
            wp_send_json_error('権限がありません。');
            return;
        }
        
        try {
            $provider = sanitize_text_field($_POST['provider'] ?? get_option('gaap_ai_provider'));
            $api_key = sanitize_text_field($_POST['api_key'] ?? '');
            
            $result = $this->ai_engine->test_provider_connection($provider, $api_key);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['message']);
            }
        } catch (Exception $e) {
            $this->logger->error('API test failed', ['error' => $e->getMessage()]);
            wp_send_json_error('API接続テストに失敗しました: ' . $e->getMessage());
        }
    }
    
    public function emergency_reset() {
        if (!current_user_can('manage_options') || !$this->security_manager->verify_nonce()) {
            wp_send_json_error('権限がありません。');
            return;
        }
        
        try {
            // エマージェンシーモード解除
            delete_option('gaap_emergency_mode');
            wp_cache_delete('gaap_emergency_mode', GAAP_CACHE_GROUP);
            
            // キャッシュクリア
            wp_cache_flush_group(GAAP_CACHE_GROUP);
            
            // コンポーネント再初期化
            $this->init_components();
            
            $this->logger->info('Emergency reset completed');
            wp_send_json_success('エマージェンシーリセットが完了しました。');
            
        } catch (Exception $e) {
            $this->logger->error('Emergency reset failed', ['error' => $e->getMessage()]);
            wp_send_json_error('リセット処理に失敗しました。');
        }
    }
    
    private function get_dashboard_stats() {
        $cache_key = 'gaap_dashboard_stats';
        $stats = wp_cache_get($cache_key, GAAP_CACHE_GROUP);
        
        if (false === $stats) {
            try {
                $stats = array(
                    'システム状態' => $this->get_system_status(),
                    '総チャット数' => $this->analytics->get_total_chats(),
                    '本日のチャット数' => $this->analytics->get_daily_chats(),
                    'アクティブユーザー数' => $this->analytics->get_active_users(),
                    'API状態' => $this->ai_engine->get_api_health_status(),
                    'キャッシュヒット率' => $this->cache_manager->get_hit_ratio() . '%',
                    '平均応答時間' => $this->performance_monitor->get_average_response_time() . 'ms',
                    'エラー率' => $this->logger->get_error_rate() . '%'
                );
                
                wp_cache_set($cache_key, $stats, GAAP_CACHE_GROUP, 300); // 5分キャッシュ
            } catch (Exception $e) {
                $this->logger->error('Failed to get dashboard stats', ['error' => $e->getMessage()]);
                return $this->get_fallback_stats();
            }
        }
        
        return $stats;
    }
    
    private function get_system_status() {
        if (get_option('gaap_emergency_mode')) {
            return '<span style="color: #dc3232;">エマージェンシー</span>';
        }
        
        $health_checks = array(
            'api' => $this->ai_engine->is_healthy(),
            'cache' => $this->cache_manager->is_healthy(),
            'database' => $this->check_database_health()
        );
        
        $healthy_count = array_sum($health_checks);
        $total_checks = count($health_checks);
        
        if ($healthy_count === $total_checks) {
            return '<span style="color: #46b450;">正常動作</span>';
        } elseif ($healthy_count > 0) {
            return '<span style="color: #ffb900;">部分機能</span>';
        } else {
            return '<span style="color: #dc3232;">システム異常</span>';
        }
    }
    
    private function check_database_health() {
        try {
            global $wpdb;
            $wpdb->get_var("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function enqueue_frontend_assets() {
        if (is_singular() && has_shortcode(get_post()->post_content, 'grant_ai_assistant')) {
            wp_enqueue_script(
                'gaap-frontend',
                GAAP_PLUGIN_URL . 'assets/script.js',
                array('jquery'),
                GAAP_VERSION,
                true
            );
            
            wp_enqueue_style(
                'gaap-frontend',
                GAAP_PLUGIN_URL . 'assets/style.css',
                array(),
                GAAP_VERSION
            );
            
            wp_localize_script('gaap-frontend', 'gaap_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(GAAP_SECURITY_NONCE),
                'strings' => array(
                    'error_general' => 'エラーが発生しました。',
                    'error_network' => 'ネットワークエラーです。',
                    'error_rate_limit' => 'リクエスト制限に達しました。',
                    'thinking' => 'AI が考えています...',
                )
            ));
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'grant-ai-assistant') !== false) {
            wp_enqueue_script(
                'gaap-admin',
                GAAP_PLUGIN_URL . 'assets/admin-script.js',
                array('jquery', 'wp-util'),
                GAAP_VERSION,
                true
            );
            
            wp_enqueue_style(
                'gaap-admin',
                GAAP_PLUGIN_URL . 'assets/admin-style.css',
                array(),
                GAAP_VERSION
            );
            
            wp_localize_script('gaap-admin', 'gaap_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(GAAP_SECURITY_NONCE)
            ));
        }
    }
    
    public function render_chat_interface($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default',
            'height' => '400px',
            'enable_voice' => 'true'
        ), $atts);
        
        try {
            ob_start();
            $template_path = GAAP_PLUGIN_PATH . 'templates/chat-interface.php';
            
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                echo $this->get_fallback_chat_interface($atts);
            }
            
            return ob_get_clean();
            
        } catch (Exception $e) {
            $this->logger->error('Chat interface rendering error', ['error' => $e->getMessage()]);
            return '<div class="gaap-error">チャット機能の読み込みに失敗しました。</div>';
        }
    }
    
    private function get_fallback_chat_interface($atts) {
        return '
        <div class="gaap-chat-fallback" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; max-width: 600px; margin: 20px auto;">
            <h3>Grant AI Assistant</h3>
            <div id="gaap-messages" style="height: ' . esc_attr($atts['height']) . '; overflow-y: auto; border: 1px solid #eee; padding: 10px; margin: 10px 0; background: #f9f9f9;">
                <p><em>AI Assistant が準備中です...</em></p>
            </div>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="gaap-message-input" placeholder="助成金について質問してください..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <button id="gaap-send-button" style="padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">送信</button>
            </div>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">※ 現在フォールバックモードで動作しています。</p>
        </div>';
    }
    
    public function admin_notices() {
        // エマージェンシーモード通知
        if (get_option('gaap_emergency_mode')) {
            echo '<div class="notice notice-error"><p><strong>Grant AI Assistant:</strong> エマージェンシーモードで動作しています。<a href="' . admin_url('admin.php?page=grant-ai-assistant') . '">復旧処理を実行</a></p></div>';
        }
        
        // API設定警告
        if (!get_option('gaap_api_key')) {
            echo '<div class="notice notice-warning"><p><strong>Grant AI Assistant:</strong> APIキーが設定されていません。<a href="' . admin_url('admin.php?page=grant-ai-settings') . '">設定画面</a>で設定してください。</p></div>';
        }
        
        // パフォーマンス警告
        $avg_response = $this->performance_monitor->get_average_response_time();
        if ($avg_response > 5000) { // 5秒以上
            echo '<div class="notice notice-warning"><p><strong>Grant AI Assistant:</strong> 応答速度が低下しています（平均' . $avg_response . 'ms）。システム最適化をお勧めします。</p></div>';
        }
    }
    
    public function cleanup_old_logs() {
        try {
            $this->logger->cleanup_old_logs();
            $this->logger->info('Log cleanup completed');
        } catch (Exception $e) {
            error_log('GAAP Log cleanup failed: ' . $e->getMessage());
        }
    }
}

/**
 * 高性能AI エンジン
 */
class GAAP_AI_Engine {
    
    private $cache_manager;
    private $logger;
    private $security_manager;
    
    const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
    const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
    const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';
    
    public function __construct($cache_manager, $logger, $security_manager) {
        $this->cache_manager = $cache_manager;
        $this->logger = $logger;
        $this->security_manager = $security_manager;
    }
    
    public function process_chat_message($message) {
        $start_time = microtime(true);
        
        try {
            // 入力検証
            $message = $this->sanitize_message($message);
            if (empty($message)) {
                throw new InvalidArgumentException('無効なメッセージです。');
            }
            
            // キャッシュ確認
            $cache_key = 'gaap_chat_' . md5($message);
            $cached_response = $this->cache_manager->get($cache_key);
            if (false !== $cached_response) {
                $this->logger->debug('Cache hit for chat message');
                return $cached_response;
            }
            
            // AI プロバイダーに送信
            $provider = get_option('gaap_ai_provider', 'openai');
            $response = $this->send_to_ai_provider($provider, $message);
            
            // 応答を処理
            $processed_response = $this->process_ai_response($response);
            
            // キャッシュに保存
            $cache_duration = get_option('gaap_cache_duration', GAAP_CACHE_DURATION);
            $this->cache_manager->set($cache_key, $processed_response, $cache_duration);
            
            // パフォーマンス記録
            $processing_time = (microtime(true) - $start_time) * 1000;
            $this->logger->info('Chat processed successfully', [
                'processing_time' => $processing_time,
                'provider' => $provider,
                'cached' => false
            ]);
            
            return $processed_response;
            
        } catch (GAAP_API_Exception $e) {
            $this->logger->error('AI Provider API error', [
                'error' => $e->getMessage(),
                'provider' => $provider ?? 'unknown'
            ]);
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error in AI processing', [
                'error' => $e->getMessage(),
                'message' => substr($message, 0, 100)
            ]);
            throw new GAAP_Processing_Exception('AI処理中にエラーが発生しました。');
        }
    }
    
    private function send_to_ai_provider($provider, $message) {
        switch ($provider) {
            case 'openai':
                return $this->send_to_openai($message);
            case 'claude':
                return $this->send_to_claude($message);
            case 'gemini':
                return $this->send_to_gemini($message);
            default:
                throw new GAAP_API_Exception('サポートされていないAIプロバイダーです: ' . $provider);
        }
    }
    
    private function send_to_openai($message) {
        $api_key = get_option('gaap_openai_api_key');
        if (empty($api_key)) {
            throw new GAAP_API_Exception('OpenAI APIキーが設定されていません。');
        }
        
        $payload = array(
            'model' => get_option('gaap_openai_model', 'gpt-4'),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $this->get_system_prompt()
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'temperature' => floatval(get_option('gaap_temperature', 0.7)),
            'max_tokens' => intval(get_option('gaap_max_tokens', 1000)),
            'functions' => $this->get_function_definitions()
        );
        
        $response = wp_remote_post(self::OPENAI_API_URL, array(
            'timeout' => GAAP_API_TIMEOUT,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($payload)
        ));
        
        if (is_wp_error($response)) {
            throw new GAAP_API_Exception('OpenAI API接続エラー: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_msg = $data['error']['message'] ?? 'Unknown OpenAI API error';
            throw new GAAP_API_Exception('OpenAI API エラー [' . $status_code . ']: ' . $error_msg);
        }
        
        return $data;
    }
    
    private function send_to_claude($message) {
        // Claude API 統合（プレースホルダー実装）
        $api_key = get_option('gaap_claude_api_key');
        if (empty($api_key)) {
            throw new GAAP_API_Exception('Claude APIキーが設定されていません。');
        }
        
        // 実装予定: Claude API呼び出し
        throw new GAAP_API_Exception('Claude API統合は開発中です。');
    }
    
    private function send_to_gemini($message) {
        // Gemini API 統合（プレースホルダー実装）
        $api_key = get_option('gaap_gemini_api_key');
        if (empty($api_key)) {
            throw new GAAP_API_Exception('Gemini APIキーが設定されていません。');
        }
        
        // 実装予定: Gemini API呼び出し
        throw new GAAP_API_Exception('Gemini API統合は開発中です。');
    }
    
    private function process_ai_response($response) {
        try {
            $content = $response['choices'][0]['message']['content'] ?? '';
            $function_call = $response['choices'][0]['message']['function_call'] ?? null;
            
            $processed = array(
                'content' => $content,
                'type' => 'text',
                'confidence' => $this->calculate_confidence($response),
                'sources' => array(),
                'suggestions' => array()
            );
            
            // Function calling 処理
            if ($function_call) {
                $processed = array_merge($processed, $this->process_function_call($function_call));
            }
            
            // 助成金情報抽出
            $processed['grants'] = $this->extract_grant_information($content);
            
            return $processed;
            
        } catch (Exception $e) {
            $this->logger->error('Response processing error', ['error' => $e->getMessage()]);
            throw new GAAP_Processing_Exception('AI応答の処理に失敗しました。');
        }
    }
    
    private function extract_grant_information($content) {
        // AI応答から助成金情報を構造化して抽出
        $grants = array();
        
        // JSONフォーマットの助成金情報を検索
        if (preg_match('/\{[\s\S]*"grants"[\s\S]*\}/', $content, $matches)) {
            $json_data = json_decode($matches[0], true);
            if (isset($json_data['grants'])) {
                $grants = $json_data['grants'];
            }
        }
        
        return $grants;
    }
    
    private function calculate_confidence($response) {
        // AI応答の信頼度を計算
        $base_confidence = 0.7;
        
        if (isset($response['usage']['total_tokens'])) {
            $tokens = $response['usage']['total_tokens'];
            if ($tokens > 500) {
                $base_confidence += 0.1;
            }
        }
        
        return min(1.0, $base_confidence);
    }
    
    private function get_system_prompt() {
        return '
あなたは助成金の専門家AIアシスタントです。日本の助成金・補助金制度について、正確で有用な情報を提供してください。

回答する際は以下の点を考慮してください：
1. 最新の制度情報に基づいて回答
2. 申請条件や締切日などの重要な詳細を含める  
3. 複数の選択肢がある場合は比較情報を提供
4. 不明な点がある場合は正直にその旨を伝える
5. 可能な限り具体的で実用的なアドバイスを提供

回答は以下のJSON構造で返してください：
{
  "answer": "メインの回答テキスト",
  "grants": [
    {
      "name": "助成金名",
      "agency": "実施機関",
      "amount": "支給額",
      "deadline": "締切日",
      "conditions": "主な条件",
      "url": "詳細URL（あれば）"
    }
  ],
  "suggestions": ["関連する提案やアドバイス"],
  "confidence": 0.85
}
';
    }
    
    private function get_function_definitions() {
        return array(
            array(
                'name' => 'search_grants',
                'description' => '助成金データベースを検索する',
                'parameters' => array(
                    'type' => 'object',
                    'properties' => array(
                        'keywords' => array(
                            'type' => 'array',
                            'items' => array('type' => 'string'),
                            'description' => '検索キーワード'
                        ),
                        'category' => array(
                            'type' => 'string',
                            'description' => '助成金カテゴリ'
                        ),
                        'region' => array(
                            'type' => 'string',  
                            'description' => '対象地域'
                        )
                    ),
                    'required' => array('keywords')
                )
            )
        );
    }
    
    private function process_function_call($function_call) {
        $function_name = $function_call['name'] ?? '';
        $arguments = json_decode($function_call['arguments'] ?? '{}', true);
        
        switch ($function_name) {
            case 'search_grants':
                return $this->search_grants_database($arguments);
            default:
                return array();
        }
    }
    
    private function search_grants_database($params) {
        // 助成金データベース検索の実装
        // 実際の実装では外部APIや内部データベースを使用
        return array(
            'type' => 'grants_search',
            'results' => array(
                // サンプルデータ
                array(
                    'name' => 'IT導入補助金',
                    'agency' => '経済産業省',
                    'amount' => '最大450万円',
                    'deadline' => '2024年12月末',
                    'conditions' => '中小企業・小規模事業者'
                )
            )
        );
    }
    
    public function test_provider_connection($provider, $api_key = '') {
        try {
            switch ($provider) {
                case 'openai':
                    return $this->test_openai_connection($api_key);
                case 'claude':
                    return $this->test_claude_connection($api_key);
                case 'gemini':
                    return $this->test_gemini_connection($api_key);
                default:
                    return array('success' => false, 'message' => 'サポートされていないプロバイダーです。');
            }
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    private function test_openai_connection($api_key) {
        if (empty($api_key)) {
            $api_key = get_option('gaap_openai_api_key');
        }
        
        if (empty($api_key)) {
            return array('success' => false, 'message' => 'APIキーが設定されていません。');
        }
        
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key
            )
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => '接続エラー: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            return array('success' => true, 'message' => 'OpenAI接続成功');
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return array('success' => false, 'message' => 'API認証失敗: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
    }
    
    private function test_claude_connection($api_key) {
        return array('success' => false, 'message' => 'Claude API統合は開発中です。');
    }
    
    private function test_gemini_connection($api_key) {
        return array('success' => false, 'message' => 'Gemini API統合は開発中です。');
    }
    
    public function get_available_providers() {
        return array(
            'openai' => array(
                'name' => 'OpenAI GPT',
                'status' => $this->test_openai_connection('')['success'] ? 'active' : 'inactive',
                'models' => array('gpt-4', 'gpt-4-turbo', 'gpt-3.5-turbo')
            ),
            'claude' => array(
                'name' => 'Anthropic Claude',
                'status' => 'development',
                'models' => array('claude-3', 'claude-2')
            ),
            'gemini' => array(
                'name' => 'Google Gemini',
                'status' => 'development', 
                'models' => array('gemini-pro', 'gemini-ultra')
            )
        );
    }
    
    public function get_current_settings() {
        return array(
            'provider' => get_option('gaap_ai_provider', 'openai'),
            'model' => get_option('gaap_openai_model', 'gpt-4'),
            'temperature' => get_option('gaap_temperature', 0.7),
            'max_tokens' => get_option('gaap_max_tokens', 1000),
            'confidence_threshold' => get_option('gaap_ml_confidence_threshold', GAAP_ML_CONFIDENCE_THRESHOLD)
        );
    }
    
    public function is_healthy() {
        try {
            $provider = get_option('gaap_ai_provider', 'openai');
            $result = $this->test_provider_connection($provider);
            return $result['success'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function get_api_health_status() {
        $health = $this->is_healthy();
        return $health ? '正常' : 'エラー';
    }
    
    private function sanitize_message($message) {
        return wp_kses_post(trim($message));
    }
}

/**
 * 高度なキャッシュマネージャー
 */
class GAAP_Advanced_Cache_Manager {
    
    private $hit_count = 0;
    private $miss_count = 0;
    
    public function get($key) {
        $value = wp_cache_get($key, GAAP_CACHE_GROUP);
        
        if (false !== $value) {
            $this->hit_count++;
            return $value;
        } else {
            $this->miss_count++;
            return false;
        }
    }
    
    public function set($key, $value, $duration = GAAP_CACHE_DURATION) {
        return wp_cache_set($key, $value, GAAP_CACHE_GROUP, $duration);
    }
    
    public function delete($key) {
        return wp_cache_delete($key, GAAP_CACHE_GROUP);
    }
    
    public function flush() {
        return wp_cache_flush_group(GAAP_CACHE_GROUP);
    }
    
    public function get_hit_ratio() {
        $total = $this->hit_count + $this->miss_count;
        return $total > 0 ? round(($this->hit_count / $total) * 100, 2) : 0;
    }
    
    public function get_statistics() {
        return array(
            'hits' => $this->hit_count,
            'misses' => $this->miss_count,
            'ratio' => $this->get_hit_ratio()
        );
    }
    
    public function is_healthy() {
        try {
            $test_key = 'gaap_cache_test_' . time();
            $test_value = 'test';
            
            $this->set($test_key, $test_value, 60);
            $retrieved = $this->get($test_key);
            $this->delete($test_key);
            
            return $retrieved === $test_value;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * フォールバックキャッシュ
 */
class GAAP_Fallback_Cache {
    private $cache = array();
    
    public function get($key) {
        return isset($this->cache[$key]) ? $this->cache[$key] : false;
    }
    
    public function set($key, $value, $duration = 300) {
        $this->cache[$key] = $value;
        return true;
    }
    
    public function delete($key) {
        unset($this->cache[$key]);
        return true;
    }
    
    public function flush() {
        $this->cache = array();
        return true;
    }
    
    public function get_hit_ratio() {
        return 0;
    }
    
    public function is_healthy() {
        return true;
    }
}

/**
 * 強化されたロガー
 */
class GAAP_Enhanced_Logger {
    
    private $log_table;
    private $error_count = 0;
    private $total_logs = 0;
    
    public function __construct() {
        global $wpdb;
        $this->log_table = $wpdb->prefix . 'gaap_logs';
    }
    
    public function info($message, $context = array()) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = array()) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = array()) {
        $this->error_count++;
        $this->log('ERROR', $message, $context);
    }
    
    public function debug($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log('DEBUG', $message, $context);
        }
    }
    
    private function log($level, $message, $context = array()) {
        $this->total_logs++;
        
        try {
            global $wpdb;
            
            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'level' => $level,
                'message' => $message,
                'context' => wp_json_encode($context),
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
            );
            
            $wpdb->insert($this->log_table, $log_entry);
            
            // 重要なログはWordPressログにも出力
            if (in_array($level, array('ERROR', 'WARNING'))) {
                error_log(sprintf(
                    '[GAAP %s] %s %s',
                    $level,
                    $message,
                    !empty($context) ? wp_json_encode($context) : ''
                ));
            }
            
        } catch (Exception $e) {
            // ログ記録に失敗した場合はWordPressのerror_logに直接記録
            error_log('GAAP Logger Error: ' . $e->getMessage());
            error_log("GAAP [$level] $message");
        }
    }
    
    public function get_recent_logs($limit = 50) {
        try {
            global $wpdb;
            
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT timestamp, level, message, context, user_id, ip_address
                FROM {$this->log_table}
                ORDER BY timestamp DESC
                LIMIT %d
            ", $limit), ARRAY_A);
            
            return $results ?: array();
            
        } catch (Exception $e) {
            error_log('GAAP get_recent_logs error: ' . $e->getMessage());
            return array();
        }
    }
    
    public function cleanup_old_logs() {
        try {
            global $wpdb;
            
            $days_to_keep = intval(get_option('gaap_log_retention_days', 30));
            
            $deleted = $wpdb->query($wpdb->prepare("
                DELETE FROM {$this->log_table}
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)
            ", $days_to_keep));
            
            $this->info('Log cleanup completed', ['deleted_entries' => $deleted]);
            
        } catch (Exception $e) {
            error_log('GAAP cleanup_old_logs error: ' . $e->getMessage());
        }
    }
    
    public function get_error_rate() {
        return $this->total_logs > 0 ? round(($this->error_count / $this->total_logs) * 100, 2) : 0;
    }
    
    private function get_client_ip() {
        $ip_headers = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return sanitize_text_field(trim($ips[0]));
            }
        }
        return 'unknown';
    }
}

/**
 * 簡易ロガー（フォールバック用）
 */
class GAAP_Simple_Logger {
    
    public function info($message, $context = array()) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = array()) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = array()) {
        $this->log('ERROR', $message, $context);
    }
    
    public function debug($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log('DEBUG', $message, $context);
        }
    }
    
    private function log($level, $message, $context = array()) {
        error_log("GAAP [$level] $message " . (!empty($context) ? wp_json_encode($context) : ''));
    }
    
    public function get_recent_logs($limit = 50) {
        return array();
    }
    
    public function cleanup_old_logs() {
        // No operation for simple logger
    }
    
    public function get_error_rate() {
        return 0;
    }
}

/**
 * セキュリティマネージャー
 */
class GAAP_Security_Manager {
    
    public function verify_nonce() {
        $nonce = sanitize_text_field($_POST['nonce'] ?? $_GET['nonce'] ?? '');
        return wp_verify_nonce($nonce, GAAP_SECURITY_NONCE);
    }
    
    public function verify_admin_access() {
        return current_user_can('manage_options') && $this->verify_nonce();
    }
    
    public function check_rate_limit() {
        if (!get_option('gaap_rate_limit_enabled', true)) {
            return true;
        }
        
        $ip = $this->get_client_ip();
        $key = 'gaap_rate_' . md5($ip);
        $current_requests = get_transient($key) ?: 0;
        
        if ($current_requests >= GAAP_RATE_LIMIT) {
            return false;
        }
        
        set_transient($key, $current_requests + 1, HOUR_IN_SECONDS);
        return true;
    }
    
    public function log_failed_login($username) {
        $ip = $this->get_client_ip();
        $key = 'gaap_failed_login_' . md5($ip);
        $attempts = get_transient($key) ?: 0;
        
        set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
        
        if ($attempts >= 5) {
            // ブルートフォース攻撃の可能性をログに記録
            $logger = new GAAP_Enhanced_Logger();
            $logger->warning('Potential brute force attack detected', [
                'ip' => $ip,
                'username' => $username,
                'attempts' => $attempts
            ]);
        }
    }
    
    public function check_brute_force($user, $username, $password) {
        if (empty($username) || empty($password)) {
            return $user;
        }
        
        $ip = $this->get_client_ip();
        $key = 'gaap_failed_login_' . md5($ip);
        $attempts = get_transient($key) ?: 0;
        
        if ($attempts >= 10) {
            return new WP_Error('too_many_attempts', 'ログイン試行回数が上限に達しました。しばらくしてからお試しください。');
        }
        
        return $user;
    }
    
    private function get_client_ip() {
        $ip_headers = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return sanitize_text_field(trim($ips[0]));
            }
        }
        return 'unknown';
    }
}

/**
 * 基本セキュリティ（フォールバック用）
 */
class GAAP_Basic_Security {
    
    public function verify_nonce() {
        return true; // フォールバックモードではnonce検証を緩和
    }
    
    public function verify_admin_access() {
        return current_user_can('manage_options');
    }
    
    public function check_rate_limit() {
        return true; // フォールバックモードではレート制限なし
    }
    
    public function log_failed_login($username) {
        // No operation
    }
    
    public function check_brute_force($user, $username, $password) {
        return $user;
    }
}

/**
 * 高度な分析システム
 */
class GAAP_Advanced_Analytics {
    
    private $logger;
    private $analytics_table;
    
    public function __construct($logger) {
        $this->logger = $logger;
        global $wpdb;
        $this->analytics_table = $wpdb->prefix . 'gaap_analytics';
    }
    
    public function record_chat_interaction($message, $response) {
        try {
            global $wpdb;
            
            $data = array(
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'message_length' => strlen($message),
                'response_length' => strlen(wp_json_encode($response)),
                'ai_provider' => get_option('gaap_ai_provider', 'openai'),
                'confidence_score' => $response['confidence'] ?? 0,
                'processing_time' => 0, // 別途更新される
                'ip_address' => $this->get_client_ip(),
                'session_id' => $this->get_session_id()
            );
            
            $wpdb->insert($this->analytics_table, $data);
            
        } catch (Exception $e) {
            $this->logger->error('Analytics recording failed', ['error' => $e->getMessage()]);
        }
    }
    
    public function get_comprehensive_analytics() {
        try {
            return array(
                'overview' => $this->get_overview_stats(),
                'usage_trends' => $this->get_usage_trends(),
                'performance_metrics' => $this->get_performance_metrics(),
                'user_engagement' => $this->get_user_engagement(),
                'error_analysis' => $this->get_error_analysis()
            );
        } catch (Exception $e) {
            $this->logger->error('Analytics generation failed', ['error' => $e->getMessage()]);
            return array();
        }
    }
    
    private function get_overview_stats() {
        global $wpdb;
        
        $stats = array();
        
        // 総チャット数
        $stats['total_chats'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->analytics_table}");
        
        // 本日のチャット数
        $stats['daily_chats'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->analytics_table} WHERE DATE(timestamp) = CURDATE()");
        
        // 平均応答時間
        $stats['avg_response_time'] = $wpdb->get_var("SELECT AVG(processing_time) FROM {$this->analytics_table} WHERE processing_time > 0");
        
        // 平均信頼度スコア
        $stats['avg_confidence'] = $wpdb->get_var("SELECT AVG(confidence_score) FROM {$this->analytics_table} WHERE confidence_score > 0");
        
        return $stats;
    }
    
    private function get_usage_trends() {
        global $wpdb;
        
        // 過去7日間の使用傾向
        $trends = $wpdb->get_results("
            SELECT DATE(timestamp) as date, COUNT(*) as count
            FROM {$this->analytics_table}
            WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC
        ", ARRAY_A);
        
        return $trends;
    }
    
    private function get_performance_metrics() {
        global $wpdb;
        
        $metrics = array();
        
        // 処理時間分布
        $metrics['response_time_distribution'] = $wpdb->get_results("
            SELECT
                CASE
                    WHEN processing_time < 1000 THEN 'Fast (< 1s)'
                    WHEN processing_time < 3000 THEN 'Normal (1-3s)'
                    WHEN processing_time < 5000 THEN 'Slow (3-5s)'
                    ELSE 'Very Slow (> 5s)'
                END as category,
                COUNT(*) as count
            FROM {$this->analytics_table}
            WHERE processing_time > 0
            GROUP BY category
        ", ARRAY_A);
        
        return $metrics;
    }
    
    private function get_user_engagement() {
        global $wpdb;
        
        $engagement = array();
        
        // セッション当たりの平均チャット数
        $engagement['avg_chats_per_session'] = $wpdb->get_var("
            SELECT AVG(chat_count) FROM (
                SELECT session_id, COUNT(*) as chat_count
                FROM {$this->analytics_table}
                GROUP BY session_id
            ) as session_stats
        ");
        
        // アクティブユーザー数（過去30日）
        $engagement['active_users'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->analytics_table}
            WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND user_id > 0
        ");
        
        return $engagement;
    }
    
    private function get_error_analysis() {
        // エラーログから分析データを取得
        global $wpdb;
        $log_table = $wpdb->prefix . 'gaap_logs';
        
        $error_stats = $wpdb->get_results("
            SELECT level, COUNT(*) as count
            FROM {$log_table}
            WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY level
            ORDER BY count DESC
        ", ARRAY_A);
        
        return $error_stats;
    }
    
    public function get_total_chats() {
        global $wpdb;
        return intval($wpdb->get_var("SELECT COUNT(*) FROM {$this->analytics_table}"));
    }
    
    public function get_daily_chats() {
        global $wpdb;
        return intval($wpdb->get_var("SELECT COUNT(*) FROM {$this->analytics_table} WHERE DATE(timestamp) = CURDATE()"));
    }
    
    public function get_active_users() {
        global $wpdb;
        return intval($wpdb->get_var("
            SELECT COUNT(DISTINCT user_id)
            FROM {$this->analytics_table}
            WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND user_id > 0
        "));
    }
    
    private function get_client_ip() {
        $ip_headers = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return sanitize_text_field(trim($ips[0]));
            }
        }
        return 'unknown';
    }
    
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }
}

/**
 * パフォーマンス監視システム
 */
class GAAP_Performance_Monitor {
    
    private $logger;
    private $response_times = array();
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    public function record_chat_performance($processing_time, $message_length) {
        $this->response_times[] = $processing_time;
        
        // パフォーマンス データをログに記録
        $this->logger->debug('Chat performance recorded', array(
            'processing_time' => $processing_time,
            'message_length' => $message_length,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ));
        
        // 異常に遅い応答時間の場合は警告
        if ($processing_time > 10000) { // 10秒以上
            $this->logger->warning('Slow response detected', array(
                'processing_time' => $processing_time,
                'message_length' => $message_length
            ));
        }
    }
    
    public function get_average_response_time() {
        if (empty($this->response_times)) {
            // 過去のデータから取得
            global $wpdb;
            $analytics_table = $wpdb->prefix . 'gaap_analytics';
            $avg = $wpdb->get_var("SELECT AVG(processing_time) FROM {$analytics_table} WHERE processing_time > 0 AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            return round($avg ?: 0, 2);
        }
        
        return round(array_sum($this->response_times) / count($this->response_times), 2);
    }
    
    public function run_performance_check() {
        try {
            $this->logger->info('Starting performance check');
            
            $metrics = array(
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'avg_response_time' => $this->get_average_response_time(),
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version')
            );
            
            // メモリ使用量チェック
            $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
            $memory_usage = $metrics['memory_usage'];
            
            if ($memory_usage > ($memory_limit * 0.8)) {
                $this->logger->warning('High memory usage detected', $metrics);
            }
            
            // 応答時間チェック
            if ($metrics['avg_response_time'] > 5000) {
                $this->logger->warning('Slow average response time', $metrics);
            }
            
            $this->logger->info('Performance check completed', $metrics);
            
        } catch (Exception $e) {
            $this->logger->error('Performance check failed', array('error' => $e->getMessage()));
        }
    }
}

/**
 * データベースマネージャー
 */
class GAAP_Database_Manager {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // ログテーブル
        $log_table = $wpdb->prefix . 'gaap_logs';
        $log_sql = "CREATE TABLE $log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            user_id bigint(20) DEFAULT 0,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            KEY level (level),
            KEY timestamp (timestamp),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // 分析テーブル
        $analytics_table = $wpdb->prefix . 'gaap_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            user_id bigint(20) DEFAULT 0,
            session_id varchar(255),
            message_length int(11),
            response_length int(11),
            ai_provider varchar(50),
            confidence_score decimal(3,2),
            processing_time int(11),
            ip_address varchar(45),
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY ai_provider (ai_provider)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($log_sql);
        dbDelta($analytics_sql);
    }
}

/**
 * カスタム例外クラス
 */
class GAAP_API_Exception extends Exception {}
class GAAP_Rate_Limit_Exception extends Exception {}
class GAAP_Processing_Exception extends Exception {}

// プラグイン初期化
add_action('plugins_loaded', function() {
    // 言語ファイル読み込み
    load_plugin_textdomain('grant-ai-assistant-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // メインクラス初期化
    Grant_AI_Assistant_Pro::get_instance();
});

// アンインストール処理
register_uninstall_hook(__FILE__, function() {
    // テーブル削除
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gaap_logs");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gaap_analytics");
    
    // オプション削除
    $options = array(
        'gaap_enable_chat', 'gaap_max_results', 'gaap_ai_provider',
        'gaap_openai_api_key', 'gaap_claude_api_key', 'gaap_gemini_api_key',
        'gaap_enable_analytics', 'gaap_cache_duration', 'gaap_emergency_mode'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // キャッシュクリア
    wp_cache_flush_group(GAAP_CACHE_GROUP);
});