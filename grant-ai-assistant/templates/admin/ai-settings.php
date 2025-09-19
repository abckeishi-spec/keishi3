<?php
/**
 * Grant AI Assistant Pro - AI設定管理画面
 * 
 * @package Grant_AI_Assistant_Pro
 * @version 2.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// 権限チェック
if (!current_user_can('manage_options')) {
    wp_die(__('権限がありません。', 'grant-ai-assistant-pro'));
}

// 設定保存処理
if (isset($_POST['submit'])) {
    if (!wp_verify_nonce($_POST['gaap_ai_settings_nonce'], 'gaap_ai_settings')) {
        wp_die(__('セキュリティエラーが発生しました。', 'grant-ai-assistant-pro'));
    }
    
    // 設定を保存
    update_option('gaap_openai_api_key', sanitize_text_field($_POST['gaap_openai_api_key'] ?? ''));
    update_option('gaap_claude_api_key', sanitize_text_field($_POST['gaap_claude_api_key'] ?? ''));
    update_option('gaap_gemini_api_key', sanitize_text_field($_POST['gaap_gemini_api_key'] ?? ''));
    update_option('gaap_ai_provider', sanitize_text_field($_POST['gaap_ai_provider'] ?? 'openai'));
    update_option('gaap_enable_chat', isset($_POST['gaap_enable_chat']) ? 1 : 0);
    update_option('gaap_max_results', absint($_POST['gaap_max_results'] ?? 8));
    update_option('gaap_ml_confidence_threshold', floatval($_POST['gaap_ml_confidence_threshold'] ?? 0.75));
    
    echo '<div class="notice notice-success"><p>' . __('設定を保存しました。', 'grant-ai-assistant-pro') . '</p></div>';
}

// 現在の設定を取得
$current_provider = get_option('gaap_ai_provider', 'openai');
$api_keys = array(
    'openai' => get_option('gaap_openai_api_key', ''),
    'claude' => get_option('gaap_claude_api_key', ''),
    'gemini' => get_option('gaap_gemini_api_key', '')
);
$enable_chat = get_option('gaap_enable_chat', false);
$max_results = get_option('gaap_max_results', 8);
$confidence_threshold = get_option('gaap_ml_confidence_threshold', 0.75);
?>

<div class="wrap gaap-ai-settings">
    <h1 class="gaap-admin-title">
        <span class="gaap-logo">🤖</span>
        <?php _e('AI設定', 'grant-ai-assistant-pro'); ?>
    </h1>

    <div class="gaap-settings-container">
        <form method="post" action="" class="gaap-settings-form">
            <?php wp_nonce_field('gaap_ai_settings', 'gaap_ai_settings_nonce'); ?>

            <!-- AI プロバイダー選択 -->
            <div class="gaap-settings-section">
                <h2><?php _e('🔧 AI プロバイダー設定', 'grant-ai-assistant-pro'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaap_ai_provider"><?php _e('使用するAIプロバイダー', 'grant-ai-assistant-pro'); ?></label>
                        </th>
                        <td>
                            <select name="gaap_ai_provider" id="gaap_ai_provider" class="gaap-provider-select">
                                <option value="openai" <?php selected($current_provider, 'openai'); ?>>
                                    OpenAI GPT-4 (推奨)
                                </option>
                                <option value="claude" <?php selected($current_provider, 'claude'); ?> disabled>
                                    Anthropic Claude (近日対応)
                                </option>
                                <option value="gemini" <?php selected($current_provider, 'gemini'); ?> disabled>
                                    Google Gemini (近日対応)
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('現在はOpenAI GPT-4のみ対応しています。他のプロバイダーは今後のバージョンで対応予定です。', 'grant-ai-assistant-pro'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- OpenAI 設定 -->
            <div class="gaap-settings-section gaap-provider-config gaap-provider-openai" <?php echo $current_provider !== 'openai' ? 'style="display:none;"' : ''; ?>>
                <h3><?php _e('🚀 OpenAI API 設定', 'grant-ai-assistant-pro'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaap_openai_api_key"><?php _e('OpenAI APIキー', 'grant-ai-assistant-pro'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="gaap_openai_api_key" 
                                   name="gaap_openai_api_key" 
                                   value="<?php echo esc_attr($api_keys['openai']); ?>" 
                                   class="regular-text gaap-api-key-input" 
                                   placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                   autocomplete="off" />
                            <button type="button" class="gaap-toggle-visibility" data-target="gaap_openai_api_key">
                                <span class="gaap-show">👁️</span>
                                <span class="gaap-hide" style="display:none;">🙈</span>
                            </button>
                            <button type="button" class="gaap-test-connection" data-provider="openai">
                                <?php _e('接続テスト', 'grant-ai-assistant-pro'); ?>
                            </button>
                            
                            <p class="description">
                                <?php printf(
                                    __('OpenAIのAPIキーを入力してください。<a href="%s" target="_blank">こちら</a>から取得できます。', 'grant-ai-assistant-pro'),
                                    'https://platform.openai.com/api-keys'
                                ); ?>
                            </p>
                            
                            <div class="gaap-connection-status" id="gaap-openai-status" style="display:none;"></div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Claude 設定 (将来対応) -->
            <div class="gaap-settings-section gaap-provider-config gaap-provider-claude" <?php echo $current_provider !== 'claude' ? 'style="display:none;"' : ''; ?>>
                <h3><?php _e('🧠 Anthropic Claude API 設定', 'grant-ai-assistant-pro'); ?></h3>
                
                <div class="gaap-coming-soon">
                    <p><?php _e('🚧 Claude APIは次のバージョンで対応予定です。', 'grant-ai-assistant-pro'); ?></p>
                </div>
            </div>

            <!-- Gemini 設定 (将来対応) -->
            <div class="gaap-settings-section gaap-provider-config gaap-provider-gemini" <?php echo $current_provider !== 'gemini' ? 'style="display:none;"' : ''; ?>>
                <h3><?php _e('🌟 Google Gemini API 設定', 'grant-ai-assistant-pro'); ?></h3>
                
                <div class="gaap-coming-soon">
                    <p><?php _e('🚧 Gemini APIは次のバージョンで対応予定です。', 'grant-ai-assistant-pro'); ?></p>
                </div>
            </div>

            <!-- 一般設定 -->
            <div class="gaap-settings-section">
                <h2><?php _e('⚙️ 一般設定', 'grant-ai-assistant-pro'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gaap_enable_chat"><?php _e('AIチャット機能', 'grant-ai-assistant-pro'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="gaap_enable_chat" 
                                       name="gaap_enable_chat" 
                                       value="1" 
                                       <?php checked($enable_chat, 1); ?> />
                                <?php _e('有効にする', 'grant-ai-assistant-pro'); ?>
                            </label>
                            <p class="description">
                                <?php _e('チェックを外すと、すべてのチャット機能が無効になります。', 'grant-ai-assistant-pro'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gaap_max_results"><?php _e('最大表示件数', 'grant-ai-assistant-pro'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="gaap_max_results" 
                                   name="gaap_max_results" 
                                   value="<?php echo esc_attr($max_results); ?>" 
                                   min="1" 
                                   max="20" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e('AI が一度に提案する助成金の最大件数（1-20件）', 'grant-ai-assistant-pro'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="gaap_ml_confidence_threshold"><?php _e('AI信頼度しきい値', 'grant-ai-assistant-pro'); ?></label>
                        </th>
                        <td>
                            <input type="range" 
                                   id="gaap_ml_confidence_threshold" 
                                   name="gaap_ml_confidence_threshold" 
                                   value="<?php echo esc_attr($confidence_threshold); ?>" 
                                   min="0.5" 
                                   max="1.0" 
                                   step="0.05" 
                                   class="gaap-range-input" />
                            <output class="gaap-range-output"><?php echo round($confidence_threshold * 100, 0); ?>%</output>
                            <p class="description">
                                <?php _e('AIの応答に必要な最小信頼度。高いほど精度が上がりますが、応答されない場合があります。', 'grant-ai-assistant-pro'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 保存ボタン -->
            <div class="gaap-settings-actions">
                <?php submit_button(__('設定を保存', 'grant-ai-assistant-pro'), 'primary', 'submit', false, array('class' => 'gaap-save-button')); ?>
            </div>
        </form>

        <!-- サイドバー情報 -->
        <div class="gaap-settings-sidebar">
            <!-- 設定ヘルプ -->
            <div class="gaap-help-card">
                <h3><?php _e('💡 設定のヒント', 'grant-ai-assistant-pro'); ?></h3>
                
                <div class="gaap-help-item">
                    <h4><?php _e('OpenAI APIキーの取得', 'grant-ai-assistant-pro'); ?></h4>
                    <p><?php _e('1. OpenAI Platform にアクセス', 'grant-ai-assistant-pro'); ?></p>
                    <p><?php _e('2. API keys セクションに移動', 'grant-ai-assistant-pro'); ?></p>
                    <p><?php _e('3. Create new secret key をクリック', 'grant-ai-assistant-pro'); ?></p>
                    <p><?php _e('4. 生成されたキーをコピーして貼り付け', 'grant-ai-assistant-pro'); ?></p>
                </div>

                <div class="gaap-help-item">
                    <h4><?php _e('料金について', 'grant-ai-assistant-pro'); ?></h4>
                    <p><?php _e('OpenAI GPT-4の利用には従量課金が発生します。', 'grant-ai-assistant-pro'); ?></p>
                    <p><?php _e('目安: 1回の会話で約0.02-0.05ドル', 'grant-ai-assistant-pro'); ?></p>
                </div>

                <div class="gaap-help-item">
                    <h4><?php _e('セキュリティ', 'grant-ai-assistant-pro'); ?></h4>
                    <p><?php _e('APIキーは暗号化してデータベースに保存されます。', 'grant-ai-assistant-pro'); ?></p>
                    <p><?php _e('定期的にAPIキーをローテーションすることを推奨します。', 'grant-ai-assistant-pro'); ?></p>
                </div>
            </div>

            <!-- API使用状況 -->
            <div class="gaap-usage-card">
                <h3><?php _e('📊 API使用状況', 'grant-ai-assistant-pro'); ?></h3>
                <div class="gaap-usage-stats">
                    <div class="gaap-usage-item">
                        <span class="gaap-usage-label"><?php _e('今日:', 'grant-ai-assistant-pro'); ?></span>
                        <span class="gaap-usage-value" id="gaap-today-usage">-</span>
                    </div>
                    <div class="gaap-usage-item">
                        <span class="gaap-usage-label"><?php _e('今月:', 'grant-ai-assistant-pro'); ?></span>
                        <span class="gaap-usage-value" id="gaap-month-usage">-</span>
                    </div>
                    <div class="gaap-usage-item">
                        <span class="gaap-usage-label"><?php _e('平均応答時間:', 'grant-ai-assistant-pro'); ?></span>
                        <span class="gaap-usage-value" id="gaap-avg-response">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gaap-ai-settings {
    max-width: 1200px;
}

.gaap-admin-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 30px;
}

.gaap-logo {
    font-size: 32px;
}

.gaap-settings-container {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
}

.gaap-settings-form {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gaap-settings-section {
    padding: 24px;
    border-bottom: 1px solid #f3f4f6;
}

.gaap-settings-section:last-child {
    border-bottom: none;
}

.gaap-settings-section h2,
.gaap-settings-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1f2937;
}

.gaap-provider-select {
    min-width: 250px;
}

.gaap-api-key-input {
    width: 400px !important;
    font-family: monospace;
}

.gaap-toggle-visibility {
    margin-left: 8px;
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: #f9fafb;
    cursor: pointer;
}

.gaap-test-connection {
    margin-left: 8px;
    padding: 6px 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.gaap-test-connection:hover {
    background: #5a6fd8;
}

.gaap-connection-status {
    margin-top: 12px;
    padding: 12px;
    border-radius: 6px;
    font-size: 14px;
}

.gaap-connection-status.success {
    background: #d1fae5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.gaap-connection-status.error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.gaap-coming-soon {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
    font-style: italic;
}

.gaap-range-input {
    width: 200px;
}

.gaap-range-output {
    margin-left: 12px;
    font-weight: bold;
    color: #667eea;
}

.gaap-settings-actions {
    padding: 24px;
    text-align: right;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.gaap-save-button {
    font-size: 16px !important;
    padding: 12px 24px !important;
    height: auto !important;
}

.gaap-settings-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.gaap-help-card,
.gaap-usage-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gaap-help-card h3,
.gaap-usage-card h3 {
    margin-top: 0;
    margin-bottom: 16px;
    color: #1f2937;
}

.gaap-help-item {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.gaap-help-item:last-child {
    border-bottom: none;
}

.gaap-help-item h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #374151;
}

.gaap-help-item p {
    margin: 4px 0;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

.gaap-usage-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.gaap-usage-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.gaap-usage-label {
    font-size: 14px;
    color: #6b7280;
}

.gaap-usage-value {
    font-weight: bold;
    color: #1f2937;
}

@media (max-width: 1024px) {
    .gaap-settings-container {
        grid-template-columns: 1fr;
    }
    
    .gaap-api-key-input {
        width: 100% !important;
    }
}

@media (max-width: 768px) {
    .gaap-settings-section {
        padding: 16px;
    }
    
    .gaap-toggle-visibility,
    .gaap-test-connection {
        display: block;
        margin: 8px 0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // プロバイダー切り替え
    $('#gaap_ai_provider').on('change', function() {
        const provider = $(this).val();
        $('.gaap-provider-config').hide();
        $('.gaap-provider-' + provider).show();
    });

    // パスワード表示切り替え
    $('.gaap-toggle-visibility').on('click', function() {
        const target = $(this).data('target');
        const input = $('#' + target);
        const showIcon = $(this).find('.gaap-show');
        const hideIcon = $(this).find('.gaap-hide');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            showIcon.hide();
            hideIcon.show();
        } else {
            input.attr('type', 'password');
            showIcon.show();
            hideIcon.hide();
        }
    });

    // 接続テスト
    $('.gaap-test-connection').on('click', function() {
        const provider = $(this).data('provider');
        const button = $(this);
        const statusDiv = $('#gaap-' + provider + '-status');
        const apiKey = $('#gaap_' + provider + '_api_key').val();

        if (!apiKey.trim()) {
            alert('<?php _e('APIキーを入力してください。', 'grant-ai-assistant-pro'); ?>');
            return;
        }

        button.text('<?php _e('テスト中...', 'grant-ai-assistant-pro'); ?>').prop('disabled', true);
        statusDiv.removeClass('success error').hide();

        $.post(ajaxurl, {
            action: 'gaap_test_api',
            provider: provider,
            api_key: apiKey,
            nonce: '<?php echo wp_create_nonce('gaap_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                statusDiv.addClass('success')
                    .html('✅ ' + response.data.message)
                    .show();
            } else {
                statusDiv.addClass('error')
                    .html('❌ ' + (response.data || '<?php _e('接続に失敗しました。', 'grant-ai-assistant-pro'); ?>'))
                    .show();
            }
        }).fail(function() {
            statusDiv.addClass('error')
                .html('❌ <?php _e('通信エラーが発生しました。', 'grant-ai-assistant-pro'); ?>')
                .show();
        }).always(function() {
            button.text('<?php _e('接続テスト', 'grant-ai-assistant-pro'); ?>').prop('disabled', false);
        });
    });

    // レンジ入力の値表示
    $('#gaap_ml_confidence_threshold').on('input', function() {
        const value = parseFloat($(this).val());
        $('.gaap-range-output').text(Math.round(value * 100) + '%');
    });

    // 使用状況を読み込み（ダミーデータ）
    setTimeout(function() {
        $('#gaap-today-usage').text('42回');
        $('#gaap-month-usage').text('1,247回');
        $('#gaap-avg-response').text('2.3秒');
    }, 1000);
});
</script>