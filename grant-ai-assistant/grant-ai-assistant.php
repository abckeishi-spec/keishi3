<?php
/**
 * Plugin Name: Grant AI Assistant
 * Description: AI対話型助成金検索機能 - Grant Insight Perfectテーマ専用統合プラグイン
 * Version: 1.0.0
 * Author: Grant Insight Team
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: grant-ai-assistant
 * Domain Path: /languages
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// 定数定義
define('GAA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GAA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GAA_VERSION', '1.0.0');
define('GAA_TEXT_DOMAIN', 'grant-ai-assistant');

/**
 * Grant AI Assistant メインクラス
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
     * シングルトンパターン
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // フック登録
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
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

        // 必要ファイルの読み込み
        $this->load_dependencies();
        
        // フック登録
        $this->register_hooks();
        
        // テキストドメインの読み込み
        $this->load_textdomain();
        
        $this->initialized = true;
        
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Grant AI Assistant: Plugin initialized successfully');
        }
    }

    /**
     * 依存関係チェック
     */
    private function check_dependencies() {
        // Grant Insight Perfectテーマのチェック
        $theme = wp_get_theme();
        if (strpos($theme->get('Name'), 'Grant Insight') === false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                esc_html_e('Grant AI Assistant requires Grant Insight Perfect theme to be active.', GAA_TEXT_DOMAIN);
                echo '</p></div>';
            });
            return false;
        }

        // 必要関数の存在チェック
        $required_functions = array(
            'gi_safe_get_meta',
            'gi_render_card',
            'gi_get_acf_field_safely'
        );

        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                add_action('admin_notices', function() use ($function) {
                    echo '<div class="notice notice-error"><p>';
                    printf(
                        esc_html__('Grant AI Assistant requires function %s to be available.', GAA_TEXT_DOMAIN),
                        '<code>' . esc_html($function) . '</code>'
                    );
                    echo '</p></div>';
                });
                return false;
            }
        }

        // 投稿タイプ'grant'の存在チェック
        if (!post_type_exists('grant')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                esc_html_e('Grant AI Assistant requires "grant" post type to be registered.', GAA_TEXT_DOMAIN);
                echo '</p></div>';
            });
            return false;
        }

        return true;
    }

    /**
     * 依存ファイルの読み込み
     */
    private function load_dependencies() {
        require_once GAA_PLUGIN_PATH . 'includes/ai-engine.php';
        require_once GAA_PLUGIN_PATH . 'includes/ai-chat-section.php';
    }

    /**
     * フック登録
     */
    private function register_hooks() {
        // ショートコード登録
        add_shortcode('grant_ai_chat', array('Grant_AI_Chat_Section', 'render_shortcode'));
        
        // AJAX処理登録
        add_action('wp_ajax_gaa_chat_message', array('Grant_AI_Engine', 'handle_chat_message'));
        add_action('wp_ajax_nopriv_gaa_chat_message', array('Grant_AI_Engine', 'handle_chat_message'));
        
        // 管理画面メニュー
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 管理画面での設定保存
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * スクリプト・スタイルの読み込み
     */
    public function enqueue_scripts() {
        // 管理画面では読み込まない
        if (is_admin()) {
            return;
        }

        // プラグインが有効でない場合は読み込まない
        if (!get_option('gaa_enable_chat', true)) {
            return;
        }

        wp_enqueue_style(
            'gaa-chat-style', 
            GAA_PLUGIN_URL . 'assets/ai-chat.css', 
            array(), 
            GAA_VERSION
        );
        
        wp_enqueue_script(
            'gaa-chat-script', 
            GAA_PLUGIN_URL . 'assets/ai-chat.js', 
            array('jquery'), 
            GAA_VERSION, 
            true
        );
        
        // AJAX設定をローカライズ
        wp_localize_script('gaa-chat-script', 'gaa_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gaa_chat_nonce'),
            'strings' => array(
                'thinking' => __('AIが考えています...', GAA_TEXT_DOMAIN),
                'error' => __('エラーが発生しました。もう一度お試しください。', GAA_TEXT_DOMAIN),
                'placeholder' => __('例：ITコンサル業で使える設備投資助成金は？', GAA_TEXT_DOMAIN),
                'no_results' => __('該当する助成金が見つかりませんでした', GAA_TEXT_DOMAIN),
                'search_results' => __('件の助成金が見つかりました！', GAA_TEXT_DOMAIN)
            )
        ));
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
     * 設定の登録
     */
    public function register_settings() {
        register_setting('gaa_settings', 'gaa_openai_api_key');
        register_setting('gaa_settings', 'gaa_enable_chat');
        register_setting('gaa_settings', 'gaa_max_results');
        register_setting('gaa_settings', 'gaa_debug_mode');
    }

    /**
     * 管理画面表示
     */
    public function admin_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['gaa_nonce'], 'gaa_admin_settings')) {
            $this->save_settings();
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 esc_html__('設定を保存しました。', GAA_TEXT_DOMAIN) . '</p></div>';
        }
        
        $this->render_admin_page();
    }

    /**
     * 設定保存処理
     */
    private function save_settings() {
        update_option('gaa_openai_api_key', sanitize_text_field($_POST['openai_api_key'] ?? ''));
        update_option('gaa_enable_chat', isset($_POST['enable_chat']));
        update_option('gaa_max_results', intval($_POST['max_results'] ?? 6));
        update_option('gaa_debug_mode', isset($_POST['debug_mode']));
    }

    /**
     * 管理画面HTML出力
     */
    private function render_admin_page() {
        $api_key = get_option('gaa_openai_api_key', '');
        $enable_chat = get_option('gaa_enable_chat', true);
        $max_results = get_option('gaa_max_results', 6);
        $debug_mode = get_option('gaa_debug_mode', false);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Grant AI Assistant 設定', GAA_TEXT_DOMAIN); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('gaa_admin_settings', 'gaa_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('OpenAI API キー', GAA_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="password" 
                                   name="openai_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('OpenAI APIキーを入力してください。', GAA_TEXT_DOMAIN); ?>
                                <a href="https://platform.openai.com/account/api-keys" target="_blank">
                                    <?php esc_html_e('こちら', GAA_TEXT_DOMAIN); ?>
                                </a>
                                <?php esc_html_e('から取得できます。', GAA_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('AIチャット機能', GAA_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_chat" <?php checked($enable_chat); ?> />
                                <?php esc_html_e('AIチャット機能を有効にする', GAA_TEXT_DOMAIN); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('最大表示件数', GAA_TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="number" 
                                   name="max_results" 
                                   value="<?php echo esc_attr($max_results); ?>" 
                                   min="1" 
                                   max="20" />
                            <p class="description">
                                <?php esc_html_e('一度に表示する助成金の最大件数（1-20）', GAA_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('デバッグモード', GAA_TEXT_DOMAIN); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="debug_mode" <?php checked($debug_mode); ?> />
                                <?php esc_html_e('デバッグモードを有効にする', GAA_TEXT_DOMAIN); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('開発者向け：詳細なログを出力します', GAA_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2><?php esc_html_e('使用方法', GAA_TEXT_DOMAIN); ?></h2>
            <div class="card">
                <p><strong><?php esc_html_e('ショートコード:', GAA_TEXT_DOMAIN); ?></strong></p>
                <p><code>[grant_ai_chat]</code></p>
                
                <p><strong><?php esc_html_e('オプション付き:', GAA_TEXT_DOMAIN); ?></strong></p>
                <ul>
                    <li><code>[grant_ai_chat height="600px"]</code> - <?php esc_html_e('高さ指定', GAA_TEXT_DOMAIN); ?></li>
                    <li><code>[grant_ai_chat title="AI助成金相談"]</code> - <?php esc_html_e('タイトル変更', GAA_TEXT_DOMAIN); ?></li>
                    <li><code>[grant_ai_chat style="minimal"]</code> - <?php esc_html_e('スタイル変更', GAA_TEXT_DOMAIN); ?></li>
                </ul>
                
                <p><strong><?php esc_html_e('配置場所:', GAA_TEXT_DOMAIN); ?></strong></p>
                <p><?php esc_html_e('投稿、固定ページ、ウィジェットエリアに配置できます。', GAA_TEXT_DOMAIN); ?></p>
            </div>

            <h2><?php esc_html_e('システム情報', GAA_TEXT_DOMAIN); ?></h2>
            <div class="card">
                <table class="widefat">
                    <tr>
                        <td><strong>Grant Insight テーマ:</strong></td>
                        <td>
                            <?php 
                            $theme = wp_get_theme();
                            echo esc_html($theme->get('Name') . ' v' . $theme->get('Version')); 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>助成金投稿数:</strong></td>
                        <td><?php echo esc_html(wp_count_posts('grant')->publish ?? 0); ?>件</td>
                    </tr>
                    <tr>
                        <td><strong>gi_render_card関数:</strong></td>
                        <td>
                            <span class="<?php echo function_exists('gi_render_card') ? 'dashicons-yes' : 'dashicons-no'; ?>">
                                <?php echo function_exists('gi_render_card') ? __('利用可能', GAA_TEXT_DOMAIN) : __('利用不可', GAA_TEXT_DOMAIN); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>gi_safe_get_meta関数:</strong></td>
                        <td>
                            <span class="<?php echo function_exists('gi_safe_get_meta') ? 'dashicons-yes' : 'dashicons-no'; ?>">
                                <?php echo function_exists('gi_safe_get_meta') ? __('利用可能', GAA_TEXT_DOMAIN) : __('利用不可', GAA_TEXT_DOMAIN); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <style>
        .card { background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; margin: 20px 0; }
        .dashicons-yes::before { content: "✅"; }
        .dashicons-no::before { content: "❌"; }
        </style>
        <?php
    }

    /**
     * テキストドメイン読み込み
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            GAA_TEXT_DOMAIN, 
            false, 
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * プラグイン有効化時の処理
     */
    public function activate() {
        // デフォルト設定
        add_option('gaa_enable_chat', true);
        add_option('gaa_max_results', 6);
        add_option('gaa_debug_mode', false);
        
        // 必要な権限のチェック
        if (!current_user_can('manage_options')) {
            wp_die(__('このプラグインを有効化する権限がありません。', GAA_TEXT_DOMAIN));
        }

        // ログ記録
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Grant AI Assistant: Plugin activated successfully');
        }
    }
    
    /**
     * プラグイン無効化時の処理
     */
    public function deactivate() {
        // 一時的なデータのクリーンアップ
        delete_transient('gaa_api_test_result');
        
        // ログ記録
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Grant AI Assistant: Plugin deactivated');
        }
    }

    /**
     * ユーティリティ: API設定の検証
     */
    public static function validate_api_settings() {
        $api_key = get_option('gaa_openai_api_key', '');
        $enabled = get_option('gaa_enable_chat', true);
        
        return array(
            'has_api_key' => !empty($api_key),
            'is_enabled' => $enabled,
            'is_ready' => !empty($api_key) && $enabled
        );
    }

    /**
     * ユーティリティ: プラグイン設定取得
     */
    public static function get_settings() {
        return array(
            'api_key' => get_option('gaa_openai_api_key', ''),
            'enable_chat' => get_option('gaa_enable_chat', true),
            'max_results' => get_option('gaa_max_results', 6),
            'debug_mode' => get_option('gaa_debug_mode', false)
        );
    }
}

// プラグイン初期化
add_action('plugins_loaded', function() {
    Grant_AI_Assistant::get_instance();
});

/**
 * アンインストール時のクリーンアップ
 */
register_uninstall_hook(__FILE__, function() {
    // 全ての設定を削除
    delete_option('gaa_openai_api_key');
    delete_option('gaa_enable_chat');
    delete_option('gaa_max_results');
    delete_option('gaa_debug_mode');
    
    // 一時データの削除
    delete_transient('gaa_api_test_result');
});