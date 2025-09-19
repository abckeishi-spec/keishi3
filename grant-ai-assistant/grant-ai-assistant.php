<?php
/**
 * Plugin Name: Grant AI Assistant
 * Description: AI対話型助成金検索機能 - Grant Insight Perfectテーマ専用統合プラグイン
 * Version: 1.0.2
 * Author: Grant Insight Team
 * Requires at least: 5.8
 * Requires PHP: 7.0
 * Text Domain: grant-ai-assistant
 * Domain Path: /languages
 * Network: false
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// 定数定義
define('GAA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GAA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GAA_VERSION', '1.0.2');
define('GAA_TEXT_DOMAIN', 'grant-ai-assistant');
define('GAA_MINIMUM_PHP_VERSION', '7.0');
define('GAA_MINIMUM_WP_VERSION', '5.8');

/**
 * Grant AI Assistant メインクラス
 * プロダクションレベルの安全性とパフォーマンスを重視した設計
 */
class Grant_AI_Assistant {
    
    /**
     * シングルトンインスタンス
     */
    private static $instance = null;
    
    /**
     * プラグインの初期化フラグ
     */
    private $initialized = false;
    
    /**
     * エラーログ記録用
     */
    private $errors = array();

    /**
     * シングルトンパターン
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ - プライベートでシングルトンを保証
     */
    private function __construct() {
        // システム要件チェック
        if (!$this->meets_requirements()) {
            return;
        }

        // 基本フック登録
        add_action('init', array($this, 'init'), 10);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // プラグインライフサイクルフック
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 管理画面フック
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_notices', array($this, 'admin_notices'));
        }
    }
    
    /**
     * システム要件チェック
     */
    private function meets_requirements() {
        // PHP バージョンチェック
        if (version_compare(PHP_VERSION, GAA_MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        // WordPress バージョンチェック
        if (version_compare(get_bloginfo('version'), GAA_MINIMUM_WP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * プラグイン初期化
     */
    public function init() {
        if ($this->initialized) {
            return;
        }

        // 依存関係チェック
        if (!$this->check_dependencies()) {
            return;
        }

        // 言語ファイル読み込み
        $this->load_textdomain();
        
        // 必要ファイルの読み込み
        $this->load_dependencies();
        
        // フック登録
        $this->register_hooks();
        
        $this->initialized = true;
        
        // 初期化完了ログ
        $this->log_message('Plugin initialized successfully', 'info');
    }

    /**
     * 依存関係の総合チェック
     */
    private function check_dependencies() {
        $missing_dependencies = array();
        
        // テーマチェック
        $theme = wp_get_theme();
        if (strpos($theme->get('Name'), 'Grant Insight') === false) {
            $missing_dependencies[] = 'Grant Insight Perfectテーマ';
        }

        // 必要関数の存在チェック
        $required_functions = array(
            'gi_safe_get_meta' => 'gi_safe_get_meta関数',
            'gi_render_card' => 'gi_render_card関数',
            'gi_get_acf_field_safely' => 'gi_get_acf_field_safely関数'
        );

        foreach ($required_functions as $function => $label) {
            if (!function_exists($function)) {
                $missing_dependencies[] = $label;
            }
        }

        // 投稿タイプチェック
        if (!post_type_exists('grant')) {
            $missing_dependencies[] = 'grant投稿タイプ';
        }

        // タクソノミーチェック
        if (!taxonomy_exists('grant_category')) {
            $missing_dependencies[] = 'grant_categoryタクソノミー';
        }

        if (!empty($missing_dependencies)) {
            $this->errors['dependencies'] = $missing_dependencies;
            add_action('admin_notices', array($this, 'dependency_notice'));
            return false;
        }

        return true;
    }

    /**
     * 依存ファイルの安全な読み込み
     */
    private function load_dependencies() {
        $required_files = array(
            'includes/ai-engine.php',
            'includes/ai-chat-section.php'
        );

        foreach ($required_files as $file) {
            $file_path = GAA_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                $this->log_message("Required file not found: {$file}", 'error');
                return false;
            }
        }

        return true;
    }

    /**
     * フック登録
     */
    private function register_hooks() {
        // ショートコード登録
        add_shortcode('grant_ai_chat', array('Grant_AI_Chat_Section', 'render_shortcode'));
        
        // AJAX処理登録（ログイン・未ログイン両対応）
        add_action('wp_ajax_gaa_chat_message', array('Grant_AI_Engine', 'handle_chat_message'));
        add_action('wp_ajax_nopriv_gaa_chat_message', array('Grant_AI_Engine', 'handle_chat_message'));
        
        // セキュリティ強化
        add_action('wp_ajax_gaa_validate_settings', array($this, 'validate_settings_ajax'));
    }
    
    /**
     * スクリプト・スタイルの最適化された読み込み
     */
    public function enqueue_scripts() {
        // 管理画面では読み込まない
        if (is_admin()) {
            return;
        }

        // プラグインが無効な場合は読み込まない
        if (!get_option('gaa_enable_chat', false)) {
            return;
        }

        // APIキーが設定されていない場合は読み込まない
        if (empty(get_option('gaa_openai_api_key', ''))) {
            return;
        }

        // CSS読み込み（条件付き最適化）
        wp_enqueue_style(
            'gaa-chat-style',
            GAA_PLUGIN_URL . 'assets/ai-chat.css',
            array(),
            GAA_VERSION,
            'all'
        );
        
        // JavaScript読み込み（非同期最適化）
        wp_enqueue_script(
            'gaa-chat-script',
            GAA_PLUGIN_URL . 'assets/ai-chat.js',
            array('jquery'),
            GAA_VERSION,
            true
        );
        
        // 条件分岐によるスクリプト設定
        $ajax_settings = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gaa_chat_nonce'),
            'strings' => array(
                'thinking' => __('AIが考えています...', GAA_TEXT_DOMAIN),
                'error' => __('エラーが発生しました。もう一度お試しください。', GAA_TEXT_DOMAIN),
                'placeholder' => __('例：ITコンサル業で使える設備投資助成金は？', GAA_TEXT_DOMAIN),
                'no_results' => __('該当する助成金が見つかりませんでした', GAA_TEXT_DOMAIN),
                'search_results' => __('件の助成金が見つかりました！', GAA_TEXT_DOMAIN),
                'char_limit' => sprintf(__('メッセージは%d文字以内で入力してください。', GAA_TEXT_DOMAIN), 500),
                'network_error' => __('ネットワークエラーが発生しました。インターネット接続を確認してください。', GAA_TEXT_DOMAIN)
            ),
            'settings' => array(
                'max_message_length' => 500,
                'typing_delay' => 50,
                'scroll_delay' => 100,
                'auto_save' => true
            )
        );
        
        wp_localize_script('gaa-chat-script', 'gaa_ajax', $ajax_settings);
    }

    /**
     * 管理画面初期化
     */
    public function admin_init() {
        // 設定の登録（セキュリティ強化）
        register_setting(
            'gaa_settings',
            'gaa_openai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'show_in_rest' => false
            )
        );
        
        register_setting(
            'gaa_settings',
            'gaa_enable_chat',
            array(
                'type' => 'boolean',
                'default' => false
            )
        );
        
        register_setting(
            'gaa_settings',
            'gaa_max_results',
            array(
                'type' => 'integer',
                'default' => 6,
                'sanitize_callback' => array($this, 'sanitize_max_results')
            )
        );
        
        register_setting(
            'gaa_settings',
            'gaa_debug_mode',
            array(
                'type' => 'boolean',
                'default' => false
            )
        );
    }

    /**
     * APIキーのサニタイズ（セキュリティ強化）
     */
    public function sanitize_api_key($api_key) {
        $api_key = sanitize_text_field($api_key);
        
        // OpenAI APIキーのフォーマット検証
        if (!empty($api_key) && !preg_match('/^sk-[a-zA-Z0-9]{48}$/', $api_key)) {
            add_settings_error(
                'gaa_settings',
                'invalid_api_key',
                __('無効なOpenAI APIキーフォーマットです。', GAA_TEXT_DOMAIN),
                'error'
            );
            return get_option('gaa_openai_api_key', ''); // 元の値を保持
        }
        
        return $api_key;
    }

    /**
     * 最大結果数のサニタイズ
     */
    public function sanitize_max_results($value) {
        $value = intval($value);
        return max(1, min(20, $value)); // 1-20の範囲に制限
    }

    /**
     * 管理画面メニュー追加
     */
    public function add_admin_menu() {
        add_options_page(
            __('Grant AI Assistant 設定', GAA_TEXT_DOMAIN),
            __('Grant AI Assistant', GAA_TEXT_DOMAIN),
            'manage_options',
            'grant-ai-assistant',
            array($this, 'admin_page')
        );
    }

    /**
     * 管理画面表示（プロダクションレベルUI）
     */
    public function admin_page() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die(__('このページにアクセスする権限がありません。', GAA_TEXT_DOMAIN));
        }

        // 設定保存処理
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['gaa_admin_nonce'], 'gaa_admin_settings')) {
            $this->save_settings();
        }
        
        // 接続テスト処理
        if (isset($_POST['test_connection']) && wp_verify_nonce($_POST['gaa_admin_nonce'], 'gaa_admin_settings')) {
            $this->test_api_connection();
        }
        
        $this->render_admin_page();
    }

    /**
     * 設定保存処理（エラーハンドリング強化）
     */
    private function save_settings() {
        try {
            $api_key = isset($_POST['openai_api_key']) ? sanitize_text_field($_POST['openai_api_key']) : '';
            $enable_chat = isset($_POST['enable_chat']);
            $max_results = isset($_POST['max_results']) ? intval($_POST['max_results']) : 6;
            $debug_mode = isset($_POST['debug_mode']);
            
            // APIキーの暗号化保存（セキュリティ強化）
            if (!empty($api_key)) {
                update_option('gaa_openai_api_key', $this->encrypt_api_key($api_key));
            }
            
            update_option('gaa_enable_chat', $enable_chat);
            update_option('gaa_max_results', max(1, min(20, $max_results)));
            update_option('gaa_debug_mode', $debug_mode);
            
            add_settings_error(
                'gaa_settings',
                'settings_saved',
                __('設定を保存しました。', GAA_TEXT_DOMAIN),
                'success'
            );
            
            $this->log_message('Settings saved successfully', 'info');
            
        } catch (Exception $e) {
            add_settings_error(
                'gaa_settings',
                'save_error',
                __('設定の保存中にエラーが発生しました。', GAA_TEXT_DOMAIN),
                'error'
            );
            
            $this->log_message('Settings save error: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * API接続テスト
     */
    private function test_api_connection() {
        $api_key = $this->get_decrypted_api_key();
        
        if (empty($api_key)) {
            add_settings_error(
                'gaa_settings',
                'no_api_key',
                __('APIキーが設定されていません。', GAA_TEXT_DOMAIN),
                'error'
            );
            return;
        }

        // 簡単なAPI接続テスト
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'model' => 'gpt-4',
                'messages' => array(
                    array('role' => 'user', 'content' => 'test')
                ),
                'max_tokens' => 5
            )),
            'timeout' => 10
        ));

        if (is_wp_error($response)) {
            add_settings_error(
                'gaa_settings',
                'connection_failed',
                __('API接続テストに失敗しました。ネットワーク設定を確認してください。', GAA_TEXT_DOMAIN),
                'error'
            );
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 200) {
                add_settings_error(
                    'gaa_settings',
                    'connection_success',
                    __('API接続テストに成功しました。', GAA_TEXT_DOMAIN),
                    'success'
                );
            } else {
                add_settings_error(
                    'gaa_settings',
                    'connection_error',
                    sprintf(__('API接続エラー (HTTP %d)。APIキーを確認してください。', GAA_TEXT_DOMAIN), $status_code),
                    'error'
                );
            }
        }
    }

    /**
     * プロダクション品質の管理画面HTML
     */
    private function render_admin_page() {
        $api_key = $this->get_decrypted_api_key();
        $enable_chat = get_option('gaa_enable_chat', false);
        $max_results = get_option('gaa_max_results', 6);
        $debug_mode = get_option('gaa_debug_mode', false);
        
        // システム情報
        $system_info = $this->get_system_info();
        ?>
        <div class="wrap gaa-admin-wrap">
            <h1 class="gaa-admin-title">
                <span class="dashicons dashicons-admin-comments"></span>
                <?php esc_html_e('Grant AI Assistant 設定', GAA_TEXT_DOMAIN); ?>
                <span class="gaa-version">v<?php echo esc_html(GAA_VERSION); ?></span>
            </h1>
            
            <?php settings_errors('gaa_settings'); ?>
            
            <div class="gaa-admin-content">
                <div class="gaa-admin-main">
                    <form method="post" action="" class="gaa-settings-form">
                        <?php wp_nonce_field('gaa_admin_settings', 'gaa_admin_nonce'); ?>
                        
                        <div class="gaa-setting-section">
                            <h2 class="gaa-section-title">
                                <span class="dashicons dashicons-admin-network"></span>
                                <?php esc_html_e('API設定', GAA_TEXT_DOMAIN); ?>
                            </h2>
                            
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">
                                        <label for="openai_api_key"><?php esc_html_e('OpenAI API キー', GAA_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="password" 
                                               id="openai_api_key"
                                               name="openai_api_key" 
                                               value="<?php echo esc_attr($api_key ? str_repeat('*', 20) . '...' : ''); ?>" 
                                               class="regular-text gaa-api-key-input"
                                               placeholder="sk-..." />
                                        <button type="button" class="button gaa-show-key" data-show="<?php esc_attr_e('表示', GAA_TEXT_DOMAIN); ?>" data-hide="<?php esc_attr_e('非表示', GAA_TEXT_DOMAIN); ?>">
                                            <?php esc_html_e('表示', GAA_TEXT_DOMAIN); ?>
                                        </button>
                                        <p class="description">
                                            <?php 
                                            printf(
                                                __('OpenAI APIキーを入力してください。<a href="%s" target="_blank">こちら</a>から取得できます。', GAA_TEXT_DOMAIN),
                                                'https://platform.openai.com/account/api-keys'
                                            );
                                            ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <input type="submit" name="test_connection" class="button button-secondary" value="<?php esc_attr_e('接続テスト', GAA_TEXT_DOMAIN); ?>" />
                            </p>
                        </div>
                        
                        <div class="gaa-setting-section">
                            <h2 class="gaa-section-title">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php esc_html_e('機能設定', GAA_TEXT_DOMAIN); ?>
                            </h2>
                            
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row"><?php esc_html_e('AIチャット機能', GAA_TEXT_DOMAIN); ?></th>
                                    <td>
                                        <fieldset>
                                            <label>
                                                <input type="checkbox" name="enable_chat" value="1" <?php checked($enable_chat); ?> />
                                                <?php esc_html_e('AIチャット機能を有効にする', GAA_TEXT_DOMAIN); ?>
                                            </label>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="max_results"><?php esc_html_e('最大表示件数', GAA_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" 
                                               id="max_results"
                                               name="max_results" 
                                               value="<?php echo esc_attr($max_results); ?>" 
                                               min="1" 
                                               max="20" 
                                               class="small-text" />
                                        <p class="description">
                                            <?php esc_html_e('一度に表示する助成金の最大件数（1-20）', GAA_TEXT_DOMAIN); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('デバッグモード', GAA_TEXT_DOMAIN); ?></th>
                                    <td>
                                        <fieldset>
                                            <label>
                                                <input type="checkbox" name="debug_mode" value="1" <?php checked($debug_mode); ?> />
                                                <?php esc_html_e('デバッグモードを有効にする', GAA_TEXT_DOMAIN); ?>
                                            </label>
                                            <p class="description">
                                                <?php esc_html_e('開発者向け：詳細なログを出力します', GAA_TEXT_DOMAIN); ?>
                                            </p>
                                        </fieldset>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <?php submit_button(__('設定を保存', GAA_TEXT_DOMAIN), 'primary', 'submit'); ?>
                    </form>
                </div>
                
                <div class="gaa-admin-sidebar">
                    <!-- 使用方法 -->
                    <div class="gaa-admin-box">
                        <h3><?php esc_html_e('使用方法', GAA_TEXT_DOMAIN); ?></h3>
                        <div class="gaa-usage-examples">
                            <h4><?php esc_html_e('基本的な使用', GAA_TEXT_DOMAIN); ?></h4>
                            <code>[grant_ai_chat]</code>
                            
                            <h4><?php esc_html_e('カスタマイズ例', GAA_TEXT_DOMAIN); ?></h4>
                            <code>[grant_ai_chat title="AI助成金相談" height="600px"]</code>
                            
                            <h4><?php esc_html_e('利用可能な属性', GAA_TEXT_DOMAIN); ?></h4>
                            <ul class="gaa-attribute-list">
                                <li><code>title</code> - チャットタイトル</li>
                                <li><code>height</code> - 高さ (例: 500px)</li>
                                <li><code>width</code> - 幅 (例: 100%)</li>
                                <li><code>theme</code> - テーマ (light/dark)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- システム情報 -->
                    <div class="gaa-admin-box">
                        <h3><?php esc_html_e('システム情報', GAA_TEXT_DOMAIN); ?></h3>
                        <table class="gaa-system-info">
                            <tr>
                                <td><strong><?php esc_html_e('プラグインバージョン', GAA_TEXT_DOMAIN); ?>:</strong></td>
                                <td><?php echo esc_html(GAA_VERSION); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('WordPress', GAA_TEXT_DOMAIN); ?>:</strong></td>
                                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('PHP', GAA_TEXT_DOMAIN); ?>:</strong></td>
                                <td><?php echo esc_html(PHP_VERSION); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('テーマ', GAA_TEXT_DOMAIN); ?>:</strong></td>
                                <td><?php echo esc_html(wp_get_theme()->get('Name')); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('助成金投稿数', GAA_TEXT_DOMAIN); ?>:</strong></td>
                                <td><?php echo esc_html(wp_count_posts('grant')->publish); ?>件</td>
                            </tr>
                        </table>
                        
                        <h4><?php esc_html_e('依存関係チェック', GAA_TEXT_DOMAIN); ?></h4>
                        <ul class="gaa-dependency-check">
                            <?php foreach ($system_info['dependencies'] as $dep => $status): ?>
                            <li class="<?php echo $status ? 'gaa-check-ok' : 'gaa-check-error'; ?>">
                                <span class="dashicons dashicons-<?php echo $status ? 'yes' : 'no'; ?>"></span>
                                <?php echo esc_html($dep); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .gaa-admin-wrap { max-width: 1200px; }
        .gaa-admin-title { display: flex; align-items: center; gap: 10px; }
        .gaa-version { background: #0073aa; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .gaa-admin-content { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 20px; }
        .gaa-admin-main { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .gaa-admin-sidebar { display: flex; flex-direction: column; gap: 20px; }
        .gaa-admin-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .gaa-setting-section { margin-bottom: 30px; }
        .gaa-section-title { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; color: #1d2327; }
        .gaa-api-key-input { font-family: monospace; }
        .gaa-usage-examples code { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .gaa-usage-examples h4 { margin-top: 15px; margin-bottom: 5px; }
        .gaa-attribute-list { margin-left: 20px; }
        .gaa-attribute-list code { background: #e8f4fd; color: #0073aa; }
        .gaa-system-info td { padding: 4px 8px; }
        .gaa-system-info td:first-child { width: 40%; }
        .gaa-dependency-check { list-style: none; margin-left: 0; }
        .gaa-dependency-check li { display: flex; align-items: center; gap: 8px; padding: 4px 0; }
        .gaa-check-ok { color: #046b03; }
        .gaa-check-error { color: #d63638; }
        @media (max-width: 768px) {
            .gaa-admin-content { grid-template-columns: 1fr; }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showKeyBtn = document.querySelector('.gaa-show-key');
            const apiKeyInput = document.querySelector('.gaa-api-key-input');
            
            if (showKeyBtn && apiKeyInput) {
                showKeyBtn.addEventListener('click', function() {
                    if (apiKeyInput.type === 'password') {
                        apiKeyInput.type = 'text';
                        showKeyBtn.textContent = showKeyBtn.dataset.hide;
                    } else {
                        apiKeyInput.type = 'password';
                        showKeyBtn.textContent = showKeyBtn.dataset.show;
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * システム情報取得
     */
    private function get_system_info() {
        return array(
            'dependencies' => array(
                'gi_render_card関数' => function_exists('gi_render_card'),
                'gi_safe_get_meta関数' => function_exists('gi_safe_get_meta'),
                'gi_get_acf_field_safely関数' => function_exists('gi_get_acf_field_safely'),
                'grant投稿タイプ' => post_type_exists('grant'),
                'grant_categoryタクソノミー' => taxonomy_exists('grant_category'),
                'ACFプラグイン' => class_exists('ACF')
            )
        );
    }

    /**
     * APIキーの暗号化
     */
    private function encrypt_api_key($api_key) {
        if (function_exists('openssl_encrypt')) {
            $key = wp_salt('secure_auth');
            return base64_encode(openssl_encrypt($api_key, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
        }
        return base64_encode($api_key); // フォールバック
    }

    /**
     * APIキーの復号化
     */
    private function get_decrypted_api_key() {
        $encrypted = get_option('gaa_openai_api_key', '');
        if (empty($encrypted)) {
            return '';
        }

        if (function_exists('openssl_decrypt')) {
            $key = wp_salt('secure_auth');
            $decrypted = openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $key, 0, substr($key, 0, 16));
            return $decrypted !== false ? $decrypted : base64_decode($encrypted);
        }
        
        return base64_decode($encrypted); // フォールバック
    }

    /**
     * 言語ファイル読み込み
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            GAA_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * 管理画面通知
     */
    public function admin_notices() {
        // エラーがある場合の通知表示
        if (!empty($this->errors)) {
            foreach ($this->errors as $error_type => $messages) {
                if (is_array($messages)) {
                    $message = implode(', ', $messages);
                } else {
                    $message = $messages;
                }
                
                printf(
                    '<div class="notice notice-error"><p><strong>Grant AI Assistant:</strong> %s</p></div>',
                    esc_html($message)
                );
            }
        }
    }

    /**
     * PHP バージョン警告
     */
    public function php_version_notice() {
        printf(
            '<div class="notice notice-error"><p><strong>Grant AI Assistant:</strong> %s</p></div>',
            sprintf(
                __('PHP %s以上が必要です。現在のバージョン: %s', GAA_TEXT_DOMAIN),
                GAA_MINIMUM_PHP_VERSION,
                PHP_VERSION
            )
        );
    }

    /**
     * WordPress バージョン警告
     */
    public function wp_version_notice() {
        printf(
            '<div class="notice notice-warning"><p><strong>Grant AI Assistant:</strong> %s</p></div>',
            sprintf(
                __('WordPress %s以上を推奨します。現在のバージョン: %s', GAA_TEXT_DOMAIN),
                GAA_MINIMUM_WP_VERSION,
                get_bloginfo('version')
            )
        );
    }

    /**
     * 依存関係エラー通知
     */
    public function dependency_notice() {
        if (isset($this->errors['dependencies'])) {
            $missing = implode(', ', $this->errors['dependencies']);
            printf(
                '<div class="notice notice-error"><p><strong>Grant AI Assistant:</strong> %s: %s</p></div>',
                __('以下の依存関係が不足しています', GAA_TEXT_DOMAIN),
                esc_html($missing)
            );
        }
    }
    
    /**
     * プラグイン有効化処理
     */
    public function activate() {
        // システム要件の再チェック
        if (!$this->meets_requirements()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('システム要件を満たしていないため、プラグインを有効化できません。', GAA_TEXT_DOMAIN));
        }

        // デフォルト設定
        add_option('gaa_enable_chat', false); // デフォルトは無効（セキュリティ重視）
        add_option('gaa_max_results', 6);
        add_option('gaa_debug_mode', false);
        
        // 有効化ログ
        $this->log_message('Plugin activated successfully', 'info');
        
        // 管理画面リダイレクトフラグ設定
        set_transient('gaa_activation_redirect', true, 30);
    }
    
    /**
     * プラグイン無効化処理
     */
    public function deactivate() {
        // 一時データのクリーンアップ
        delete_transient('gaa_api_test_result');
        delete_transient('gaa_activation_redirect');
        
        // キャッシュクリア
        wp_cache_flush();
        
        $this->log_message('Plugin deactivated', 'info');
    }

    /**
     * ログ記録機能
     */
    private function log_message($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = sprintf('[Grant AI Assistant] [%s] %s', strtoupper($level), $message);
            error_log($log_message);
        }
    }

    /**
     * プラグイン設定の検証（公開メソッド）
     */
    public static function validate_api_settings() {
        $instance = self::get_instance();
        $api_key = $instance->get_decrypted_api_key();
        $enabled = get_option('gaa_enable_chat', false);
        
        return array(
            'has_api_key' => !empty($api_key),
            'is_enabled' => $enabled,
            'is_ready' => !empty($api_key) && $enabled
        );
    }

    /**
     * プラグイン設定取得（公開メソッド）
     */
    public static function get_settings() {
        $instance = self::get_instance();
        return array(
            'api_key' => $instance->get_decrypted_api_key(),
            'enable_chat' => get_option('gaa_enable_chat', false),
            'max_results' => get_option('gaa_max_results', 6),
            'debug_mode' => get_option('gaa_debug_mode', false)
        );
    }
}

// プラグイン初期化（プロダクション安全な方法）
function gaa_initialize_plugin() {
    // 前提条件チェック
    if (!function_exists('add_action') || !defined('WPINC')) {
        return;
    }
    
    Grant_AI_Assistant::get_instance();
}

// WordPressが完全に読み込まれてから初期化
add_action('plugins_loaded', 'gaa_initialize_plugin', 10);

// 有効化時のリダイレクト処理
add_action('admin_init', function() {
    if (get_transient('gaa_activation_redirect')) {
        delete_transient('gaa_activation_redirect');
        if (is_admin() && !defined('DOING_AJAX')) {
            wp_safe_redirect(admin_url('options-general.php?page=grant-ai-assistant'));
            exit;
        }
    }
});

/**
 * アンインストール処理（セキュアクリーンアップ）
 */
register_uninstall_hook(__FILE__, function() {
    // すべての設定とデータを完全削除
    $options_to_delete = array(
        'gaa_openai_api_key',
        'gaa_enable_chat',
        'gaa_max_results',
        'gaa_debug_mode'
    );
    
    foreach ($options_to_delete as $option) {
        delete_option($option);
    }
    
    // 一時データの削除
    delete_transient('gaa_api_test_result');
    delete_transient('gaa_activation_redirect');
    
    // キャッシュフラッシュ
    wp_cache_flush();
});