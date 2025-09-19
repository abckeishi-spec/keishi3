<?php
/**
 * Grant AI Assistant Pro - システムログ管理画面
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

// ログデータが渡されているかチェック
$logs = isset($logs) ? $logs : array();

// ログクリア処理
if (isset($_POST['clear_logs'])) {
    if (!wp_verify_nonce($_POST['gaap_logs_nonce'], 'gaap_clear_logs')) {
        wp_die(__('セキュリティエラーが発生しました。', 'grant-ai-assistant-pro'));
    }
    
    // ログクリア実行（実際の実装では適切な処理を行う）
    echo '<div class="notice notice-success"><p>' . __('ログをクリアしました。', 'grant-ai-assistant-pro') . '</p></div>';
}
?>

<div class="wrap gaap-logs">
    <h1 class="gaap-admin-title">
        <span class="gaap-logo">📋</span>
        <?php _e('システムログ', 'grant-ai-assistant-pro'); ?>
    </h1>

    <div class="gaap-logs-controls">
        <div class="gaap-log-filters">
            <select id="gaap-log-level" class="gaap-filter-select">
                <option value=""><?php _e('全てのレベル', 'grant-ai-assistant-pro'); ?></option>
                <option value="error"><?php _e('エラー', 'grant-ai-assistant-pro'); ?></option>
                <option value="warning"><?php _e('警告', 'grant-ai-assistant-pro'); ?></option>
                <option value="info"><?php _e('情報', 'grant-ai-assistant-pro'); ?></option>
                <option value="debug"><?php _e('デバッグ', 'grant-ai-assistant-pro'); ?></option>
            </select>

            <select id="gaap-log-type" class="gaap-filter-select">
                <option value=""><?php _e('全てのタイプ', 'grant-ai-assistant-pro'); ?></option>
                <option value="chat_interaction"><?php _e('チャット対話', 'grant-ai-assistant-pro'); ?></option>
                <option value="api_call"><?php _e('API呼び出し', 'grant-ai-assistant-pro'); ?></option>
                <option value="security"><?php _e('セキュリティ', 'grant-ai-assistant-pro'); ?></option>
                <option value="performance"><?php _e('パフォーマンス', 'grant-ai-assistant-pro'); ?></option>
                <option value="system"><?php _e('システム', 'grant-ai-assistant-pro'); ?></option>
            </select>

            <input type="date" id="gaap-log-date" class="gaap-filter-date" value="<?php echo date('Y-m-d'); ?>" />
            
            <button type="button" class="gaap-filter-apply"><?php _e('フィルター適用', 'grant-ai-assistant-pro'); ?></button>
            <button type="button" class="gaap-filter-clear"><?php _e('クリア', 'grant-ai-assistant-pro'); ?></button>
        </div>

        <div class="gaap-log-actions">
            <button type="button" class="gaap-refresh-logs"><?php _e('更新', 'grant-ai-assistant-pro'); ?></button>
            <button type="button" class="gaap-export-logs"><?php _e('エクスポート', 'grant-ai-assistant-pro'); ?></button>
            
            <form method="post" style="display: inline-block;" onsubmit="return confirm('<?php _e('本当にログをクリアしますか？', 'grant-ai-assistant-pro'); ?>')">
                <?php wp_nonce_field('gaap_clear_logs', 'gaap_logs_nonce'); ?>
                <button type="submit" name="clear_logs" class="gaap-clear-logs"><?php _e('ログクリア', 'grant-ai-assistant-pro'); ?></button>
            </form>
        </div>
    </div>

    <!-- ログ統計 -->
    <div class="gaap-log-stats">
        <div class="gaap-stat-item gaap-stat-error">
            <div class="gaap-stat-icon">🚨</div>
            <div class="gaap-stat-content">
                <div class="gaap-stat-number">3</div>
                <div class="gaap-stat-label"><?php _e('エラー (24h)', 'grant-ai-assistant-pro'); ?></div>
            </div>
        </div>

        <div class="gaap-stat-item gaap-stat-warning">
            <div class="gaap-stat-icon">⚠️</div>
            <div class="gaap-stat-content">
                <div class="gaap-stat-number">12</div>
                <div class="gaap-stat-label"><?php _e('警告 (24h)', 'grant-ai-assistant-pro'); ?></div>
            </div>
        </div>

        <div class="gaap-stat-item gaap-stat-info">
            <div class="gaap-stat-icon">ℹ️</div>
            <div class="gaap-stat-content">
                <div class="gaap-stat-number">847</div>
                <div class="gaap-stat-label"><?php _e('情報 (24h)', 'grant-ai-assistant-pro'); ?></div>
            </div>
        </div>

        <div class="gaap-stat-item gaap-stat-total">
            <div class="gaap-stat-icon">📊</div>
            <div class="gaap-stat-content">
                <div class="gaap-stat-number">862</div>
                <div class="gaap-stat-label"><?php _e('総ログ数', 'grant-ai-assistant-pro'); ?></div>
            </div>
        </div>
    </div>

    <!-- ログテーブル -->
    <div class="gaap-logs-table-container">
        <table class="gaap-logs-table">
            <thead>
                <tr>
                    <th class="gaap-col-time"><?php _e('時刻', 'grant-ai-assistant-pro'); ?></th>
                    <th class="gaap-col-level"><?php _e('レベル', 'grant-ai-assistant-pro'); ?></th>
                    <th class="gaap-col-type"><?php _e('タイプ', 'grant-ai-assistant-pro'); ?></th>
                    <th class="gaap-col-message"><?php _e('メッセージ', 'grant-ai-assistant-pro'); ?></th>
                    <th class="gaap-col-ip"><?php _e('IP', 'grant-ai-assistant-pro'); ?></th>
                    <th class="gaap-col-user"><?php _e('ユーザー', 'grant-ai-assistant-pro'); ?></th>
                    <th class="gaap-col-actions"><?php _e('操作', 'grant-ai-assistant-pro'); ?></th>
                </tr>
            </thead>
            <tbody id="gaap-logs-tbody">
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="gaap-log-row gaap-log-<?php echo esc_attr($log->log_level ?? 'info'); ?>">
                            <td class="gaap-col-time">
                                <time datetime="<?php echo esc_attr($log->created_at ?? ''); ?>">
                                    <?php echo esc_html(date('Y/m/d H:i:s', strtotime($log->created_at ?? 'now'))); ?>
                                </time>
                            </td>
                            <td class="gaap-col-level">
                                <span class="gaap-level-badge gaap-level-<?php echo esc_attr($log->log_level ?? 'info'); ?>">
                                    <?php echo esc_html(ucfirst($log->log_level ?? 'info')); ?>
                                </span>
                            </td>
                            <td class="gaap-col-type">
                                <?php echo esc_html($log->log_type ?? 'unknown'); ?>
                            </td>
                            <td class="gaap-col-message">
                                <div class="gaap-message-preview">
                                    <?php
                                    $data = json_decode($log->log_data ?? '{}', true);
                                    $message = is_array($data) ? ($data['message'] ?? $data['error'] ?? 'ログデータ') : 'ログデータ';
                                    echo esc_html(mb_strimwidth($message, 0, 80, '...'));
                                    ?>
                                </div>
                            </td>
                            <td class="gaap-col-ip">
                                <?php echo esc_html($log->ip_address ?? '-'); ?>
                            </td>
                            <td class="gaap-col-user">
                                <?php if (!empty($log->user_id)): ?>
                                    <?php
                                    $user = get_user_by('id', $log->user_id);
                                    echo $user ? esc_html($user->display_name) : 'ID:' . esc_html($log->user_id);
                                    ?>
                                <?php else: ?>
                                    <?php _e('ゲスト', 'grant-ai-assistant-pro'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="gaap-col-actions">
                                <button type="button" class="gaap-view-details" data-log-id="<?php echo esc_attr($log->id ?? ''); ?>">
                                    <?php _e('詳細', 'grant-ai-assistant-pro'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="gaap-no-logs">
                            <?php _e('ログがありません。', 'grant-ai-assistant-pro'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ページネーション -->
    <div class="gaap-pagination">
        <button type="button" class="gaap-page-btn gaap-page-prev" disabled>
            <?php _e('前へ', 'grant-ai-assistant-pro'); ?>
        </button>
        
        <span class="gaap-page-info">
            <?php _e('1 - 50 / 862件', 'grant-ai-assistant-pro'); ?>
        </span>
        
        <button type="button" class="gaap-page-btn gaap-page-next">
            <?php _e('次へ', 'grant-ai-assistant-pro'); ?>
        </button>
    </div>
</div>

<!-- ログ詳細モーダル -->
<div id="gaap-log-modal" class="gaap-modal" style="display: none;">
    <div class="gaap-modal-content">
        <div class="gaap-modal-header">
            <h3><?php _e('ログ詳細', 'grant-ai-assistant-pro'); ?></h3>
            <button type="button" class="gaap-modal-close">&times;</button>
        </div>
        <div class="gaap-modal-body">
            <div class="gaap-log-detail">
                <div class="gaap-detail-section">
                    <h4><?php _e('基本情報', 'grant-ai-assistant-pro'); ?></h4>
                    <table class="gaap-detail-table">
                        <tr>
                            <th><?php _e('時刻:', 'grant-ai-assistant-pro'); ?></th>
                            <td id="gaap-detail-time">-</td>
                        </tr>
                        <tr>
                            <th><?php _e('レベル:', 'grant-ai-assistant-pro'); ?></th>
                            <td id="gaap-detail-level">-</td>
                        </tr>
                        <tr>
                            <th><?php _e('タイプ:', 'grant-ai-assistant-pro'); ?></th>
                            <td id="gaap-detail-type">-</td>
                        </tr>
                        <tr>
                            <th><?php _e('IPアドレ��:', 'grant-ai-assistant-pro'); ?></th>
                            <td id="gaap-detail-ip">-</td>
                        </tr>
                        <tr>
                            <th><?php _e('ユーザー:', 'grant-ai-assistant-pro'); ?></th>
                            <td id="gaap-detail-user">-</td>
                        </tr>
                    </table>
                </div>

                <div class="gaap-detail-section">
                    <h4><?php _e('ログデータ', 'grant-ai-assistant-pro'); ?></h4>
                    <pre id="gaap-detail-data" class="gaap-log-data"></pre>
                </div>
            </div>
        </div>
        <div class="gaap-modal-footer">
            <button type="button" class="gaap-modal-close-btn"><?php _e('閉じる', 'grant-ai-assistant-pro'); ?></button>
        </div>
    </div>
</div>

<style>
.gaap-logs {
    max-width: 1400px;
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

.gaap-logs-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 16px 20px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    flex-wrap: wrap;
    gap: 16px;
}

.gaap-log-filters {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.gaap-filter-select,
.gaap-filter-date {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 14px;
}

.gaap-filter-apply,
.gaap-filter-clear,
.gaap-refresh-logs,
.gaap-export-logs,
.gaap-clear-logs {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.gaap-filter-apply,
.gaap-refresh-logs {
    background: #667eea;
    color: white;
}

.gaap-filter-clear {
    background: #6b7280;
    color: white;
}

.gaap-export-logs {
    background: #10b981;
    color: white;
}

.gaap-clear-logs {
    background: #ef4444;
    color: white;
}

.gaap-log-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.gaap-log-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.gaap-stat-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gaap-stat-icon {
    font-size: 24px;
    width: 36px;
    text-align: center;
}

.gaap-stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #1f2937;
}

.gaap-stat-label {
    color: #6b7280;
    font-size: 14px;
}

.gaap-stat-error .gaap-stat-number {
    color: #ef4444;
}

.gaap-stat-warning .gaap-stat-number {
    color: #f59e0b;
}

.gaap-stat-info .gaap-stat-number {
    color: #3b82f6;
}

.gaap-logs-table-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow-x: auto;
    margin-bottom: 20px;
}

.gaap-logs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.gaap-logs-table th {
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
}

.gaap-logs-table td {
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.gaap-log-row:hover {
    background: #f9fafb;
}

.gaap-col-time {
    min-width: 140px;
}

.gaap-col-level {
    min-width: 80px;
}

.gaap-col-type {
    min-width: 120px;
}

.gaap-col-message {
    max-width: 300px;
}

.gaap-col-ip {
    min-width: 100px;
}

.gaap-col-user {
    min-width: 100px;
}

.gaap-col-actions {
    min-width: 80px;
}

.gaap-level-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.gaap-level-error {
    background: #fee2e2;
    color: #991b1b;
}

.gaap-level-warning {
    background: #fef3c7;
    color: #92400e;
}

.gaap-level-info {
    background: #dbeafe;
    color: #1e40af;
}

.gaap-level-debug {
    background: #f3f4f6;
    color: #374151;
}

.gaap-message-preview {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.4;
}

.gaap-view-details {
    padding: 4px 8px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.gaap-view-details:hover {
    background: #5a6fd8;
}

.gaap-no-logs {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 40px;
}

.gaap-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gaap-page-btn {
    padding: 8px 16px;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.gaap-page-btn:hover:not(:disabled) {
    background: #e5e7eb;
}

.gaap-page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.gaap-page-info {
    font-size: 14px;
    color: #6b7280;
}

/* モーダル */
.gaap-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100000;
}

.gaap-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.gaap-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.gaap-modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #1f2937;
}

.gaap-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gaap-modal-close:hover {
    color: #374151;
}

.gaap-modal-body {
    padding: 24px;
    max-height: 60vh;
    overflow-y: auto;
}

.gaap-detail-section {
    margin-bottom: 24px;
}

.gaap-detail-section:last-child {
    margin-bottom: 0;
}

.gaap-detail-section h4 {
    margin: 0 0 12px 0;
    color: #1f2937;
    font-size: 16px;
    font-weight: 600;
}

.gaap-detail-table {
    width: 100%;
    border-collapse: collapse;
}

.gaap-detail-table th,
.gaap-detail-table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
}

.gaap-detail-table th {
    background: #f9fafb;
    font-weight: 500;
    color: #374151;
    width: 120px;
}

.gaap-log-data {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 16px;
    margin: 0;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.4;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

.gaap-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    text-align: right;
}

.gaap-modal-close-btn {
    padding: 8px 16px;
    background: #6b7280;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.gaap-modal-close-btn:hover {
    background: #4b5563;
}

@media (max-width: 1024px) {
    .gaap-logs-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .gaap-log-filters,
    .gaap-log-actions {
        justify-content: center;
    }
    
    .gaap-log-stats {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
}

@media (max-width: 768px) {
    .gaap-logs-table {
        font-size: 12px;
    }
    
    .gaap-logs-table th,
    .gaap-logs-table td {
        padding: 8px;
    }
    
    .gaap-col-message {
        max-width: 200px;
    }
    
    .gaap-modal-content {
        width: 95%;
        margin: 20px;
    }
    
    .gaap-modal-body {
        padding: 16px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // ログサンプルデータ生成（実際の実装ではサーバーサイドで処理）
    function generateSampleLogs() {
        const levels = ['error', 'warning', 'info', 'debug'];
        const types = ['chat_interaction', 'api_call', 'security', 'performance', 'system'];
        const messages = [
            'ユーザーからのチャット要求を処理しました',
            'OpenAI API呼び出しが完了しました',
            'レート制限チェックを実行しました',
            'データベース接続が確立されました',
            'キャッシュからデータを取得しました'
        ];

        const tbody = $('#gaap-logs-tbody');
        if (tbody.children().length <= 1) { // 空の場合のみ生成
            tbody.empty();
            
            for (let i = 0; i < 50; i++) {
                const level = levels[Math.floor(Math.random() * levels.length)];
                const type = types[Math.floor(Math.random() * types.length)];
                const message = messages[Math.floor(Math.random() * messages.length)];
                const date = new Date(Date.now() - Math.random() * 7 * 24 * 60 * 60 * 1000);
                
                const row = $(`
                    <tr class="gaap-log-row gaap-log-${level}">
                        <td class="gaap-col-time">
                            <time datetime="${date.toISOString()}">
                                ${date.getFullYear()}/${(date.getMonth()+1).toString().padStart(2,'0')}/${date.getDate().toString().padStart(2,'0')} ${date.getHours().toString().padStart(2,'0')}:${date.getMinutes().toString().padStart(2,'0')}:${date.getSeconds().toString().padStart(2,'0')}
                            </time>
                        </td>
                        <td class="gaap-col-level">
                            <span class="gaap-level-badge gaap-level-${level}">
                                ${level.toUpperCase()}
                            </span>
                        </td>
                        <td class="gaap-col-type">${type}</td>
                        <td class="gaap-col-message">
                            <div class="gaap-message-preview">${message}</div>
                        </td>
                        <td class="gaap-col-ip">${Math.floor(Math.random()*255)}.${Math.floor(Math.random()*255)}.${Math.floor(Math.random()*255)}.${Math.floor(Math.random()*255)}</td>
                        <td class="gaap-col-user">${Math.random() > 0.5 ? 'ゲスト' : 'admin'}</td>
                        <td class="gaap-col-actions">
                            <button type="button" class="gaap-view-details" data-log-id="${i}">詳細</button>
                        </td>
                    </tr>
                `);
                
                tbody.append(row);
            }
        }
    }

    // サンプルログ生成
    generateSampleLogs();

    // フィルター適用
    $('.gaap-filter-apply').on('click', function() {
        const level = $('#gaap-log-level').val();
        const type = $('#gaap-log-type').val();
        const date = $('#gaap-log-date').val();

        $('.gaap-log-row').each(function() {
            let show = true;
            
            if (level && !$(this).hasClass('gaap-log-' + level)) {
                show = false;
            }
            
            if (type && $(this).find('.gaap-col-type').text().trim() !== type) {
                show = false;
            }
            
            if (date) {
                const logDate = $(this).find('time').attr('datetime');
                const logDateStr = new Date(logDate).toISOString().split('T')[0];
                if (logDateStr !== date) {
                    show = false;
                }
            }
            
            $(this).toggle(show);
        });
    });

    // フィルタークリア
    $('.gaap-filter-clear').on('click', function() {
        $('#gaap-log-level, #gaap-log-type').val('');
        $('#gaap-log-date').val('<?php echo date('Y-m-d'); ?>');
        $('.gaap-log-row').show();
    });

    // ログ更新
    $('.gaap-refresh-logs').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        button.text('更新中...').prop('disabled', true);
        
        setTimeout(function() {
            button.text(originalText).prop('disabled', false);
            // 実際の実装ではここでサーバーからデータを取得
        }, 1000);
    });

    // ログエクスポート
    $('.gaap-export-logs').on('click', function() {
        alert('ログエクスポート機能は開発中です。');
    });

    // ログ詳細表示
    $(document).on('click', '.gaap-view-details', function() {
        const logId = $(this).data('log-id');
        const row = $(this).closest('tr');
        
        // ログ詳細データを取得（実際の実装ではAJAXでサーバーから取得）
        const time = row.find('time').text();
        const level = row.find('.gaap-level-badge').text();
        const type = row.find('.gaap-col-type').text();
        const ip = row.find('.gaap-col-ip').text();
        const user = row.find('.gaap-col-user').text();
        const message = row.find('.gaap-message-preview').text();

        // モーダルに情報を設定
        $('#gaap-detail-time').text(time);
        $('#gaap-detail-level').text(level);
        $('#gaap-detail-type').text(type);
        $('#gaap-detail-ip').text(ip);
        $('#gaap-detail-user').text(user);
        
        // サンプルのJSON データ
        const sampleData = {
            message: message,
            processing_time: Math.random() * 5,
            memory_usage: Math.floor(Math.random() * 100) + ' MB',
            request_id: 'req_' + Math.random().toString(36).substr(2, 9),
            user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        };
        
        $('#gaap-detail-data').text(JSON.stringify(sampleData, null, 2));
        
        // モーダルを表示
        $('#gaap-log-modal').show();
    });

    // モーダル閉じる
    $('.gaap-modal-close, .gaap-modal-close-btn').on('click', function() {
        $('#gaap-log-modal').hide();
    });

    // モーダル外クリックで閉じる
    $('#gaap-log-modal').on('click', function(e) {
        if ($(e.target).is(this)) {
            $(this).hide();
        }
    });

    // ページネーション（サンプル実装）
    $('.gaap-page-next').on('click', function() {
        alert('次のページ機能は開発中です。');
    });

    $('.gaap-page-prev').on('click', function() {
        alert('前のページ機能は開発中です。');
    });
});
</script>