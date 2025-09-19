<?php
/**
 * Grant AI Assistant - Chat Section Renderer
 * AIチャットセクションの表示とショートコード処理
 * 
 * @package Grant_AI_Assistant
 * @version 1.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Grant AI Chat Section クラス
 * チャットインターフェースの表示を担当
 */
class Grant_AI_Chat_Section {
    
    /**
     * ショートコードレンダリング
     */
    public static function render_shortcode($atts) {
        // 属性のデフォルト値設定
        $atts = shortcode_atts(array(
            'style' => 'default',
            'height' => '500px',
            'title' => __('AI助成金コンシェルジュ', GAA_TEXT_DOMAIN),
            'subtitle' => __('簡単な質問に答えるだけで、ピッタリの助成金を見つけます', GAA_TEXT_DOMAIN),
            'width' => '100%',
            'theme' => 'light'
        ), $atts, 'grant_ai_chat');
        
        // プラグイン設定確認
        $settings = Grant_AI_Assistant::validate_api_settings();
        if (!$settings['is_enabled']) {
            return '<div class="gaa-notice gaa-notice-info">' . 
                   __('AIチャット機能は現在無効です。', GAA_TEXT_DOMAIN) . 
                   '</div>';
        }

        if (!$settings['has_api_key']) {
            if (current_user_can('manage_options')) {
                $admin_url = admin_url('options-general.php?page=grant-ai-assistant');
                return '<div class="gaa-notice gaa-notice-warning">' . 
                       sprintf(
                           __('AIチャット機能を使用するには<a href="%s">設定</a>でAPIキーを入力してください。', GAA_TEXT_DOMAIN),
                           esc_url($admin_url)
                       ) . 
                       '</div>';
            } else {
                return '<div class="gaa-notice gaa-notice-warning">' . 
                       __('AIチャット機能は現在設定中です。しばらくお待ちください。', GAA_TEXT_DOMAIN) . 
                       '</div>';
            }
        }
        
        // HTMLレンダリング
        ob_start();
        self::render_chat_interface($atts);
        return ob_get_clean();
    }
    
    /**
     * チャットインターフェース描画
     */
    private static function render_chat_interface($atts) {
        $unique_id = 'gaa-chat-' . uniqid();
        $container_style = sprintf('height: %s; max-width: %s;', esc_attr($atts['height']), esc_attr($atts['width']));
        $theme_class = 'gaa-theme-' . esc_attr($atts['theme']);
        ?>
        <div id="<?php echo esc_attr($unique_id); ?>" 
             class="gaa-chat-container <?php echo esc_attr($theme_class); ?>" 
             style="<?php echo $container_style; ?>"
             data-style="<?php echo esc_attr($atts['style']); ?>">
            
            <!-- ヘッダー -->
            <div class="gaa-chat-header">
                <div class="gaa-ai-avatar">
                    <span class="gaa-avatar-icon">🤖</span>
                </div>
                <div class="gaa-chat-title">
                    <h3><?php echo esc_html($atts['title']); ?></h3>
                    <?php if (!empty($atts['subtitle'])): ?>
                    <p class="gaa-chat-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="gaa-chat-controls">
                    <button type="button" class="gaa-minimize-btn" title="<?php esc_attr_e('最小化', GAA_TEXT_DOMAIN); ?>">
                        <span class="gaa-minimize-icon">−</span>
                    </button>
                </div>
            </div>
            
            <!-- チャット履歴エリア -->
            <div class="gaa-chat-history" id="<?php echo esc_attr($unique_id); ?>-history">
                <!-- 初期メッセージ -->
                <div class="gaa-message gaa-ai-message gaa-initial-message">
                    <div class="gaa-avatar">🤖</div>
                    <div class="gaa-message-content">
                        <p><?php esc_html_e('こんにちは！助成金探しをお手伝いします。', GAA_TEXT_DOMAIN); ?></p>
                        <p><?php esc_html_e('どのような事業をされていますか？どんな用途で助成金をお探しですか？', GAA_TEXT_DOMAIN); ?></p>
                    </div>
                    <div class="gaa-message-time" data-time="<?php echo esc_attr(current_time('c')); ?>">
                        <?php echo esc_html(current_time('H:i')); ?>
                    </div>
                </div>
            </div>
            
            <!-- クイック選択ボタン -->
            <div class="gaa-quick-buttons" id="<?php echo esc_attr($unique_id); ?>-quick">
                <div class="gaa-quick-buttons-title">
                    <?php esc_html_e('よくある質問:', GAA_TEXT_DOMAIN); ?>
                </div>
                <div class="gaa-quick-buttons-grid">
                    <?php
                    $quick_options = array(
                        array(
                            'icon' => '💻',
                            'text' => __('IT・サービス業', GAA_TEXT_DOMAIN),
                            'message' => __('IT・サービス業です', GAA_TEXT_DOMAIN)
                        ),
                        array(
                            'icon' => '🏭',
                            'text' => __('製造業', GAA_TEXT_DOMAIN),
                            'message' => __('製造業です', GAA_TEXT_DOMAIN)
                        ),
                        array(
                            'icon' => '🍽️',
                            'text' => __('飲食業', GAA_TEXT_DOMAIN),
                            'message' => __('飲食業です', GAA_TEXT_DOMAIN)
                        ),
                        array(
                            'icon' => '⚙️',
                            'text' => __('設備投資', GAA_TEXT_DOMAIN),
                            'message' => __('設備投資を考えています', GAA_TEXT_DOMAIN)
                        ),
                        array(
                            'icon' => '🚀',
                            'text' => __('新事業開始', GAA_TEXT_DOMAIN),
                            'message' => __('新事業を始めたいと思っています', GAA_TEXT_DOMAIN)
                        ),
                        array(
                            'icon' => '📱',
                            'text' => __('DX化', GAA_TEXT_DOMAIN),
                            'message' => __('DX化を進めたいと考えています', GAA_TEXT_DOMAIN)
                        ),
                        array(
                            'icon' => '👥',
                            'text' => __('人材育成', GAA_TEXT_DOMAIN),
                            'message' => __('人材育成・研修について助成金を探しています', GAA_TEXT_DOMAIN)
                        ),
                        array(
                            'icon' => '🌐',
                            'text' => __('海外展開', GAA_TEXT_DOMAIN),
                            'message' => __('海外展開を検討しています', GAA_TEXT_DOMAIN)
                        )
                    );
                    
                    foreach ($quick_options as $option):
                    ?>
                    <button class="gaa-quick-btn" 
                            data-message="<?php echo esc_attr($option['message']); ?>"
                            title="<?php echo esc_attr($option['text']); ?>">
                        <span class="gaa-quick-btn-icon"><?php echo $option['icon']; ?></span>
                        <span class="gaa-quick-btn-text"><?php echo esc_html($option['text']); ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 入力エリア -->
            <div class="gaa-input-area">
                <div class="gaa-input-container">
                    <div class="gaa-input-wrapper">
                        <textarea id="<?php echo esc_attr($unique_id); ?>-input" 
                                class="gaa-message-input" 
                                placeholder="<?php echo esc_attr__('ここにメッセージを入力してください...（例：ITコンサル業で使える設備投資助成金は？）', GAA_TEXT_DOMAIN); ?>"
                                maxlength="500"
                                rows="1"></textarea>
                        <div class="gaa-input-counter">
                            <span class="gaa-char-count">0</span>/500
                        </div>
                    </div>
                    <button id="<?php echo esc_attr($unique_id); ?>-send" 
                            class="gaa-send-button" 
                            type="button"
                            title="<?php esc_attr_e('送信', GAA_TEXT_DOMAIN); ?>">
                        <span class="gaa-send-icon">📤</span>
                        <span class="gaa-send-text"><?php esc_html_e('送信', GAA_TEXT_DOMAIN); ?></span>
                        <span class="gaa-loading hidden">
                            <span class="gaa-spinner"></span>
                        </span>
                    </button>
                </div>
                
                <!-- 入力ヒント -->
                <div class="gaa-input-hints">
                    <div class="gaa-hint-item">
                        💡 <?php esc_html_e('業種や目的を具体的に教えてください', GAA_TEXT_DOMAIN); ?>
                    </div>
                    <div class="gaa-hint-item">
                        ⭐ <?php esc_html_e('Enterキーで送信、Shift+Enterで改行', GAA_TEXT_DOMAIN); ?>
                    </div>
                </div>
            </div>
            
            <!-- 結果表示エリア -->
            <div class="gaa-results-area" id="<?php echo esc_attr($unique_id); ?>-results">
                <!-- 動的に助成金カードが表示される -->
            </div>
            
            <!-- フッター情報 -->
            <div class="gaa-chat-footer">
                <div class="gaa-disclaimer">
                    <small>
                        <?php esc_html_e('※ AI が提案する助成金情報は参考情報です。詳細は各助成金の公式サイトでご確認ください。', GAA_TEXT_DOMAIN); ?>
                    </small>
                </div>
                <div class="gaa-powered-by">
                    <small>
                        <?php 
                        printf(
                            __('Powered by %s', GAA_TEXT_DOMAIN),
                            '<strong>Grant Insight AI</strong>'
                        ); 
                        ?>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- チャット初期化スクリプト -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof GAAChat !== 'undefined') {
                new GAAChat('<?php echo esc_js($unique_id); ?>');
            } else {
                console.error('Grant AI Assistant: GAAChat class not found. Please check if ai-chat.js is loaded.');
            }
        });
        </script>
        <?php
    }

    /**
     * 管理画面用プレビュー表示
     */
    public static function render_admin_preview() {
        $preview_atts = array(
            'title' => __('AI助成金コンシェルジュ（プレビュー）', GAA_TEXT_DOMAIN),
            'subtitle' => __('これはプレビューです。実際の動作は公開ページでご確認ください。', GAA_TEXT_DOMAIN),
            'height' => '400px',
            'style' => 'preview'
        );
        
        ?>
        <div class="gaa-admin-preview">
            <h3><?php esc_html_e('ショートコードプレビュー', GAA_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('以下は [grant_ai_chat] ショートコードの表示例です:', GAA_TEXT_DOMAIN); ?></p>
            
            <?php self::render_chat_interface($preview_atts); ?>
            
            <div class="gaa-preview-overlay">
                <div class="gaa-preview-notice">
                    <h4><?php esc_html_e('プレビューモード', GAA_TEXT_DOMAIN); ?></h4>
                    <p><?php esc_html_e('実際のAI機能は公開ページで動作します', GAA_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        
        <style>
        .gaa-admin-preview {
            position: relative;
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .gaa-preview-overlay {
            position: absolute;
            top: 60px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        
        .gaa-preview-notice {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .gaa-preview-notice h4 {
            margin: 0 0 10px 0;
            color: #666;
        }
        
        .gaa-preview-notice p {
            margin: 0;
            color: #888;
        }
        </style>
        <?php
    }

    /**
     * ショートコードヘルプ情報
     */
    public static function render_shortcode_help() {
        ?>
        <div class="gaa-shortcode-help">
            <h4><?php esc_html_e('利用可能なショートコードオプション', GAA_TEXT_DOMAIN); ?></h4>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('属性', GAA_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('説明', GAA_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('デフォルト値', GAA_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('例', GAA_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>title</code></td>
                        <td><?php esc_html_e('チャットのタイトル', GAA_TEXT_DOMAIN); ?></td>
                        <td><?php esc_html_e('AI助成金コンシェルジュ', GAA_TEXT_DOMAIN); ?></td>
                        <td><code>title="助成金相談AI"</code></td>
                    </tr>
                    <tr>
                        <td><code>height</code></td>
                        <td><?php esc_html_e('チャットエリアの高さ', GAA_TEXT_DOMAIN); ?></td>
                        <td>500px</td>
                        <td><code>height="600px"</code></td>
                    </tr>
                    <tr>
                        <td><code>width</code></td>
                        <td><?php esc_html_e('チャットエリアの幅', GAA_TEXT_DOMAIN); ?></td>
                        <td>100%</td>
                        <td><code>width="800px"</code></td>
                    </tr>
                    <tr>
                        <td><code>style</code></td>
                        <td><?php esc_html_e('表示スタイル', GAA_TEXT_DOMAIN); ?></td>
                        <td>default</td>
                        <td><code>style="minimal"</code></td>
                    </tr>
                    <tr>
                        <td><code>theme</code></td>
                        <td><?php esc_html_e('カラーテーマ', GAA_TEXT_DOMAIN); ?></td>
                        <td>light</td>
                        <td><code>theme="dark"</code></td>
                    </tr>
                </tbody>
            </table>
            
            <h5><?php esc_html_e('使用例', GAA_TEXT_DOMAIN); ?></h5>
            <div class="gaa-examples">
                <div class="gaa-example">
                    <h6><?php esc_html_e('基本的な使用', GAA_TEXT_DOMAIN); ?></h6>
                    <code>[grant_ai_chat]</code>
                </div>
                
                <div class="gaa-example">
                    <h6><?php esc_html_e('カスタマイズ例', GAA_TEXT_DOMAIN); ?></h6>
                    <code>[grant_ai_chat title="助成金AI相談" height="700px" theme="dark"]</code>
                </div>
                
                <div class="gaa-example">
                    <h6><?php esc_html_e('コンパクト表示', GAA_TEXT_DOMAIN); ?></h6>
                    <code>[grant_ai_chat height="400px" width="600px" style="minimal"]</code>
                </div>
            </div>
        </div>
        
        <style>
        .gaa-shortcode-help {
            margin: 20px 0;
        }
        
        .gaa-examples {
            margin-top: 15px;
        }
        
        .gaa-example {
            margin: 10px 0;
            padding: 10px;
            background: #f8f8f8;
            border-left: 4px solid #0073aa;
        }
        
        .gaa-example h6 {
            margin: 0 0 5px 0;
            font-weight: 600;
        }
        
        .gaa-example code {
            background: #e8e8e8;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: Consolas, Monaco, monospace;
        }
        </style>
        <?php
    }

    /**
     * チャットエラー表示
     */
    public static function render_error_state($message = '') {
        if (empty($message)) {
            $message = __('AIチャット機能でエラーが発生しました。', GAA_TEXT_DOMAIN);
        }
        
        ?>
        <div class="gaa-error-state">
            <div class="gaa-error-icon">⚠️</div>
            <div class="gaa-error-message">
                <h4><?php esc_html_e('エラー', GAA_TEXT_DOMAIN); ?></h4>
                <p><?php echo esc_html($message); ?></p>
            </div>
            <div class="gaa-error-actions">
                <button type="button" class="gaa-retry-btn" onclick="location.reload()">
                    <?php esc_html_e('再読み込み', GAA_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * ローディング状態表示
     */
    public static function render_loading_state() {
        ?>
        <div class="gaa-loading-state">
            <div class="gaa-loading-spinner">
                <div class="gaa-spinner-ring"></div>
            </div>
            <p class="gaa-loading-text">
                <?php esc_html_e('AIチャットを準備しています...', GAA_TEXT_DOMAIN); ?>
            </p>
        </div>
        <?php
    }
}