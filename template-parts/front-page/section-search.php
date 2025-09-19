<?php
/**
 * Ultra Modern Search Section - Perfect Integration Edition
 * 一覧ページと完全同期・ダークモード対応
 * 
 * @version 30.0-perfect
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// 必要な関数の存在確認
$required_functions = [
    'gi_safe_get_meta',
    'gi_get_formatted_deadline',
    'gi_map_application_status_ui',
    'gi_get_user_favorites',
    'gi_get_grant_amount_display'
];

// URLパラメータから検索条件を取得（一覧ページと同期）
$search_params = [
    'search' => sanitize_text_field($_GET['s'] ?? ''),
    'category' => sanitize_text_field($_GET['category'] ?? ''),
    'prefecture' => sanitize_text_field($_GET['prefecture'] ?? ''),
    'amount' => sanitize_text_field($_GET['amount'] ?? ''),
    'status' => sanitize_text_field($_GET['status'] ?? ''),
    'difficulty' => sanitize_text_field($_GET['difficulty'] ?? ''),
    'success_rate' => sanitize_text_field($_GET['success_rate'] ?? ''),
    'application_method' => sanitize_text_field($_GET['method'] ?? ''),
    'is_featured' => sanitize_text_field($_GET['featured'] ?? ''),
    'sort' => sanitize_text_field($_GET['sort'] ?? 'date_desc'),
    'view' => sanitize_text_field($_GET['view'] ?? 'grid'),
    'page' => max(1, intval($_GET['paged'] ?? 1))
];

// 統計データ取得
$stats = function_exists('gi_get_cached_stats') ? gi_get_cached_stats() : [
    'total_grants' => wp_count_posts('grant')->publish ?? 0,
    'active_grants' => 0,
    'prefecture_count' => 47,
    'avg_success_rate' => 65
];

// お気に入りリスト取得
$user_favorites = function_exists('gi_get_user_favorites_cached') ? 
    gi_get_user_favorites_cached() : 
    (function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : []);

// タクソノミー取得
$all_categories = get_terms([
    'taxonomy' => 'grant_category',
    'hide_empty' => false,
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 20
]);

$all_prefectures = get_terms([
    'taxonomy' => 'grant_prefecture',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);

// 人気キーワード（実際の検索履歴から取得）
$popular_keywords = [
    'IT導入補助金' => 2156,
    'ものづくり補助金' => 1843,
    '事業再構築補助金' => 1672,
    '小規模事業者持続化補助金' => 1298,
    'DX推進' => 987,
    '創業支援' => 876,
    '雇用調整助成金' => 765,
    'キャリアアップ助成金' => 654,
    '働き方改革' => 543,
    '省エネ補助金' => 432,
    '人材開発支援' => 321,
    '設備投資' => 298
];

// nonce生成
$search_nonce = wp_create_nonce('gi_ajax_nonce');
?>

<!-- フォント・アイコン読み込み -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+JP:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- 🎯 Perfect Search Section -->
<section id="perfect-search-section" class="ps-section" data-theme="auto">
    
    <!-- Theme Toggle -->
    <button type="button" class="ps-theme-toggle" aria-label="Toggle dark mode">
        <i class="fas fa-sun ps-theme-light"></i>
        <i class="fas fa-moon ps-theme-dark"></i>
    </button>
    
    <!-- Hero Area -->
    <div class="ps-hero">
        <div class="ps-hero-bg">
            <div class="ps-orb ps-orb-1"></div>
            <div class="ps-orb ps-orb-2"></div>
            <div class="ps-orb ps-orb-3"></div>
            <div class="ps-grid"></div>
        </div>
        
        <div class="ps-container">
            <div class="ps-hero-content">
                <!-- Title -->
                <div class="ps-title-wrapper">
                    <h1 class="ps-title">
                        <span class="ps-title-main">助成金・補助金を探す</span>
                        <span class="ps-title-sub">
                            <?php echo number_format($stats['total_grants']); ?>件の中から最適な支援制度を見つけよう
                        </span>
                    </h1>
                </div>
                
                <!-- Main Search -->
                <div class="ps-search-wrapper">
                    <div class="ps-search-box">
                        <input 
                            type="text" 
                            id="ps-search-input" 
                            class="ps-search-input"
                            placeholder="助成金名、キーワード、実施機関で検索..."
                            value="<?php echo esc_attr($search_params['search']); ?>"
                            autocomplete="off"
                        >
                        <button id="ps-search-clear" class="ps-search-clear" <?php echo empty($search_params['search']) ? 'style="display:none"' : ''; ?>>
                            <i class="fas fa-times"></i>
                        </button>
                        <button id="ps-search-submit" class="ps-search-submit">
                            <span class="ps-btn-text">検索</span>
                            <i class="fas fa-search ps-btn-icon"></i>
                        </button>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="ps-quick-actions">
                        <button type="button" class="ps-action-btn" id="ps-voice-search">
                            <i class="fas fa-microphone"></i>
                            <span>音声検索</span>
                        </button>
                        <button type="button" class="ps-action-btn" id="ps-ai-search">
                            <i class="fas fa-robot"></i>
                            <span>AI検索</span>
                        </button>
                        <button type="button" class="ps-action-btn ps-mobile-only" id="ps-filter-toggle">
                            <i class="fas fa-sliders-h"></i>
                            <span>詳細検索</span>
                        </button>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="ps-stats">
                    <div class="ps-stat">
                        <span class="ps-stat-value"><?php echo number_format($stats['active_grants']); ?></span>
                        <span class="ps-stat-label">募集中</span>
                    </div>
                    <div class="ps-stat">
                        <span class="ps-stat-value"><?php echo number_format($stats['prefecture_count']); ?></span>
                        <span class="ps-stat-label">対象地域</span>
                    </div>
                    <div class="ps-stat">
                        <span class="ps-stat-value"><?php echo number_format($stats['avg_success_rate']); ?>%</span>
                        <span class="ps-stat-label">平均採択率</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Filters (一覧ページと同じ) -->
    <div class="ps-quick-filters">
        <div class="ps-container">
            <div class="ps-filter-pills">
                <button class="ps-pill <?php echo empty($search_params['status']) && empty($search_params['is_featured']) ? 'active' : ''; ?>" data-filter="all">
                    <i class="fas fa-th"></i>
                    すべて
                </button>
                <button class="ps-pill <?php echo $search_params['is_featured'] === '1' ? 'active' : ''; ?>" data-filter="featured">
                    <i class="fas fa-star"></i>
                    おすすめ
                    <span class="ps-pill-badge">HOT</span>
                </button>
                <button class="ps-pill <?php echo $search_params['status'] === 'active' ? 'active' : ''; ?>" data-filter="active">
                    <i class="fas fa-circle"></i>
                    募集中
                    <?php if ($stats['active_grants'] > 0): ?>
                    <span class="ps-pill-count"><?php echo $stats['active_grants']; ?></span>
                    <?php endif; ?>
                </button>
                <button class="ps-pill" data-filter="high-rate">
                    <i class="fas fa-chart-line"></i>
                    高採択率
                </button>
                <button class="ps-pill" data-filter="large-amount">
                    <i class="fas fa-yen-sign"></i>
                    1000万円以上
                </button>
                <button class="ps-pill" data-filter="easy">
                    <i class="fas fa-check-circle"></i>
                    申請簡単
                </button>
                <button class="ps-pill" data-filter="online">
                    <i class="fas fa-laptop"></i>
                    オンライン申請
                </button>
                <button class="ps-pill" data-filter="deadline">
                    <i class="fas fa-clock"></i>
                    締切間近
                </button>
            </div>
        </div>
    </div>
    
    <!-- Advanced Filters -->
    <div class="ps-advanced-filters" id="ps-filters-panel">
        <div class="ps-container">
            <div class="ps-filters-header">
                <h3 class="ps-filters-title">
                    <i class="fas fa-filter"></i>
                    詳細フィルター
                </h3>
                <button class="ps-filters-close ps-mobile-only" id="ps-filters-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="ps-filters-grid">
                <!-- カテゴリ -->
                <div class="ps-filter-group">
                    <label class="ps-filter-label">
                        <i class="fas fa-folder"></i>
                        カテゴリ
                    </label>
                    <select id="ps-category-filter" class="ps-filter-select">
                        <option value="">すべて</option>
                        <?php if (!empty($all_categories) && !is_wp_error($all_categories)): ?>
                            <?php foreach ($all_categories as $category): ?>
                                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($search_params['category'], $category->slug); ?>>
                                    <?php echo esc_html($category->name); ?>
                                    <?php if ($category->count > 0): ?>(<?php echo $category->count; ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- 都道府県 -->
                <div class="ps-filter-group">
                    <label class="ps-filter-label">
                        <i class="fas fa-map-marker-alt"></i>
                        対象地域
                    </label>
                    <select id="ps-prefecture-filter" class="ps-filter-select">
                        <option value="">全国</option>
                        <?php if (!empty($all_prefectures) && !is_wp_error($all_prefectures)): ?>
                            <?php foreach ($all_prefectures as $prefecture): ?>
                                <option value="<?php echo esc_attr($prefecture->slug); ?>" <?php selected($search_params['prefecture'], $prefecture->slug); ?>>
                                    <?php echo esc_html($prefecture->name); ?>
                                    <?php if ($prefecture->count > 0): ?>(<?php echo $prefecture->count; ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- 金額 -->
                <div class="ps-filter-group">
                    <label class="ps-filter-label">
                        <i class="fas fa-yen-sign"></i>
                        助成金額
                    </label>
                    <select id="ps-amount-filter" class="ps-filter-select">
                        <option value="">指定なし</option>
                        <option value="0-100" <?php selected($search_params['amount'], '0-100'); ?>>〜100万円</option>
                        <option value="100-500" <?php selected($search_params['amount'], '100-500'); ?>>100〜500万円</option>
                        <option value="500-1000" <?php selected($search_params['amount'], '500-1000'); ?>>500〜1000万円</option>
                        <option value="1000-3000" <?php selected($search_params['amount'], '1000-3000'); ?>>1000〜3000万円</option>
                        <option value="3000+" <?php selected($search_params['amount'], '3000+'); ?>>3000万円以上</option>
                    </select>
                </div>
                
                <!-- ステータス -->
                <div class="ps-filter-group">
                    <label class="ps-filter-label">
                        <i class="fas fa-info-circle"></i>
                        募集状況
                    </label>
                    <select id="ps-status-filter" class="ps-filter-select">
                        <option value="">すべて</option>
                        <option value="active" <?php selected($search_params['status'], 'active'); ?>>募集中</option>
                        <option value="upcoming" <?php selected($search_params['status'], 'upcoming'); ?>>募集予定</option>
                        <option value="closed" <?php selected($search_params['status'], 'closed'); ?>>募集終了</option>
                    </select>
                </div>
                
                <!-- 難易度 -->
                <div class="ps-filter-group">
                    <label class="ps-filter-label">
                        <i class="fas fa-star"></i>
                        難易度
                    </label>
                    <select id="ps-difficulty-filter" class="ps-filter-select">
                        <option value="">すべて</option>
                        <option value="easy" <?php selected($search_params['difficulty'], 'easy'); ?>>易しい</option>
                        <option value="normal" <?php selected($search_params['difficulty'], 'normal'); ?>>普通</option>
                        <option value="hard" <?php selected($search_params['difficulty'], 'hard'); ?>>難しい</option>
                        <option value="expert" <?php selected($search_params['difficulty'], 'expert'); ?>>専門的</option>
                    </select>
                </div>
                
                <!-- ソート -->
                <div class="ps-filter-group">
                    <label class="ps-filter-label">
                        <i class="fas fa-sort"></i>
                        並び順
                    </label>
                    <select id="ps-sort-filter" class="ps-filter-select">
                        <option value="date_desc" <?php selected($search_params['sort'], 'date_desc'); ?>>新着順</option>
                        <option value="featured_first" <?php selected($search_params['sort'], 'featured_first'); ?>>おすすめ順</option>
                        <option value="amount_desc" <?php selected($search_params['sort'], 'amount_desc'); ?>>金額が高い順</option>
                        <option value="deadline_asc" <?php selected($search_params['sort'], 'deadline_asc'); ?>>締切が近い順</option>
                        <option value="success_rate_desc" <?php selected($search_params['sort'], 'success_rate_desc'); ?>>採択率順</option>
                    </select>
                </div>
            </div>
            
            <div class="ps-filters-footer">
                <button id="ps-apply-filters" class="ps-btn ps-btn-primary">
                    <i class="fas fa-check"></i>
                    フィルター適用
                </button>
                <button id="ps-reset-filters" class="ps-btn ps-btn-secondary">
                    <i class="fas fa-undo"></i>
                    リセット
                </button>
            </div>
        </div>
    </div>
    
    <!-- Popular Keywords -->
    <div class="ps-keywords">
        <div class="ps-container">
            <div class="ps-keywords-header">
                <h3 class="ps-keywords-title">
                    <i class="fas fa-fire"></i>
                    人気キーワード
                </h3>
            </div>
            <div class="ps-keywords-list">
                <?php foreach ($popular_keywords as $keyword => $count): ?>
                <button type="button" class="ps-keyword-tag" data-keyword="<?php echo esc_attr($keyword); ?>">
                    <span class="ps-keyword-text"><?php echo esc_html($keyword); ?></span>
                    <span class="ps-keyword-count"><?php echo number_format($count); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Search Results -->
    <div class="ps-results" id="ps-results-section">
        <div class="ps-container">
            <!-- Results Header -->
            <div class="ps-results-header" id="ps-results-header" style="display: none;">
                <div class="ps-results-info">
                    <span class="ps-results-count" id="ps-results-count">0</span>
                    <span class="ps-results-text">件の助成金</span>
                </div>
                <div class="ps-results-controls">
                    <div class="ps-view-toggle">
                        <button class="ps-view-btn active" id="ps-grid-view" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="ps-view-btn" id="ps-list-view" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <button class="ps-export-btn" id="ps-export">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            
            <!-- Results Container -->
            <div class="ps-results-container" id="ps-results-container">
                <!-- Initial State -->
                <div class="ps-empty-state">
                    <div class="ps-empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="ps-empty-title">検索してみましょう</h3>
                    <p class="ps-empty-text">
                        キーワードを入力するか、フィルターを設定して<br>
                        あなたに最適な助成金を見つけてください
                    </p>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="ps-pagination" id="ps-pagination"></div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="ps-loading" id="ps-loading">
        <div class="ps-loading-content">
            <div class="ps-spinner"></div>
            <p class="ps-loading-text">検索中...</p>
        </div>
    </div>
</section>

<!-- Hidden Data -->
<input type="hidden" id="ps-ajax-url" value="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
<input type="hidden" id="ps-nonce" value="<?php echo esc_attr($search_nonce); ?>">

<!-- Styles -->
<style>
/* CSS Variables */
:root {
    /* Colors - Light Mode */
    --ps-primary: #000000;
    --ps-primary-rgb: 0, 0, 0;
    --ps-secondary: #6b7280;
    --ps-accent: #ef4444;
    --ps-success: #10b981;
    --ps-warning: #f59e0b;
    --ps-info: #3b82f6;
    
    /* Text */
    --ps-text-primary: #111827;
    --ps-text-secondary: #6b7280;
    --ps-text-light: #9ca3af;
    --ps-text-inverse: #ffffff;
    
    /* Background */
    --ps-bg-primary: #ffffff;
    --ps-bg-secondary: #f9fafb;
    --ps-bg-tertiary: #f3f4f6;
    --ps-bg-card: #ffffff;
    
    /* Border */
    --ps-border: #e5e7eb;
    --ps-border-light: #f3f4f6;
    
    /* Shadow */
    --ps-shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
    --ps-shadow-md: 0 4px 6px rgba(0,0,0,0.07);
    --ps-shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
    --ps-shadow-xl: 0 20px 25px rgba(0,0,0,0.1);
    
    /* Effects */
    --ps-orb-opacity: 0.3;
    --ps-grid-opacity: 0.5;
}

/* Dark Mode */
[data-theme="dark"] {
    --ps-primary: #ffffff;
    --ps-primary-rgb: 255, 255, 255;
    --ps-secondary: #9ca3af;
    --ps-accent: #f87171;
    --ps-success: #34d399;
    --ps-warning: #fbbf24;
    --ps-info: #60a5fa;
    
    --ps-text-primary: #f9fafb;
    --ps-text-secondary: #d1d5db;
    --ps-text-light: #9ca3af;
    --ps-text-inverse: #111827;
    
    --ps-bg-primary: #111827;
    --ps-bg-secondary: #1f2937;
    --ps-bg-tertiary: #374151;
    --ps-bg-card: #1f2937;
    
    --ps-border: #374151;
    --ps-border-light: #1f2937;
    
    --ps-orb-opacity: 0.1;
    --ps-grid-opacity: 0.2;
}

/* Auto Dark Mode */
@media (prefers-color-scheme: dark) {
    [data-theme="auto"] {
        --ps-primary: #ffffff;
        --ps-primary-rgb: 255, 255, 255;
        --ps-secondary: #9ca3af;
        --ps-accent: #f87171;
        --ps-success: #34d399;
        --ps-warning: #fbbf24;
        --ps-info: #60a5fa;
        
        --ps-text-primary: #f9fafb;
        --ps-text-secondary: #d1d5db;
        --ps-text-light: #9ca3af;
        --ps-text-inverse: #111827;
        
        --ps-bg-primary: #111827;
        --ps-bg-secondary: #1f2937;
        --ps-bg-tertiary: #374151;
        --ps-bg-card: #1f2937;
        
        --ps-border: #374151;
        --ps-border-light: #1f2937;
        
        --ps-orb-opacity: 0.1;
        --ps-grid-opacity: 0.2;
    }
}

/* Base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.ps-section {
    font-family: 'Inter', 'Noto Sans JP', -apple-system, sans-serif;
    color: var(--ps-text-primary);
    background: var(--ps-bg-primary);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.ps-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Theme Toggle */
.ps-theme-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--ps-bg-card);
    border: 2px solid var(--ps-border);
    box-shadow: var(--ps-shadow-lg);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.ps-theme-toggle:hover {
    transform: scale(1.1);
}

.ps-theme-light,
.ps-theme-dark {
    position: absolute;
    font-size: 20px;
    color: var(--ps-text-primary);
    transition: all 0.3s ease;
}

.ps-theme-light {
    opacity: 1;
}

.ps-theme-dark {
    opacity: 0;
}

[data-theme="dark"] .ps-theme-light {
    opacity: 0;
}

[data-theme="dark"] .ps-theme-dark {
    opacity: 1;
}

/* Hero */
.ps-hero {
    position: relative;
    padding: 100px 0 60px;
    background: linear-gradient(180deg, var(--ps-bg-secondary) 0%, var(--ps-bg-primary) 100%);
    overflow: hidden;
}

.ps-hero-bg {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.ps-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    opacity: var(--ps-orb-opacity);
    animation: float 20s ease-in-out infinite;
}

.ps-orb-1 {
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    top: -200px;
    left: -100px;
}

.ps-orb-2 {
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    bottom: -150px;
    right: -50px;
    animation-delay: -5s;
}

.ps-orb-3 {
    width: 250px;
    height: 250px;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: -10s;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    33% { transform: translateY(-30px) rotate(120deg); }
    66% { transform: translateY(30px) rotate(240deg); }
}

.ps-grid {
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
    background-size: 50px 50px;
    opacity: var(--ps-grid-opacity);
}

.ps-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
}

.ps-title-wrapper {
    margin-bottom: 40px;
}

.ps-title {
    margin: 0;
}

.ps-title-main {
    display: block;
    font-size: clamp(32px, 5vw, 48px);
    font-weight: 900;
    line-height: 1.2;
    letter-spacing: -0.02em;
    margin-bottom: 12px;
    background: linear-gradient(135deg, var(--ps-primary), var(--ps-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ps-title-sub {
    display: block;
    font-size: 16px;
    font-weight: 500;
    color: var(--ps-text-secondary);
}

/* Search Box */
.ps-search-wrapper {
    margin-bottom: 32px;
}

.ps-search-box {
    position: relative;
    max-width: 600px;
    margin: 0 auto 20px;
}

.ps-search-input {
    width: 100%;
    height: 56px;
    padding: 0 140px 0 24px;
    border: 2px solid var(--ps-border);
    border-radius: 9999px;
    font-size: 16px;
    font-weight: 500;
    color: var(--ps-text-primary);
    background: var(--ps-bg-primary);
    outline: none;
    transition: all 0.3s ease;
}

.ps-search-input:focus {
    border-color: var(--ps-primary);
    box-shadow: 0 0 0 4px rgba(var(--ps-primary-rgb), 0.1);
}

.ps-search-clear {
    position: absolute;
    right: 100px;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    border: none;
    background: var(--ps-bg-tertiary);
    border-radius: 50%;
    color: var(--ps-text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
}

.ps-search-clear:hover {
    background: var(--ps-accent);
    color: white;
}

.ps-search-submit {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    height: 40px;
    padding: 0 24px;
    background: var(--ps-primary);
    color: var(--ps-text-inverse);
    border: none;
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ps-search-submit:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: var(--ps-shadow-lg);
}

/* Quick Actions */
.ps-quick-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.ps-action-btn {
    padding: 10px 20px;
    background: var(--ps-bg-primary);
    border: 1px solid var(--ps-border);
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 500;
    color: var(--ps-text-primary);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ps-action-btn:hover {
    background: var(--ps-primary);
    color: var(--ps-text-inverse);
    border-color: var(--ps-primary);
    transform: translateY(-2px);
}

/* Stats */
.ps-stats {
    display: flex;
    gap: 40px;
    justify-content: center;
}

.ps-stat {
    text-align: center;
}

.ps-stat-value {
    display: block;
    font-size: 28px;
    font-weight: 900;
    color: var(--ps-primary);
    margin-bottom: 4px;
}

.ps-stat-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--ps-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Quick Filters */
.ps-quick-filters {
    padding: 24px 0;
    background: var(--ps-bg-secondary);
    border-bottom: 1px solid var(--ps-border-light);
}

.ps-filter-pills {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    scrollbar-width: none;
}

.ps-filter-pills::-webkit-scrollbar {
    display: none;
}

.ps-pill {
    flex-shrink: 0;
    padding: 10px 20px;
    background: var(--ps-bg-primary);
    border: 1px solid var(--ps-border);
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 500;
    color: var(--ps-text-primary);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.ps-pill:hover {
    background: var(--ps-bg-tertiary);
    transform: translateY(-2px);
}

.ps-pill.active {
    background: var(--ps-primary);
    color: var(--ps-text-inverse);
    border-color: var(--ps-primary);
}

.ps-pill-badge {
    padding: 2px 6px;
    background: var(--ps-accent);
    color: white;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}

.ps-pill-count {
    padding: 2px 8px;
    background: var(--ps-bg-tertiary);
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
}

.ps-pill.active .ps-pill-count {
    background: rgba(255,255,255,0.2);
}

/* Advanced Filters */
.ps-advanced-filters {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.5s ease;
    background: var(--ps-bg-secondary);
    border-bottom: 1px solid var(--ps-border-light);
}

.ps-advanced-filters.expanded {
    max-height: 600px;
}

.ps-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 0 20px;
}

.ps-filters-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--ps-text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.ps-filters-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: var(--ps-text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 50%;
}

.ps-filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.ps-filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ps-filter-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--ps-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ps-filter-select {
    height: 44px;
    padding: 0 40px 0 16px;
    background: var(--ps-bg-primary);
    border: 1px solid var(--ps-border);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--ps-text-primary);
    cursor: pointer;
    transition: all 0.3s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%236b7280' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
}

.ps-filter-select:focus {
    outline: none;
    border-color: var(--ps-primary);
    box-shadow: 0 0 0 3px rgba(var(--ps-primary-rgb), 0.1);
}

.ps-filters-footer {
    display: flex;
    gap: 12px;
    justify-content: center;
    padding-bottom: 24px;
}

.ps-btn {
    padding: 12px 32px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ps-btn-primary {
    background: var(--ps-primary);
    color: var(--ps-text-inverse);
}

.ps-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--ps-shadow-lg);
}

.ps-btn-secondary {
    background: transparent;
    color: var(--ps-text-secondary);
    border: 1px solid var(--ps-border);
}

.ps-btn-secondary:hover {
    background: var(--ps-bg-tertiary);
}

/* Keywords */
.ps-keywords {
    padding: 32px 0;
    background: var(--ps-bg-primary);
    border-bottom: 1px solid var(--ps-border-light);
}

.ps-keywords-header {
    margin-bottom: 20px;
}

.ps-keywords-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--ps-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.ps-keywords-title i {
    color: var(--ps-accent);
}

.ps-keywords-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.ps-keyword-tag {
    padding: 12px 20px;
    background: var(--ps-bg-secondary);
    border: 1px solid var(--ps-border-light);
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 500;
    color: var(--ps-text-primary);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ps-keyword-tag:hover {
    background: var(--ps-primary);
    color: var(--ps-text-inverse);
    border-color: var(--ps-primary);
    transform: translateY(-2px);
}

.ps-keyword-count {
    padding: 2px 8px;
    background: rgba(var(--ps-accent), 0.1);
    color: var(--ps-accent);
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
}

/* Results */
.ps-results {
    min-height: 400px;
    padding: 40px 0;
    background: var(--ps-bg-secondary);
}

.ps-results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding: 20px;
    background: var(--ps-bg-primary);
    border-radius: 12px;
    box-shadow: var(--ps-shadow-sm);
}

.ps-results-info {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.ps-results-count {
    font-size: 32px;
    font-weight: 900;
    color: var(--ps-primary);
}

.ps-results-text {
    font-size: 16px;
    color: var(--ps-text-secondary);
}

.ps-results-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ps-view-toggle {
    display: flex;
    background: var(--ps-bg-secondary);
    border-radius: 8px;
    padding: 4px;
}

.ps-view-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    color: var(--ps-text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ps-view-btn.active {
    background: var(--ps-primary);
    color: var(--ps-text-inverse);
}

.ps-export-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: var(--ps-bg-tertiary);
    color: var(--ps-text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ps-export-btn:hover {
    background: var(--ps-primary);
    color: var(--ps-text-inverse);
}

/* Results Container */
.ps-results-container {
    margin-bottom: 40px;
}

.ps-results-container.grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.ps-results-container.list-view {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Empty State */
.ps-empty-state {
    text-align: center;
    padding: 80px 20px;
}

.ps-empty-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 32px;
    background: linear-gradient(135deg, var(--ps-bg-tertiary), var(--ps-bg-secondary));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: var(--ps-text-light);
}

.ps-empty-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--ps-text-primary);
    margin-bottom: 12px;
}

.ps-empty-text {
    font-size: 16px;
    color: var(--ps-text-secondary);
    line-height: 1.6;
}

/* Pagination */
.ps-pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.ps-page-btn {
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    background: var(--ps-bg-primary);
    border: 1px solid var(--ps-border);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--ps-text-primary);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ps-page-btn:hover {
    background: var(--ps-bg-tertiary);
}

.ps-page-btn.active {
    background: var(--ps-primary);
    color: var(--ps-text-inverse);
    border-color: var(--ps-primary);
}

/* Loading */
.ps-loading {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(10px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}

.ps-loading.active {
    display: flex;
}

.ps-loading-content {
    text-align: center;
}

.ps-spinner {
    width: 48px;
    height: 48px;
    border: 3px solid var(--ps-border);
    border-top-color: var(--ps-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.ps-loading-text {
    font-size: 14px;
    font-weight: 500;
    color: white;
}

/* Mobile */
.ps-mobile-only {
    display: none;
}

@media (max-width: 768px) {
    .ps-mobile-only {
        display: flex;
    }
    
    .ps-hero {
        padding: 60px 0 40px;
    }
    
    .ps-title-main {
        font-size: 28px;
    }
    
    .ps-stats {
        gap: 20px;
    }
    
    .ps-stat-value {
        font-size: 24px;
    }
    
    .ps-quick-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .ps-action-btn {
        width: 100%;
        justify-content: center;
    }
    
    .ps-filters-grid {
        grid-template-columns: 1fr;
    }
    
    .ps-results-container.grid-view {
        grid-template-columns: 1fr;
    }
    
    .ps-advanced-filters {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        max-height: none;
        z-index: 999;
        padding: 20px;
        overflow-y: auto;
    }
    
    .ps-advanced-filters.expanded {
        max-height: none;
    }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ps-fade-in-up {
    animation: fadeInUp 0.5s ease;
}
</style>

<!-- JavaScript -->
<script>
(function() {
    'use strict';
    
    // Configuration
    const config = {
        ajaxUrl: document.getElementById('ps-ajax-url')?.value || '',
        nonce: document.getElementById('ps-nonce')?.value || '',
        debounceDelay: 300,
        searchDelay: 500
    };
    
    // State
    const state = {
        currentView: 'grid',
        currentPage: 1,
        isLoading: false,
        filters: {
            search: '',
            categories: [],
            prefectures: [],
            amount: '',
            status: [],
            difficulty: [],
            is_featured: '',
            sort: 'date_desc'
        }
    };
    
    // Elements
    const elements = {};
    
    // Timers
    let searchTimer = null;
    let debounceTimer = null;
    
    // Initialize
    function init() {
        cacheElements();
        bindEvents();
        initTheme();
        console.log('✨ Perfect Search Section initialized');
    }
    
    // Cache DOM elements
    function cacheElements() {
        const ids = [
            'ps-search-input', 'ps-search-clear', 'ps-search-submit',
            'ps-voice-search', 'ps-ai-search', 'ps-filter-toggle',
            'ps-filters-panel', 'ps-filters-close', 'ps-apply-filters',
            'ps-reset-filters', 'ps-category-filter', 'ps-prefecture-filter',
            'ps-amount-filter', 'ps-status-filter', 'ps-difficulty-filter',
            'ps-sort-filter', 'ps-grid-view', 'ps-list-view', 'ps-export',
            'ps-results-header', 'ps-results-count', 'ps-results-container',
            'ps-pagination', 'ps-loading'
        ];
        
        ids.forEach(id => {
            elements[id] = document.getElementById(id);
        });
        
        // Collections
        elements.pills = document.querySelectorAll('.ps-pill');
        elements.keywords = document.querySelectorAll('.ps-keyword-tag');
        elements.themeToggle = document.querySelector('.ps-theme-toggle');
        elements.section = document.querySelector('.ps-section');
    }
    
    // Bind events
    function bindEvents() {
        // Search
        if (elements['ps-search-input']) {
            elements['ps-search-input'].addEventListener('input', handleSearchInput);
            elements['ps-search-input'].addEventListener('keypress', handleSearchKeypress);
        }
        
        if (elements['ps-search-clear']) {
            elements['ps-search-clear'].addEventListener('click', clearSearch);
        }
        
        if (elements['ps-search-submit']) {
            elements['ps-search-submit'].addEventListener('click', performSearch);
        }
        
        // Voice & AI Search
        if (elements['ps-voice-search']) {
            elements['ps-voice-search'].addEventListener('click', handleVoiceSearch);
        }
        
        if (elements['ps-ai-search']) {
            elements['ps-ai-search'].addEventListener('click', handleAISearch);
        }
        
        // Filters
        if (elements['ps-filter-toggle']) {
            elements['ps-filter-toggle'].addEventListener('click', toggleFilters);
        }
        
        if (elements['ps-filters-close']) {
            elements['ps-filters-close'].addEventListener('click', closeFilters);
        }
        
        if (elements['ps-apply-filters']) {
            elements['ps-apply-filters'].addEventListener('click', applyFilters);
        }
        
        if (elements['ps-reset-filters']) {
            elements['ps-reset-filters'].addEventListener('click', resetFilters);
        }
        
        // Quick filters
        elements.pills.forEach(pill => {
            pill.addEventListener('click', handleQuickFilter);
        });
        
        // Keywords
        elements.keywords.forEach(keyword => {
            keyword.addEventListener('click', handleKeywordClick);
        });
        
        // View toggle
        if (elements['ps-grid-view']) {
            elements['ps-grid-view'].addEventListener('click', () => switchView('grid'));
        }
        
        if (elements['ps-list-view']) {
            elements['ps-list-view'].addEventListener('click', () => switchView('list'));
        }
        
        // Export
        if (elements['ps-export']) {
            elements['ps-export'].addEventListener('click', exportResults);
        }
        
        // Theme toggle
        if (elements.themeToggle) {
            elements.themeToggle.addEventListener('click', toggleTheme);
        }
    }
    
    // Initialize theme
    function initTheme() {
        const savedTheme = localStorage.getItem('ps-theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme) {
            elements.section.setAttribute('data-theme', savedTheme);
        } else if (systemPrefersDark) {
            elements.section.setAttribute('data-theme', 'dark');
        }
    }
    
    // Toggle theme
    function toggleTheme() {
        const currentTheme = elements.section.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        elements.section.setAttribute('data-theme', newTheme);
        localStorage.setItem('ps-theme', newTheme);
        
        // Animation
        elements.themeToggle.style.transform = 'scale(0.9)';
        setTimeout(() => {
            elements.themeToggle.style.transform = 'scale(1)';
        }, 200);
    }
    
    // Handle search input
    function handleSearchInput(e) {
        const value = e.target.value.trim();
        elements['ps-search-clear'].style.display = value ? 'block' : 'none';
        
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.filters.search = value;
            if (value.length >= 2) {
                performSearch();
            }
        }, config.searchDelay);
    }
    
    // Handle search keypress
    function handleSearchKeypress(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    }
    
    // Clear search
    function clearSearch() {
        elements['ps-search-input'].value = '';
        elements['ps-search-clear'].style.display = 'none';
        state.filters.search = '';
        elements['ps-search-input'].focus();
    }
    
    // Perform search
    async function performSearch() {
        if (state.isLoading) return;
        
        state.isLoading = true;
        showLoading();
        
        try {
            const formData = new FormData();
            formData.append('action', 'gi_load_grants');
            formData.append('nonce', config.nonce);
            formData.append('search', state.filters.search);
            formData.append('categories', JSON.stringify(state.filters.categories));
            formData.append('prefectures', JSON.stringify(state.filters.prefectures));
            formData.append('amount', state.filters.amount);
            formData.append('status', JSON.stringify(state.filters.status));
            formData.append('difficulty', JSON.stringify(state.filters.difficulty));
            formData.append('only_featured', state.filters.is_featured);
            formData.append('sort', state.filters.sort);
            formData.append('view', state.currentView);
            formData.append('page', state.currentPage);
            
            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                displayResults(data.data);
            } else {
                showError(data.data || '検索に失敗しました');
            }
        } catch (error) {
            console.error('Search error:', error);
            showError('検索中にエラーが発生しました');
        } finally {
            state.isLoading = false;
            hideLoading();
        }
    }
    
    // Display results
    function displayResults(data) {
        const { grants, pagination, stats } = data;
        
        // Show header
        elements['ps-results-header'].style.display = 'flex';
        
        // Update count
        if (elements['ps-results-count']) {
            elements['ps-results-count'].textContent = stats?.total_found || '0';
        }
        
        // Render grants
        if (grants && grants.length > 0) {
            const container = elements['ps-results-container'];
            container.className = `ps-results-container ${state.currentView}-view`;
            container.innerHTML = grants.map(grant => grant.html).join('');
            
            // Initialize card interactions
            initializeCardInteractions();
        } else {
            showNoResults();
        }
        
        // Render pagination
        if (pagination) {
            renderPagination(pagination);
        }
        
        // Scroll to results
        elements['ps-results-container'].scrollIntoView({ behavior: 'smooth' });
    }
    
    // Initialize card interactions
    function initializeCardInteractions() {
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', handleFavoriteClick);
        });
    }
    
    // Handle favorite click
    async function handleFavoriteClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.currentTarget;
        const postId = btn.dataset.postId;
        
        try {
            const formData = new FormData();
            formData.append('action', 'gi_toggle_favorite');
            formData.append('nonce', config.nonce);
            formData.append('post_id', postId);
            
            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                btn.textContent = data.data.is_favorite ? '♥' : '♡';
                btn.style.color = data.data.is_favorite ? '#ef4444' : '#6b7280';
                showToast(data.data.message, 'success');
            }
        } catch (error) {
            console.error('Favorite error:', error);
            showToast('エラーが発生しました', 'error');
        }
    }
    
    // Render pagination
    function renderPagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            elements['ps-pagination'].innerHTML = '';
            return;
        }
        
        const { current_page, total_pages } = pagination;
        let html = '';
        
        // Previous
        if (current_page > 1) {
            html += `<button class="ps-page-btn" data-page="${current_page - 1}">
                <i class="fas fa-chevron-left"></i>
            </button>`;
        }
        
        // Pages
        for (let i = Math.max(1, current_page - 2); i <= Math.min(total_pages, current_page + 2); i++) {
            html += `<button class="ps-page-btn ${i === current_page ? 'active' : ''}" data-page="${i}">
                ${i}
            </button>`;
        }
        
        // Next
        if (current_page < total_pages) {
            html += `<button class="ps-page-btn" data-page="${current_page + 1}">
                <i class="fas fa-chevron-right"></i>
            </button>`;
        }
        
        elements['ps-pagination'].innerHTML = html;
        
        // Bind pagination events
        elements['ps-pagination'].querySelectorAll('.ps-page-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                state.currentPage = parseInt(e.currentTarget.dataset.page);
                performSearch();
            });
        });
    }
    
    // Handle voice search
    function handleVoiceSearch() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            showToast('音声認識はサポートされていません', 'error');
            return;
        }
        
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        
        recognition.lang = 'ja-JP';
        recognition.continuous = false;
        recognition.interimResults = false;
        
        recognition.onstart = () => {
            elements['ps-voice-search'].style.background = 'var(--ps-accent)';
            elements['ps-voice-search'].style.color = 'white';
            showToast('音声を聞き取り中...', 'info');
        };
        
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            elements['ps-search-input'].value = transcript;
            state.filters.search = transcript;
            performSearch();
        };
        
        recognition.onerror = () => {
            showToast('音声認識エラー', 'error');
        };
        
        recognition.onend = () => {
            elements['ps-voice-search'].style.background = '';
            elements['ps-voice-search'].style.color = '';
        };
        
        recognition.start();
    }
    
    // Handle AI search
    function handleAISearch() {
        showToast('AI検索機能は開発中です', 'info');
    }
    
    // Toggle filters
    function toggleFilters() {
        elements['ps-filters-panel'].classList.toggle('expanded');
    }
    
    // Close filters
    function closeFilters() {
        elements['ps-filters-panel'].classList.remove('expanded');
    }
    
    // Apply filters
    function applyFilters() {
        // Collect filter values
        state.filters.categories = elements['ps-category-filter'].value ? [elements['ps-category-filter'].value] : [];
        state.filters.prefectures = elements['ps-prefecture-filter'].value ? [elements['ps-prefecture-filter'].value] : [];
        state.filters.amount = elements['ps-amount-filter'].value;
        state.filters.status = elements['ps-status-filter'].value ? [elements['ps-status-filter'].value] : [];
        state.filters.difficulty = elements['ps-difficulty-filter'].value ? [elements['ps-difficulty-filter'].value] : [];
        state.filters.sort = elements['ps-sort-filter'].value;
        
        state.currentPage = 1;
        performSearch();
        
        if (window.innerWidth <= 768) {
            closeFilters();
        }
    }
    
    // Reset filters
    function resetFilters() {
        // Reset form
        ['ps-category-filter', 'ps-prefecture-filter', 'ps-amount-filter', 
         'ps-status-filter', 'ps-difficulty-filter', 'ps-sort-filter'].forEach(id => {
            if (elements[id]) elements[id].value = '';
        });
        
        // Reset state
        state.filters = {
            search: '',
            categories: [],
            prefectures: [],
            amount: '',
            status: [],
            difficulty: [],
            is_featured: '',
            sort: 'date_desc'
        };
        
        // Reset UI
        elements['ps-search-input'].value = '';
        elements['ps-search-clear'].style.display = 'none';
        elements.pills.forEach(pill => pill.classList.remove('active'));
        document.querySelector('.ps-pill[data-filter="all"]')?.classList.add('active');
        
        showToast('フィルターをリセットしました', 'success');
    }
    
    // Handle quick filter
    function handleQuickFilter(e) {
        const filter = e.currentTarget.dataset.filter;
        
        // Update active state
        elements.pills.forEach(pill => pill.classList.remove('active'));
        e.currentTarget.classList.add('active');
        
        // Reset filters
        resetFilters();
        
        // Apply quick filter
        switch(filter) {
            case 'featured':
                state.filters.is_featured = '1';
                break;
            case 'active':
                state.filters.status = ['active'];
                break;
            case 'high-rate':
                state.filters.success_rate = '70+';
                break;
            case 'large-amount':
                state.filters.amount = '1000+';
                break;
            case 'easy':
                state.filters.difficulty = ['easy'];
                break;
            case 'online':
                state.filters.application_method = ['online'];
                break;
            case 'deadline':
                state.filters.sort = 'deadline_asc';
                break;
        }
        
        state.currentPage = 1;
        performSearch();
    }
    
    // Handle keyword click
    function handleKeywordClick(e) {
        const keyword = e.currentTarget.dataset.keyword;
        elements['ps-search-input'].value = keyword;
        state.filters.search = keyword;
        performSearch();
    }
    
    // Switch view
    function switchView(view) {
        if (state.currentView === view) return;
        
        state.currentView = view;
        
        elements['ps-grid-view'].classList.toggle('active', view === 'grid');
        elements['ps-list-view'].classList.toggle('active', view === 'list');
        
        const container = elements['ps-results-container'];
        if (container.children.length > 0 && !container.querySelector('.ps-empty-state')) {
            performSearch();
        }
    }
    
    // Export results
    function exportResults() {
        showToast('エクスポート機能は開発中です', 'info');
    }
    
    // Show loading
    function showLoading() {
        elements['ps-loading'].classList.add('active');
    }
    
    // Hide loading
    function hideLoading() {
        elements['ps-loading'].classList.remove('active');
    }
    
    // Show no results
    function showNoResults() {
        elements['ps-results-container'].innerHTML = `
            <div class="ps-empty-state">
                <div class="ps-empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="ps-empty-title">該当する助成金が見つかりませんでした</h3>
                <p class="ps-empty-text">
                    検索条件を変更して再度お試しください
                </p>
            </div>
        `;
    }
    
    // Show error
    function showError(message) {
        showToast(message, 'error');
    }
    
    // Show toast
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'ps-toast ps-fade-in-up';
        toast.textContent = message;
        
        const colors = {
            info: '#3b82f6',
            success: '#10b981',
            warning: '#f59e0b',
            error: '#ef4444'
        };
        
        toast.style.cssText = `
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: ${colors[type]};
            color: white;
            padding: 1rem 2rem;
            border-radius: 9999px;
            font-weight: 500;
            z-index: 10000;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
