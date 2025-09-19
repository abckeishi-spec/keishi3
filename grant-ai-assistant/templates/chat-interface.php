<?php
/**
 * Grant AI Assistant Pro - チャットインターフェーステンプレート
 * 
 * @package Grant_AI_Assistant_Pro
 * @version 2.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// ショートコード属性（$atts）とチャットID（$chat_id）が利用可能
$height = esc_attr($atts['height'] ?? '600px');
$width = esc_attr($atts['width'] ?? '100%');
$title = esc_html($atts['title'] ?? __('AI助成金コンシェルジュ', 'grant-ai-assistant-pro'));
$theme = esc_attr($atts['theme'] ?? 'default');
$enable_voice = filter_var($atts['enable_voice'] ?? false, FILTER_VALIDATE_BOOLEAN);
$enable_export = filter_var($atts['enable_export'] ?? true, FILTER_VALIDATE_BOOLEAN);
$max_messages = absint($atts['max_messages'] ?? 50);
?>

<div id="<?php echo esc_attr($chat_id); ?>" 
     class="gaap-chat-container gaap-theme-<?php echo $theme; ?>" 
     style="max-width: <?php echo $width; ?>; height: <?php echo $height; ?>"
     data-config="<?php echo esc_attr(json_encode([
         'maxMessages' => $max_messages,
         'enableVoice' => $enable_voice,
         'enableExport' => $enable_export,
         'theme' => $theme
     ])); ?>">

    <!-- チャットヘッダー -->
    <div class="gaap-chat-header">
        <div class="gaap-header-content">
            <h3><?php echo $title; ?></h3>
            <div class="gaap-chat-status">
                <span class="gaap-status-indicator"></span>
                <span class="gaap-status-text"><?php _e('オンライン', 'grant-ai-assistant-pro'); ?></span>
            </div>
        </div>
        
        <?php if ($enable_export): ?>
        <div class="gaap-header-actions">
            <button type="button" class="gaap-export-btn" title="<?php _e('会話をエクスポート', 'grant-ai-assistant-pro'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7,10 12,15 17,10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </button>
            
            <div class="gaap-export-menu" style="display: none;">
                <button type="button" class="gaap-export-json" data-format="json">
                    <?php _e('JSON形式', 'grant-ai-assistant-pro'); ?>
                </button>
                <button type="button" class="gaap-export-text" data-format="txt">
                    <?php _e('テキスト形式', 'grant-ai-assistant-pro'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- メッセージエリア -->
    <div class="gaap-chat-messages" 
         id="<?php echo esc_attr($chat_id); ?>-messages"
         role="log"
         aria-live="polite"
         aria-label="<?php _e('会話履歴', 'grant-ai-assistant-pro'); ?>">
        
        <!-- 初期メッセージはJavaScriptで動的に追加 -->
    </div>

    <!-- 入力エリア -->
    <div class="gaap-chat-input">
        <div class="gaap-input-wrapper">
            <input type="text" 
                   id="<?php echo esc_attr($chat_id); ?>-input"
                   placeholder="<?php _e('助成金について質問してください...', 'grant-ai-assistant-pro'); ?>"
                   maxlength="1000"
                   autocomplete="off"
                   aria-label="<?php _e('メッセージを入力', 'grant-ai-assistant-pro'); ?>" />
            
            <button type="button" 
                    id="<?php echo esc_attr($chat_id); ?>-send" 
                    class="gaap-send-button"
                    aria-label="<?php _e('メッセージを送信', 'grant-ai-assistant-pro'); ?>"
                    disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22,2 15,22 11,13 2,9"/>
                </svg>
            </button>
        </div>

        <?php if ($enable_voice): ?>
        <button type="button" 
                class="gaap-voice-button"
                aria-label="<?php _e('音声入力', 'grant-ai-assistant-pro'); ?>"
                aria-pressed="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 1a4 4 0 0 0-4 4v7a4 4 0 0 0 8 0V5a4 4 0 0 0-4-4z"/>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                <line x1="12" y1="19" x2="12" y2="23"/>
                <line x1="8" y1="23" x2="16" y2="23"/>
            </svg>
        </button>
        <?php endif; ?>
    </div>

    <!-- 提案ボタンエリア（動的に生成） -->
    <!-- JavaScriptで動的に追加される -->
    
    <!-- 文字数カウンター（JavaScriptで動的に追加） -->
    
    <!-- ローディング・エラー表示用（JavaScriptで動的に追加） -->
</div>

<!-- スクリーンリーダー用のライブリージョン -->
<div id="<?php echo esc_attr($chat_id); ?>-announcements" 
     class="gaap-sr-only" 
     aria-live="assertive" 
     aria-atomic="true">
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // チャットIDを取得
    const chatId = '<?php echo esc_js($chat_id); ?>';
    
    // GAAP Pro Chat インスタンスを作成
    if (window.GAAP && typeof window.GAAP.createChat === 'function') {
        const chatConfig = {
            ...gaapConfig.settings,
            enableVoice: <?php echo $enable_voice ? 'true' : 'false'; ?>,
            enableExport: <?php echo $enable_export ? 'true' : 'false'; ?>,
            maxMessages: <?php echo $max_messages; ?>,
            theme: '<?php echo esc_js($theme); ?>'
        };
        
        try {
            const chatInstance = window.GAAP.createChat(chatId, chatConfig);
            
            if (chatInstance) {
                // エクスポート機能の設定
                <?php if ($enable_export): ?>
                setupExportFunctionality(chatInstance);
                <?php endif; ?>
                
                // 音声機能の設定
                <?php if ($enable_voice): ?>
                setupVoiceFunctionality(chatInstance);
                <?php endif; ?>
                
                console.log('GAAP Chat instance created successfully:', chatId);
            } else {
                console.error('Failed to create GAAP Chat instance:', chatId);
                showFallbackMessage();
            }
        } catch (error) {
            console.error('Error creating GAAP Chat instance:', error);
            showFallbackMessage();
        }
    } else {
        console.error('GAAP Chat system not available');
        showFallbackMessage();
    }
    
    /**
     * エクスポート機能セットアップ
     */
    function setupExportFunctionality(chatInstance) {
        const exportBtn = document.querySelector(`#${chatId} .gaap-export-btn`);
        const exportMenu = document.querySelector(`#${chatId} .gaap-export-menu`);
        
        if (exportBtn && exportMenu) {
            exportBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                exportMenu.style.display = exportMenu.style.display === 'none' ? 'block' : 'none';
            });
            
            // メニュー外クリックで閉じる
            document.addEventListener('click', function() {
                exportMenu.style.display = 'none';
            });
            
            // エクスポート実行
            exportMenu.addEventListener('click', function(e) {
                if (e.target.matches('[data-format]')) {
                    const format = e.target.getAttribute('data-format');
                    chatInstance.exportConversation(format);
                    exportMenu.style.display = 'none';
                }
            });
        }
    }
    
    /**
     * 音声機能セットアップ
     */
    function setupVoiceFunctionality(chatInstance) {
        // 音声機能は GAAPProChat クラス内で自動的にセットアップされる
        console.log('Voice functionality enabled for chat:', chatId);
    }
    
    /**
     * フォールバック表示
     */
    function showFallbackMessage() {
        const container = document.getElementById(chatId);
        if (container) {
            container.innerHTML = `
                <div class="gaap-fallback-message">
                    <div class="gaap-fallback-icon">⚠️</div>
                    <div class="gaap-fallback-content">
                        <h3><?php _e('チャット機能が利用できません', 'grant-ai-assistant-pro'); ?></h3>
                        <p><?php _e('申し訳ございませんが、現在チャット機能を利用できません。しばらく時間をおいて再度お試しください。', 'grant-ai-assistant-pro'); ?></p>
                        <?php if (current_user_can('manage_options')): ?>
                        <p><a href="<?php echo admin_url('admin.php?page=gaap-ai-settings'); ?>" class="gaap-fallback-link">
                            <?php _e('管理者の方は設定画面をご確認ください', 'grant-ai-assistant-pro'); ?>
                        </a></p>
                        <?php endif; ?>
                    </div>
                </div>
            `;
        }
    }
});
</script>

<style>
/* チャットインターフェース専用のスタイル調整 */
.gaap-chat-container {
    /* メインのスタイルはstyle.cssで定義済み */
    position: relative;
}

.gaap-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.gaap-header-actions {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.gaap-export-btn {
    padding: 8px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.gaap-export-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.gaap-export-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 4px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    min-width: 120px;
}

.gaap-export-menu button {
    display: block;
    width: 100%;
    padding: 8px 12px;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
    transition: background-color 0.2s;
}

.gaap-export-menu button:hover {
    background: #f3f4f6;
}

.gaap-export-menu button:first-child {
    border-radius: 6px 6px 0 0;
}

.gaap-export-menu button:last-child {
    border-radius: 0 0 6px 6px;
}

.gaap-input-wrapper {
    position: relative;
    flex: 1;
}

.gaap-fallback-message {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 40px 24px;
    text-align: center;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin: 20px 0;
}

.gaap-fallback-icon {
    font-size: 48px;
    flex-shrink: 0;
}

.gaap-fallback-content h3 {
    margin: 0 0 12px 0;
    color: #374151;
    font-size: 18px;
}

.gaap-fallback-content p {
    margin: 0 0 8px 0;
    color: #6b7280;
    line-height: 1.5;
}

.gaap-fallback-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.gaap-fallback-link:hover {
    text-decoration: underline;
}

/* テーマ別カスタマイズ */
.gaap-theme-dark {
    --gaap-white: #1f2937;
    --gaap-gray-50: #374151;
    --gaap-gray-100: #4b5563;
    --gaap-gray-800: #ffffff;
}

.gaap-theme-compact .gaap-chat-messages {
    padding: 12px;
}

.gaap-theme-compact .gaap-message-content {
    padding: 8px 12px;
}

.gaap-theme-minimal .gaap-chat-header {
    background: #f9fafb;
    color: #374151;
}

.gaap-theme-minimal .gaap-status-indicator {
    display: none;
}

/* レスポンシブ調整 */
@media (max-width: 768px) {
    .gaap-header-content {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
    
    .gaap-header-actions {
        order: -1;
        align-self: flex-end;
    }
    
    .gaap-fallback-message {
        flex-direction: column;
        text-align: center;
        padding: 24px 16px;
    }
    
    .gaap-fallback-icon {
        font-size: 36px;
    }
}

/* アクセシビリティ強化 */
@media (prefers-reduced-motion: reduce) {
    .gaap-export-btn,
    .gaap-export-menu button {
        transition: none;
    }
}

/* 高コントラストモード */
@media (prefers-contrast: high) {
    .gaap-export-menu {
        border-width: 2px;
    }
    
    .gaap-fallback-message {
        border-width: 2px;
    }
}
</style>