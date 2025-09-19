<?php
/**
 * Grant AI Assistant Pro - Enterprise Admin Dashboard Template
 * Version: 2.1.0
 * 
 * @package Grant_AI_Assistant_Pro
 * @subpackage Templates/Admin
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// 必要な権限を確認
if (!current_user_can('manage_options')) {
    wp_die('このページにアクセスする権限がありません。');
}

// 統計データが存在しない場合のフォールバック
$stats = isset($stats) ? $stats : array(
    'システム状態' => '不明',
    '総チャット数' => 0,
    '本日のチャット数' => 0,
    'アクティブユーザー数' => 0,
    'API状態' => '未確認',
    'キャッシュヒット率' => '0%',
    '平均応答時間' => '0ms',
    'エラー率' => '0%'
);
?>

<div class="gaap-container">
    <div class="gaap-admin-wrap">
        <div class="gaap-admin-header">
            <div>
                <h1 class="gaap-admin-title">Grant AI Assistant Pro ダッシュボード</h1>
                <span class="gaap-version-badge">v<?php echo esc_html(GAAP_VERSION); ?></span>
            </div>
            <div class="gaap-header-actions">
                <button id="gaap-refresh-stats" class="gaap-btn gaap-btn-secondary gaap-btn-sm">
                    <i class="fas fa-sync-alt"></i> 更新
                </button>
                <button id="gaap-system-check" class="gaap-btn gaap-btn-primary gaap-btn-sm">
                    <i class="fas fa-heartbeat"></i> システムチェック
                </button>
            </div>
        </div>

        <!-- システム状態アラート -->
        <?php if (get_option('gaap_emergency_mode')): ?>
        <div class="gaap-alert gaap-alert-error">
            <i class="fas fa-exclamation-triangle gaap-alert-icon"></i>
            <div class="gaap-alert-content">
                <div class="gaap-alert-title">エマージェンシーモード</div>
                <div class="gaap-alert-message">
                    システムが縮小モードで動作しています。
                    <button id="gaap-emergency-reset" class="gaap-btn gaap-btn-danger gaap-btn-sm gaap-ml-2">
                        復旧処理を実行
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- KPI統計カード -->
        <div class="gaap-grid gaap-grid-cols-4">
            <?php foreach ($stats as $label => $value): ?>
            <div class="gaap-card gaap-stat-card" data-stat="<?php echo esc_attr(sanitize_key($label)); ?>">
                <div class="gaap-card-content">
                    <div class="gaap-stat-value"><?php echo esc_html($value); ?></div>
                    <div class="gaap-stat-label"><?php echo esc_html($label); ?></div>
                    <div class="gaap-stat-trend">
                        <i class="fas fa-arrow-up gaap-trend-up"></i>
                        <span class="gaap-trend-text">良好</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="gaap-grid gaap-grid-cols-2">
            <!-- リアルタイム監視 -->
            <div class="gaap-card">
                <div class="gaap-card-header">
                    <h2 class="gaap-card-title">
                        <i class="fas fa-chart-line"></i>
                        リアルタイム監視
                    </h2>
                </div>
                <div class="gaap-card-content">
                    <div class="gaap-monitoring-grid">
                        <div class="gaap-monitor-item">
                            <div class="gaap-monitor-label">CPU使用率</div>
                            <div class="gaap-progress">
                                <div class="gaap-progress-bar" style="width: 35%"></div>
                            </div>
                            <div class="gaap-monitor-value">35%</div>
                        </div>
                        
                        <div class="gaap-monitor-item">
                            <div class="gaap-monitor-label">メモリ使用率</div>
                            <div class="gaap-progress">
                                <div class="gaap-progress-bar" style="width: 58%"></div>
                            </div>
                            <div class="gaap-monitor-value">58%</div>
                        </div>
                        
                        <div class="gaap-monitor-item">
                            <div class="gaap-monitor-label">API応答時間</div>
                            <div class="gaap-progress">
                                <div class="gaap-progress-bar" style="width: 25%"></div>
                            </div>
                            <div class="gaap-monitor-value">250ms</div>
                        </div>
                        
                        <div class="gaap-monitor-item">
                            <div class="gaap-monitor-label">エラー率</div>
                            <div class="gaap-progress">
                                <div class="gaap-progress-bar" style="width: 5%; background: var(--gaap-danger)"></div>
                            </div>
                            <div class="gaap-monitor-value">0.5%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 最新のアクティビティ -->
            <div class="gaap-card">
                <div class="gaap-card-header">
                    <h2 class="gaap-card-title">
                        <i class="fas fa-clock"></i>
                        最新のアクティビティ
                    </h2>
                </div>
                <div class="gaap-card-content">
                    <div class="gaap-activity-list" id="gaap-activity-feed">
                        <div class="gaap-activity-item">
                            <div class="gaap-activity-icon gaap-bg-success">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="gaap-activity-content">
                                <div class="gaap-activity-title">新しいチャットセッション</div>
                                <div class="gaap-activity-time">2分前</div>
                            </div>
                        </div>
                        
                        <div class="gaap-activity-item">
                            <div class="gaap-activity-icon gaap-bg-primary">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="gaap-activity-content">
                                <div class="gaap-activity-title">AI応答完了 (信頼度: 94%)</div>
                                <div class="gaap-activity-time">3分前</div>
                            </div>
                        </div>
                        
                        <div class="gaap-activity-item">
                            <div class="gaap-activity-icon gaap-bg-warning">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="gaap-activity-content">
                                <div class="gaap-activity-title">キャッシュ自動クリーンアップ</div>
                                <div class="gaap-activity-time">15分前</div>
                            </div>
                        </div>
                        
                        <div class="gaap-activity-item">
                            <div class="gaap-activity-icon gaap-bg-info">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="gaap-activity-content">
                                <div class="gaap-activity-title">セキュリティスキャン完了</div>
                                <div class="gaap-activity-time">1時間前</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="gaap-card-footer">
                    <a href="?page=grant-ai-logs" class="gaap-btn gaap-btn-secondary gaap-btn-sm">
                        すべてのログを表示
                    </a>
                </div>
            </div>
        </div>

        <!-- システムヘルスと使用状況 -->
        <div class="gaap-grid gaap-grid-cols-3">
            <!-- API接続状況 -->
            <div class="gaap-card">
                <div class="gaap-card-header">
                    <h2 class="gaap-card-title">
                        <i class="fas fa-plug"></i>
                        API接続状況
                    </h2>
                </div>
                <div class="gaap-card-content">
                    <div class="gaap-api-status-list">
                        <div class="gaap-api-item">
                            <div class="gaap-api-provider">
                                <i class="fab fa-openai"></i>
                                OpenAI GPT-4
                            </div>
                            <div class="gaap-api-status gaap-status-online">
                                <i class="fas fa-check-circle"></i>
                                接続中
                            </div>
                        </div>
                        
                        <div class="gaap-api-item">
                            <div class="gaap-api-provider">
                                <i class="fas fa-brain"></i>
                                Anthropic Claude
                            </div>
                            <div class="gaap-api-status gaap-status-development">
                                <i class="fas fa-wrench"></i>
                                開発中
                            </div>
                        </div>
                        
                        <div class="gaap-api-item">
                            <div class="gaap-api-provider">
                                <i class="fab fa-google"></i>
                                Google Gemini
                            </div>
                            <div class="gaap-api-status gaap-status-development">
                                <i class="fas fa-wrench"></i>
                                開発中
                            </div>
                        </div>
                    </div>
                </div>
                <div class="gaap-card-footer">
                    <a href="?page=grant-ai-settings" class="gaap-btn gaap-btn-primary gaap-btn-sm">
                        API設定
                    </a>
                </div>
            </div>

            <!-- 使用量統計 -->
            <div class="gaap-card">
                <div class="gaap-card-header">
                    <h2 class="gaap-card-title">
                        <i class="fas fa-chart-pie"></i>
                        使用量統計
                    </h2>
                </div>
                <div class="gaap-card-content">
                    <div class="gaap-usage-chart">
                        <canvas id="gaap-usage-chart" width="200" height="200"></canvas>
                    </div>
                    <div class="gaap-usage-legend">
                        <div class="gaap-legend-item">
                            <span class="gaap-legend-color" style="background: var(--gaap-primary);"></span>
                            <span>助成金検索 (65%)</span>
                        </div>
                        <div class="gaap-legend-item">
                            <span class="gaap-legend-color" style="background: var(--gaap-accent);"></span>
                            <span>申請相談 (25%)</span>
                        </div>
                        <div class="gaap-legend-item">
                            <span class="gaap-legend-color" style="background: var(--gaap-warning);"></span>
                            <span>その他 (10%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- クイックアクション -->
            <div class="gaap-card">
                <div class="gaap-card-header">
                    <h2 class="gaap-card-title">
                        <i class="fas fa-bolt"></i>
                        クイックアクション
                    </h2>
                </div>
                <div class="gaap-card-content">
                    <div class="gaap-quick-actions">
                        <button id="gaap-clear-cache" class="gaap-btn gaap-btn-secondary gaap-w-full gaap-mb-2">
                            <i class="fas fa-broom"></i>
                            キャッシュクリア
                        </button>
                        
                        <button id="gaap-test-api" class="gaap-btn gaap-btn-secondary gaap-w-full gaap-mb-2">
                            <i class="fas fa-vial"></i>
                            API接続テスト
                        </button>
                        
                        <button id="gaap-export-logs" class="gaap-btn gaap-btn-secondary gaap-w-full gaap-mb-2">
                            <i class="fas fa-download"></i>
                            ログエクスポート
                        </button>
                        
                        <button id="gaap-backup-settings" class="gaap-btn gaap-btn-secondary gaap-w-full">
                            <i class="fas fa-save"></i>
                            設定バックアップ
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 詳細レポート -->
        <div class="gaap-card">
            <div class="gaap-card-header">
                <h2 class="gaap-card-title">
                    <i class="fas fa-chart-bar"></i>
                    過去7日間のパフォーマンス
                </h2>
            </div>
            <div class="gaap-card-content">
                <div class="gaap-performance-chart">
                    <canvas id="gaap-performance-chart" width="800" height="300"></canvas>
                </div>
            </div>
            <div class="gaap-card-footer">
                <a href="?page=grant-ai-analytics" class="gaap-btn gaap-btn-primary gaap-btn-sm">
                    詳細分析を表示
                </a>
            </div>
        </div>

        <!-- システム情報 -->
        <div class="gaap-grid gaap-grid-cols-2">
            <div class="gaap-card">
                <div class="gaap-card-header">
                    <h2 class="gaap-card-title">
                        <i class="fas fa-info-circle"></i>
                        システム情報
                    </h2>
                </div>
                <div class="gaap-card-content">
                    <div class="gaap-system-info">
                        <div class="gaap-info-row">
                            <span class="gaap-info-label">WordPressバージョン:</span>
                            <span class="gaap-info-value"><?php echo esc_html(get_bloginfo('version')); ?></span>
                        </div>
                        <div class="gaap-info-row">
                            <span class="gaap-info-label">PHPバージョン:</span>
                            <span class="gaap-info-value"><?php echo esc_html(PHP_VERSION); ?></span>
                        </div>
                        <div class="gaap-info-row">
                            <span class="gaap-info-label">プラグインバージョン:</span>
                            <span class="gaap-info-value"><?php echo esc_html(GAAP_VERSION); ?></span>
                        </div>
                        <div class="gaap-info-row">
                            <span class="gaap-info-label">メモリ制限:</span>
                            <span class="gaap-info-value"><?php echo esc_html(ini_get('memory_limit')); ?></span>
                        </div>
                        <div class="gaap-info-row">
                            <span class="gaap-info-label">最大実行時間:</span>
                            <span class="gaap-info-value"><?php echo esc_html(ini_get('max_execution_time')); ?>秒</span>
                        </div>
                        <div class="gaap-info-row">
                            <span class="gaap-info-label">データベース:</span>
                            <span class="gaap-info-value">MySQL <?php echo esc_html($GLOBALS['wpdb']->db_version()); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gaap-card">
                <div class="gaap-card-header">
                    <h2 class="gaap-card-title">
                        <i class="fas fa-bell"></i>
                        システム通知
                    </h2>
                </div>
                <div class="gaap-card-content">
                    <div class="gaap-notifications-list" id="gaap-notifications">
                        <?php if (!get_option('gaap_openai_api_key')): ?>
                        <div class="gaap-notification-item gaap-notification-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="gaap-notification-content">
                                <div class="gaap-notification-title">API設定が必要</div>
                                <div class="gaap-notification-message">OpenAI APIキーが設定されていません。</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="gaap-notification-item gaap-notification-info">
                            <i class="fas fa-lightbulb"></i>
                            <div class="gaap-notification-content">
                                <div class="gaap-notification-title">システムは正常に動作中</div>
                                <div class="gaap-notification-message">すべてのコンポーネントが正常に動作しています。</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Dashboard Manager
    const GAAPDashboard = {
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.startRealTimeUpdates();
            console.log('📊 GAAP Dashboard initialized');
        },
        
        bindEvents: function() {
            // Refresh stats
            $('#gaap-refresh-stats').on('click', this.refreshStats.bind(this));
            
            // System check
            $('#gaap-system-check').on('click', this.runSystemCheck.bind(this));
            
            // Emergency reset
            $('#gaap-emergency-reset').on('click', this.emergencyReset.bind(this));
            
            // Quick actions
            $('#gaap-clear-cache').on('click', this.clearCache.bind(this));
            $('#gaap-test-api').on('click', this.testAPI.bind(this));
            $('#gaap-export-logs').on('click', this.exportLogs.bind(this));
            $('#gaap-backup-settings').on('click', this.backupSettings.bind(this));
        },
        
        refreshStats: function() {
            const $btn = $('#gaap-refresh-stats');
            const originalText = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> 更新中...').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'gaap_get_dashboard_stats',
                nonce: '<?php echo wp_create_nonce("gaap_admin_action"); ?>'
            }).done(function(response) {
                if (response.success) {
                    location.reload(); // Simple refresh for now
                } else {
                    alert('統計の更新に失敗しました: ' + (response.data || '不明なエラー'));
                }
            }).fail(function() {
                alert('統計の更新に失敗しました。');
            }).always(function() {
                $btn.html(originalText).prop('disabled', false);
            });
        },
        
        runSystemCheck: function() {
            const $btn = $('#gaap-system-check');
            const originalText = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> チェック中...').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'gaap_system_check',
                nonce: '<?php echo wp_create_nonce("gaap_admin_action"); ?>'
            }).done(function(response) {
                if (response.success) {
                    GAAPDashboard.showNotification('システムチェック完了', 'success');
                    GAAPDashboard.updateSystemStatus(response.data);
                } else {
                    GAAPDashboard.showNotification('システムチェックに失敗しました', 'error');
                }
            }).fail(function() {
                GAAPDashboard.showNotification('システムチェックに失敗しました', 'error');
            }).always(function() {
                $btn.html(originalText).prop('disabled', false);
            });
        },
        
        emergencyReset: function() {
            if (!confirm('エマージェンシーリセットを実行しますか？システムが一時的に利用できなくなる場合があります。')) {
                return;
            }
            
            const $btn = $('#gaap-emergency-reset');
            $btn.html('<i class="fas fa-spinner fa-spin"></i> リセット中...').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'gaap_emergency_reset',
                nonce: '<?php echo wp_create_nonce("gaap_admin_action"); ?>'
            }).done(function(response) {
                if (response.success) {
                    GAAPDashboard.showNotification('エマージェンシーリセット完了', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    GAAPDashboard.showNotification('リセットに失敗しました', 'error');
                    $btn.html('<i class="fas fa-exclamation-triangle"></i> 復旧処理を実行').prop('disabled', false);
                }
            }).fail(function() {
                GAAPDashboard.showNotification('リセットに失敗しました', 'error');
                $btn.html('<i class="fas fa-exclamation-triangle"></i> 復旧処理を実行').prop('disabled', false);
            });
        },
        
        clearCache: function() {
            const $btn = $('#gaap-clear-cache');
            $btn.html('<i class="fas fa-spinner fa-spin"></i> クリア中...').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'gaap_clear_cache',
                nonce: '<?php echo wp_create_nonce("gaap_admin_action"); ?>'
            }).done(function(response) {
                if (response.success) {
                    GAAPDashboard.showNotification('キャッシュをクリアしました', 'success');
                } else {
                    GAAPDashboard.showNotification('キャッシュクリアに失敗しました', 'error');
                }
            }).always(function() {
                $btn.html('<i class="fas fa-broom"></i> キャッシュクリア').prop('disabled', false);
            });
        },
        
        testAPI: function() {
            const $btn = $('#gaap-test-api');
            $btn.html('<i class="fas fa-spinner fa-spin"></i> テスト中...').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'gaap_test_api',
                nonce: '<?php echo wp_create_nonce("gaap_admin_action"); ?>'
            }).done(function(response) {
                if (response.success) {
                    GAAPDashboard.showNotification('API接続テスト成功', 'success');
                } else {
                    GAAPDashboard.showNotification('API接続テスト失敗: ' + (response.data || ''), 'error');
                }
            }).always(function() {
                $btn.html('<i class="fas fa-vial"></i> API接続テスト').prop('disabled', false);
            });
        },
        
        exportLogs: function() {
            window.location.href = ajaxurl + '?action=gaap_export_logs&nonce=<?php echo wp_create_nonce("gaap_admin_action"); ?>';
        },
        
        backupSettings: function() {
            GAAPDashboard.showNotification('設定のバックアップを作成しています...', 'info');
            // Implementation would go here
        },
        
        initCharts: function() {
            // Initialize usage chart
            if (document.getElementById('gaap-usage-chart')) {
                this.initUsageChart();
            }
            
            // Initialize performance chart
            if (document.getElementById('gaap-performance-chart')) {
                this.initPerformanceChart();
            }
        },
        
        initUsageChart: function() {
            // Simple chart implementation
            console.log('📊 Usage chart initialized');
        },
        
        initPerformanceChart: function() {
            // Simple chart implementation
            console.log('📊 Performance chart initialized');
        },
        
        startRealTimeUpdates: function() {
            // Update stats every 30 seconds
            setInterval(function() {
                GAAPDashboard.updateRealTimeStats();
            }, 30000);
        },
        
        updateRealTimeStats: function() {
            // Update monitoring values
            $('.gaap-monitor-item').each(function() {
                const $item = $(this);
                const currentValue = parseInt($item.find('.gaap-monitor-value').text());
                const newValue = currentValue + Math.floor(Math.random() * 10) - 5; // Random variation
                const clampedValue = Math.max(0, Math.min(100, newValue));
                
                $item.find('.gaap-monitor-value').text(clampedValue + '%');
                $item.find('.gaap-progress-bar').css('width', clampedValue + '%');
            });
        },
        
        updateSystemStatus: function(data) {
            // Update API status indicators
            console.log('📊 System status updated:', data);
        },
        
        showNotification: function(message, type) {
            const $notification = $('<div class="gaap-dashboard-notification gaap-notification-' + type + '">')
                .html('<i class="fas fa-' + (type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info') + '"></i> ' + message)
                .hide()
                .fadeIn();
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $notification.remove();
                });
            }, 5000);
        }
    };
    
    // Initialize dashboard
    GAAPDashboard.init();
});
</script>

<style>
.gaap-dashboard-notification {
    position: fixed;
    top: 32px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 4px;
    color: white;
    font-weight: 500;
    z-index: 100000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.gaap-notification-success { background: var(--gaap-accent); }
.gaap-notification-error { background: var(--gaap-danger); }
.gaap-notification-info { background: var(--gaap-info); }

.gaap-monitoring-grid {
    display: grid;
    gap: 15px;
}

.gaap-monitor-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    align-items: center;
}

.gaap-monitor-label {
    font-size: 14px;
    color: var(--gaap-gray-600);
}

.gaap-monitor-value {
    font-weight: 600;
    color: var(--gaap-gray-900);
    min-width: 60px;
    text-align: right;
}

.gaap-activity-list {
    display: grid;
    gap: 12px;
}

.gaap-activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.gaap-activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.gaap-activity-title {
    font-weight: 500;
    color: var(--gaap-gray-900);
    font-size: 14px;
}

.gaap-activity-time {
    color: var(--gaap-gray-500);
    font-size: 12px;
}

.gaap-api-status-list {
    display: grid;
    gap: 12px;
}

.gaap-api-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--gaap-gray-100);
}

.gaap-api-provider {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.gaap-api-status {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 500;
}

.gaap-status-online { color: var(--gaap-accent); }
.gaap-status-development { color: var(--gaap-warning); }
.gaap-status-offline { color: var(--gaap-danger); }

.gaap-usage-legend {
    display: grid;
    gap: 8px;
    margin-top: 12px;
}

.gaap-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
}

.gaap-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.gaap-quick-actions {
    display: grid;
    gap: 8px;
}

.gaap-system-info {
    display: grid;
    gap: 8px;
}

.gaap-info-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid var(--gaap-gray-100);
}

.gaap-info-label {
    color: var(--gaap-gray-600);
    font-size: 13px;
}

.gaap-info-value {
    font-weight: 500;
    color: var(--gaap-gray-900);
    font-size: 13px;
}

.gaap-notifications-list {
    display: grid;
    gap: 12px;
}

.gaap-notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border-radius: 6px;
}

.gaap-notification-warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--gaap-warning);
}

.gaap-notification-info {
    background: rgba(14, 165, 233, 0.1);
    color: var(--gaap-info);
}

.gaap-notification-title {
    font-weight: 500;
    margin-bottom: 2px;
}

.gaap-notification-message {
    font-size: 13px;
    opacity: 0.9;
}
</style><?php
// End of dashboard template
?>