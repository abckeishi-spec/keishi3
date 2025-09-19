<?php
/**
 * Grant AI Assistant Pro - 分析レポート管理画面
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

// 分析データが渡されているかチェック
$analytics_data = isset($analytics_data) ? $analytics_data : array();
?>

<div class="wrap gaap-analytics">
    <h1 class="gaap-admin-title">
        <span class="gaap-logo">📊</span>
        <?php _e('分析レポート', 'grant-ai-assistant-pro'); ?>
    </h1>

    <div class="gaap-analytics-controls">
        <div class="gaap-date-range">
            <label for="gaap-date-from"><?php _e('期間:', 'grant-ai-assistant-pro'); ?></label>
            <input type="date" id="gaap-date-from" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" />
            <span><?php _e('〜', 'grant-ai-assistant-pro'); ?></span>
            <input type="date" id="gaap-date-to" value="<?php echo date('Y-m-d'); ?>" />
            <button type="button" class="gaap-refresh-data"><?php _e('更新', 'grant-ai-assistant-pro'); ?></button>
        </div>
        
        <div class="gaap-export-actions">
            <button type="button" class="gaap-export-csv"><?php _e('CSV出力', 'grant-ai-assistant-pro'); ?></button>
            <button type="button" class="gaap-export-pdf"><?php _e('PDF出力', 'grant-ai-assistant-pro'); ?></button>
        </div>
    </div>

    <div class="gaap-analytics-grid">
        <!-- KPI概要 -->
        <div class="gaap-kpi-summary">
            <div class="gaap-kpi-card">
                <div class="gaap-kpi-icon">💬</div>
                <div class="gaap-kpi-content">
                    <div class="gaap-kpi-number">1,247</div>
                    <div class="gaap-kpi-label"><?php _e('総対話数', 'grant-ai-assistant-pro'); ?></div>
                    <div class="gaap-kpi-change gaap-positive">+15.3%</div>
                </div>
            </div>

            <div class="gaap-kpi-card">
                <div class="gaap-kpi-icon">👥</div>
                <div class="gaap-kpi-content">
                    <div class="gaap-kpi-number">823</div>
                    <div class="gaap-kpi-label"><?php _e('ユニークユーザー', 'grant-ai-assistant-pro'); ?></div>
                    <div class="gaap-kpi-change gaap-positive">+8.7%</div>
                </div>
            </div>

            <div class="gaap-kpi-card">
                <div class="gaap-kpi-icon">⭐</div>
                <div class="gaap-kpi-content">
                    <div class="gaap-kpi-number">87.2%</div>
                    <div class="gaap-kpi-label"><?php _e('満足度', 'grant-ai-assistant-pro'); ?></div>
                    <div class="gaap-kpi-change gaap-positive">+2.1%</div>
                </div>
            </div>

            <div class="gaap-kpi-card">
                <div class="gaap-kpi-icon">⚡</div>
                <div class="gaap-kpi-content">
                    <div class="gaap-kpi-number">1.8秒</div>
                    <div class="gaap-kpi-label"><?php _e('平均応答時間', 'grant-ai-assistant-pro'); ?></div>
                    <div class="gaap-kpi-change gaap-negative">+0.2秒</div>
                </div>
            </div>
        </div>

        <!-- 対話数推移グラフ -->
        <div class="gaap-chart-section">
            <h3><?php _e('📈 対話数推移 (過去30日)', 'grant-ai-assistant-pro'); ?></h3>
            <div class="gaap-chart-container">
                <canvas id="gaap-interactions-chart" width="800" height="400"></canvas>
            </div>
        </div>

        <!-- 意図分布グラフ -->
        <div class="gaap-chart-section">
            <h3><?php _e('🎯 質問意図分布', 'grant-ai-assistant-pro'); ?></h3>
            <div class="gaap-chart-container">
                <canvas id="gaap-intent-chart" width="400" height="400"></canvas>
            </div>
        </div>

        <!-- 人気キーワード -->
        <div class="gaap-keywords-section">
            <h3><?php _e('🔍 人気キーワード', 'grant-ai-assistant-pro'); ?></h3>
            
            <div class="gaap-keyword-cloud">
                <span class="gaap-keyword gaap-size-xl">スタートアップ</span>
                <span class="gaap-keyword gaap-size-lg">設備投資</span>
                <span class="gaap-keyword gaap-size-md">研究開発</span>
                <span class="gaap-keyword gaap-size-lg">IT導入</span>
                <span class="gaap-keyword gaap-size-sm">人材育成</span>
                <span class="gaap-keyword gaap-size-md">デジタル化</span>
                <span class="gaap-keyword gaap-size-xl">補助金</span>
                <span class="gaap-keyword gaap-size-sm">雇用創出</span>
                <span class="gaap-keyword gaap-size-md">地域振興</span>
                <span class="gaap-keyword gaap-size-lg">新事業</span>
                <span class="gaap-keyword gaap-size-sm">環境対応</span>
                <span class="gaap-keyword gaap-size-md">働き方改革</span>
            </div>
        </div>

        <!-- 時間帯別利用状況 -->
        <div class="gaap-heatmap-section">
            <h3><?php _e('🕐 時間帯別利用状況', 'grant-ai-assistant-pro'); ?></h3>
            
            <div class="gaap-heatmap">
                <div class="gaap-heatmap-labels">
                    <div class="gaap-days">
                        <span>月</span><span>火</span><span>水</span><span>木</span><span>金</span><span>土</span><span>日</span>
                    </div>
                    <div class="gaap-hours">
                        <?php for($h = 0; $h < 24; $h++): ?>
                            <span><?php echo sprintf('%02d', $h); ?></span>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="gaap-heatmap-grid" id="gaap-heatmap-grid">
                    <!-- ヒートマップは JavaScript で生成 -->
                </div>
            </div>

            <div class="gaap-heatmap-legend">
                <span><?php _e('低', 'grant-ai-assistant-pro'); ?></span>
                <div class="gaap-legend-scale">
                    <span class="gaap-legend-color gaap-level-0"></span>
                    <span class="gaap-legend-color gaap-level-1"></span>
                    <span class="gaap-legend-color gaap-level-2"></span>
                    <span class="gaap-legend-color gaap-level-3"></span>
                    <span class="gaap-legend-color gaap-level-4"></span>
                </div>
                <span><?php _e('高', 'grant-ai-assistant-pro'); ?></span>
            </div>
        </div>

        <!-- 助成金推奨精度 -->
        <div class="gaap-accuracy-section">
            <h3><?php _e('🎯 助成金推奨精度', 'grant-ai-assistant-pro'); ?></h3>
            
            <div class="gaap-accuracy-metrics">
                <div class="gaap-accuracy-item">
                    <div class="gaap-accuracy-label"><?php _e('精度率', 'grant-ai-assistant-pro'); ?></div>
                    <div class="gaap-accuracy-bar">
                        <div class="gaap-accuracy-fill" style="width: 87.2%"></div>
                    </div>
                    <div class="gaap-accuracy-value">87.2%</div>
                </div>

                <div class="gaap-accuracy-item">
                    <div class="gaap-accuracy-label"><?php _e('適合率', 'grant-ai-assistant-pro'); ?></div>
                    <div class="gaap-accuracy-bar">
                        <div class="gaap-accuracy-fill" style="width: 91.5%"></div>
                    </div>
                    <div class="gaap-accuracy-value">91.5%</div>
                </div>

                <div class="gaap-accuracy-item">
                    <div class="gaap-accuracy-label"><?php _e('再現率', 'grant-ai-assistant-pro'); ?></div>
                    <div class="gaap-accuracy-bar">
                        <div class="gaap-accuracy-fill" style="width: 83.7%"></div>
                    </div>
                    <div class="gaap-accuracy-value">83.7%</div>
                </div>
            </div>
        </div>

        <!-- エラー・問題分析 -->
        <div class="gaap-issues-section">
            <h3><?php _e('⚠️ 問題・改善点', 'grant-ai-assistant-pro'); ?></h3>
            
            <div class="gaap-issues-list">
                <div class="gaap-issue-item gaap-issue-warning">
                    <div class="gaap-issue-icon">⚠️</div>
                    <div class="gaap-issue-content">
                        <div class="gaap-issue-title"><?php _e('API応答時間増加', 'grant-ai-assistant-pro'); ?></div>
                        <div class="gaap-issue-desc"><?php _e('過去1週間で平均応答時間が15%増加しています。', 'grant-ai-assistant-pro'); ?></div>
                        <div class="gaap-issue-action">
                            <button type="button" class="gaap-issue-btn"><?php _e('詳細確認', 'grant-ai-assistant-pro'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="gaap-issue-item gaap-issue-info">
                    <div class="gaap-issue-icon">💡</div>
                    <div class="gaap-issue-content">
                        <div class="gaap-issue-title"><?php _e('新しいキーワードトレンド', 'grant-ai-assistant-pro'); ?></div>
                        <div class="gaap-issue-desc"><?php _e('「DX推進」「カーボンニュートラル」への質問が増加中', 'grant-ai-assistant-pro'); ?></div>
                        <div class="gaap-issue-action">
                            <button type="button" class="gaap-issue-btn"><?php _e('対応検討', 'grant-ai-assistant-pro'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="gaap-issue-item gaap-issue-success">
                    <div class="gaap-issue-icon">✅</div>
                    <div class="gaap-issue-content">
                        <div class="gaap-issue-title"><?php _e('ユーザー満足度向上', 'grant-ai-assistant-pro'); ?></div>
                        <div class="gaap-issue-desc"><?php _e('プロンプト改良により満足度が12%向上しました。', 'grant-ai-assistant-pro'); ?></div>
                        <div class="gaap-issue-action">
                            <span class="gaap-issue-status"><?php _e('対応完了', 'grant-ai-assistant-pro'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gaap-analytics {
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

.gaap-analytics-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 16px 20px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gaap-date-range {
    display: flex;
    align-items: center;
    gap: 8px;
}

.gaap-date-range input {
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}

.gaap-refresh-data {
    padding: 6px 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.gaap-export-actions {
    display: flex;
    gap: 8px;
}

.gaap-export-csv,
.gaap-export-pdf {
    padding: 8px 16px;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.gaap-export-csv:hover,
.gaap-export-pdf:hover {
    background: #e5e7eb;
}

.gaap-analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.gaap-kpi-summary {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.gaap-kpi-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gaap-kpi-icon {
    font-size: 32px;
    width: 48px;
    text-align: center;
}

.gaap-kpi-number {
    font-size: 28px;
    font-weight: bold;
    color: #1f2937;
    line-height: 1;
}

.gaap-kpi-label {
    color: #6b7280;
    font-size: 14px;
    margin: 4px 0;
}

.gaap-kpi-change {
    font-size: 12px;
    font-weight: 600;
}

.gaap-kpi-change.gaap-positive {
    color: #10b981;
}

.gaap-kpi-change.gaap-negative {
    color: #ef4444;
}

.gaap-chart-section,
.gaap-keywords-section,
.gaap-heatmap-section,
.gaap-accuracy-section,
.gaap-issues-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gaap-chart-section h3,
.gaap-keywords-section h3,
.gaap-heatmap-section h3,
.gaap-accuracy-section h3,
.gaap-issues-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1f2937;
    font-size: 18px;
}

.gaap-chart-container {
    position: relative;
    height: 300px;
}

.gaap-keyword-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}

.gaap-keyword {
    display: inline-block;
    padding: 6px 12px;
    background: #f3f4f6;
    color: #374151;
    border-radius: 20px;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
}

.gaap-keyword:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

.gaap-keyword.gaap-size-xl {
    font-size: 24px;
    font-weight: bold;
    background: #667eea;
    color: white;
}

.gaap-keyword.gaap-size-lg {
    font-size: 18px;
    font-weight: 600;
    background: #e0e7ff;
    color: #3730a3;
}

.gaap-keyword.gaap-size-md {
    font-size: 16px;
    font-weight: 500;
}

.gaap-keyword.gaap-size-sm {
    font-size: 14px;
}

.gaap-heatmap {
    display: flex;
    gap: 16px;
}

.gaap-heatmap-labels {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.gaap-days {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 12px;
    font-weight: 600;
}

.gaap-days span {
    width: 24px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gaap-hours {
    display: flex;
    gap: 2px;
    font-size: 10px;
    margin-top: 8px;
}

.gaap-hours span {
    width: 16px;
    text-align: center;
}

.gaap-heatmap-grid {
    display: grid;
    grid-template-columns: repeat(24, 16px);
    grid-template-rows: repeat(7, 16px);
    gap: 2px;
}

.gaap-heatmap-cell {
    width: 16px;
    height: 16px;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.2s;
}

.gaap-heatmap-cell:hover {
    transform: scale(1.2);
    border: 1px solid #374151;
}

.gaap-level-0 { background: #f3f4f6; }
.gaap-level-1 { background: #c7d2fe; }
.gaap-level-2 { background: #a5b4fc; }
.gaap-level-3 { background: #818cf8; }
.gaap-level-4 { background: #6366f1; }

.gaap-heatmap-legend {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    font-size: 12px;
    color: #6b7280;
}

.gaap-legend-scale {
    display: flex;
    gap: 2px;
}

.gaap-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.gaap-accuracy-metrics {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.gaap-accuracy-item {
    display: flex;
    align-items: center;
    gap: 16px;
}

.gaap-accuracy-label {
    min-width: 80px;
    font-weight: 500;
    color: #374151;
}

.gaap-accuracy-bar {
    flex: 1;
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
}

.gaap-accuracy-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.5s ease;
}

.gaap-accuracy-value {
    min-width: 60px;
    text-align: right;
    font-weight: 600;
    color: #1f2937;
}

.gaap-issues-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.gaap-issue-item {
    display: flex;
    gap: 16px;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid;
}

.gaap-issue-item.gaap-issue-warning {
    background: #fffbeb;
    border-left-color: #f59e0b;
}

.gaap-issue-item.gaap-issue-info {
    background: #eff6ff;
    border-left-color: #3b82f6;
}

.gaap-issue-item.gaap-issue-success {
    background: #f0fdf4;
    border-left-color: #10b981;
}

.gaap-issue-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.gaap-issue-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.gaap-issue-desc {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 8px;
}

.gaap-issue-btn {
    padding: 4px 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.gaap-issue-status {
    padding: 4px 12px;
    background: #10b981;
    color: white;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

@media (max-width: 1024px) {
    .gaap-analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .gaap-analytics-controls {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .gaap-kpi-summary {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
}

@media (max-width: 768px) {
    .gaap-heatmap {
        flex-direction: column;
    }
    
    .gaap-issue-item {
        flex-direction: column;
        gap: 8px;
    }
    
    .gaap-chart-container {
        height: 200px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Chart.js が読み込まれるまで待機
    if (typeof Chart !== 'undefined') {
        initCharts();
    } else {
        // Chart.js を動的に読み込み
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js';
        script.onload = initCharts;
        document.head.appendChild(script);
    }

    function initCharts() {
        // 対話数推移グラフ
        const interactionsCtx = document.getElementById('gaap-interactions-chart');
        if (interactionsCtx) {
            new Chart(interactionsCtx, {
                type: 'line',
                data: {
                    labels: generateDateLabels(),
                    datasets: [{
                        label: '<?php _e('対話数', 'grant-ai-assistant-pro'); ?>',
                        data: generateInteractionsData(),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // 意図分布グラフ
        const intentCtx = document.getElementById('gaap-intent-chart');
        if (intentCtx) {
            new Chart(intentCtx, {
                type: 'doughnut',
                data: {
                    labels: ['スタートアップ', '設備投資', '研究開発', 'IT・DX', '人材育成', 'その他'],
                    datasets: [{
                        data: [28, 22, 18, 15, 10, 7],
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#f093fb',
                            '#10b981',
                            '#f59e0b',
                            '#ef4444'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    function generateDateLabels() {
        const labels = [];
        for (let i = 29; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            labels.push(date.getMonth() + 1 + '/' + date.getDate());
        }
        return labels;
    }

    function generateInteractionsData() {
        const data = [];
        for (let i = 0; i < 30; i++) {
            data.push(Math.floor(Math.random() * 50) + 20);
        }
        return data;
    }

    // ヒートマップ生成
    function generateHeatmap() {
        const grid = $('#gaap-heatmap-grid');
        for (let day = 0; day < 7; day++) {
            for (let hour = 0; hour < 24; hour++) {
                const level = Math.floor(Math.random() * 5);
                const cell = $('<div class="gaap-heatmap-cell gaap-level-' + level + '"></div>');
                
                cell.attr('title', '曜日 ' + day + ', 時刻 ' + hour + ':00 - レベル ' + level);
                
                grid.append(cell);
            }
        }
    }

    generateHeatmap();

    // エクスポート機能
    $('.gaap-export-csv').on('click', function() {
        alert('<?php _e('CSV出力機能は開発中です。', 'grant-ai-assistant-pro'); ?>');
    });

    $('.gaap-export-pdf').on('click', function() {
        alert('<?php _e('PDF出力機能は開発中です。', 'grant-ai-assistant-pro'); ?>');
    });

    // データ更新
    $('.gaap-refresh-data').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        button.text('<?php _e('更新中...', 'grant-ai-assistant-pro'); ?>').prop('disabled', true);
        
        // 実際の更新処理はここに実装
        setTimeout(function() {
            button.text(originalText).prop('disabled', false);
            // 成功メッセージを表示
            $('<div class="notice notice-success is-dismissible"><p><?php _e('データを更新しました。', 'grant-ai-assistant-pro'); ?></p></div>')
                .insertAfter('.gaap-admin-title');
        }, 2000);
    });

    // 問題詳細確認
    $('.gaap-issue-btn').on('click', function() {
        const title = $(this).closest('.gaap-issue-item').find('.gaap-issue-title').text();
        alert('<?php _e('詳細:', 'grant-ai-assistant-pro'); ?> ' + title + '\n\n<?php _e('詳細分析画面は開発中です。', 'grant-ai-assistant-pro'); ?>');
    });
});
</script>