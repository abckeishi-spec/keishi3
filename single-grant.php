<?php
/**
 * Template for displaying single grant posts - Ultimate Enhanced Version
 * 完全統合版 - 全機能実装 + スタイリッシュデザイン
 *
 * @package Grant_Insight_Perfect
 * @version 8.0
 */

get_header();

// 投稿データの事前取得と準備
if (!have_posts()) {
    wp_redirect(home_url('/404'));
    exit;
}

the_post();
$post_id = get_the_ID();

// パフォーマンス最適化：完全なメタデータ取得
$grant_data = function_exists('gi_get_complete_grant_data') 
    ? gi_get_complete_grant_data($post_id) 
    : gi_get_all_grant_meta($post_id);

// 重要データの抽出
$ai_summary = $grant_data['ai_summary'] ?? gi_safe_get_meta($post_id, 'ai_summary', '');
$max_amount = $grant_data['max_amount'] ?? gi_safe_get_meta($post_id, 'max_amount', '');
$max_amount_numeric = intval($grant_data['max_amount_numeric'] ?? gi_safe_get_meta($post_id, 'max_amount_numeric', 0));
$subsidy_rate = $grant_data['subsidy_rate'] ?? gi_safe_get_meta($post_id, 'subsidy_rate', '');
$deadline_date = $grant_data['deadline_date'] ?? gi_safe_get_meta($post_id, 'deadline_date', '');
$deadline_formatted = $grant_data['deadline_formatted'] ?? gi_get_formatted_deadline($post_id);
$application_status = $grant_data['application_status'] ?? gi_safe_get_meta($post_id, 'application_status', 'open');
$grant_difficulty = $grant_data['grant_difficulty'] ?? gi_safe_get_meta($post_id, 'grant_difficulty', 'normal');
$grant_success_rate = intval($grant_data['grant_success_rate'] ?? gi_safe_get_meta($post_id, 'grant_success_rate', 0));
$organization = $grant_data['organization'] ?? gi_safe_get_meta($post_id, 'organization', '');
$organization_type = $grant_data['organization_type'] ?? gi_safe_get_meta($post_id, 'organization_type', '');
$grant_target = $grant_data['grant_target'] ?? gi_safe_get_meta($post_id, 'grant_target', '');
$application_period = $grant_data['application_period'] ?? gi_safe_get_meta($post_id, 'application_period', '');
$official_url = $grant_data['official_url'] ?? gi_safe_get_meta($post_id, 'official_url', '');
$external_link = $grant_data['external_link'] ?? gi_safe_get_meta($post_id, 'external_link', '');
$eligible_expenses = $grant_data['eligible_expenses'] ?? gi_safe_get_meta($post_id, 'eligible_expenses', '');
$application_method = $grant_data['application_method'] ?? gi_safe_get_meta($post_id, 'application_method', '');
$required_documents = $grant_data['required_documents'] ?? gi_safe_get_meta($post_id, 'required_documents', '');
$contact_info = $grant_data['contact_info'] ?? gi_safe_get_meta($post_id, 'contact_info', '');
$is_featured = $grant_data['is_featured'] ?? gi_safe_get_meta($post_id, 'is_featured', false);
$views_count = intval($grant_data['views_count'] ?? gi_safe_get_meta($post_id, 'views_count', 0));
$priority_order = intval($grant_data['priority_order'] ?? gi_safe_get_meta($post_id, 'priority_order', 100));
$amount_note = $grant_data['amount_note'] ?? gi_safe_get_meta($post_id, 'amount_note', '');
$deadline_note = $grant_data['deadline_note'] ?? gi_safe_get_meta($post_id, 'deadline_note', '');
$admin_notes = $grant_data['admin_notes'] ?? gi_safe_get_meta($post_id, 'admin_notes', '');

// タクソノミー情報
$categories = get_the_terms($post_id, 'grant_category');
$prefectures = get_the_terms($post_id, 'grant_prefecture');  
$tags = get_the_terms($post_id, 'grant_tag');

$main_category = ($categories && !is_wp_error($categories)) ? $categories[0] : null;
$main_prefecture = ($prefectures && !is_wp_error($prefectures)) ? $prefectures[0] : null;

// 金額フォーマット（統一関数使用）
$formatted_amount = function_exists('gi_format_amount_unified') 
    ? gi_format_amount_unified($max_amount_numeric, $max_amount) 
    : gi_format_amount_man($max_amount_numeric);

// 締切計算（完全版）
$days_remaining = 0;
$deadline_status = 'normal';
$deadline_timestamp = 0;

if ($deadline_date) {
    $deadline_timestamp = is_numeric($deadline_date) ? intval($deadline_date) : strtotime($deadline_date);
    if ($deadline_timestamp && $deadline_timestamp > 0) {
        $current_time = current_time('timestamp');
        $days_remaining = ceil(($deadline_timestamp - $current_time) / (60 * 60 * 24));
        
        if ($days_remaining <= 0) {
            $deadline_status = 'expired';
        } elseif ($days_remaining <= 3) {
            $deadline_status = 'critical';
        } elseif ($days_remaining <= 7) {
            $deadline_status = 'urgent';  
        } elseif ($days_remaining <= 14) {
            $deadline_status = 'soon';
        } else {
            $deadline_status = 'normal';
        }
    }
}

// 難易度設定（完全版）
$difficulty_config = array(
    'easy' => array(
        'label' => '易しい', 
        'color' => 'emerald', 
        'bg_color' => 'bg-emerald-500',
        'text_color' => 'text-emerald-600',
        'stars' => 1, 
        'description' => '初回申請でも対応可能。基本書類のみで申請できます。',
        'tips' => '必要書類をしっかり準備すれば問題ありません。'
    ),
    'normal' => array(
        'label' => '普通', 
        'color' => 'blue', 
        'bg_color' => 'bg-blue-500',
        'text_color' => 'text-blue-600',
        'stars' => 2, 
        'description' => '一般的な申請レベル。事業計画の作成が必要です。',
        'tips' => '事業計画を丁寧に作成し、計画性をアピールしましょう。'
    ),
    'hard' => array(
        'label' => '難しい', 
        'color' => 'amber', 
        'bg_color' => 'bg-amber-500',
        'text_color' => 'text-amber-600',
        'stars' => 3, 
        'description' => '専門知識や詳細な事業計画が必要。経験者推奨。',
        'tips' => '専門家のサポートを検討することをお勧めします。'
    ),
    'expert' => array(
        'label' => '専門的', 
        'color' => 'red', 
        'bg_color' => 'bg-red-500',
        'text_color' => 'text-red-600',
        'stars' => 4, 
        'description' => '高度な専門性が必要。専門家による申請サポート必須。',
        'tips' => '認定支援機関などの専門家と連携して申請することを強く推奨します。'
    )
);

$difficulty_info = $difficulty_config[$grant_difficulty] ?? $difficulty_config['normal'];

// お気に入り状態
$user_favorites = function_exists('gi_get_user_favorites_cached') 
    ? gi_get_user_favorites_cached() 
    : gi_get_user_favorites();
$is_favorite = in_array($post_id, $user_favorites);

// 関連データ
$related_grants_count = function_exists('gi_count_related_grants') ? gi_count_related_grants($post_id) : 3;
$similar_success_rate = function_exists('gi_get_similar_grants_success_rate') ? gi_get_similar_grants_success_rate($post_id) : null;

// 統計データ
$site_stats = function_exists('gi_get_cached_stats') ? gi_get_cached_stats() : array();

// 構造化データ（完全版）
$structured_data = array(
    '@context' => 'https://schema.org',
    '@type' => 'GovernmentService',
    'name' => get_the_title(),
    'description' => wp_strip_all_tags(get_the_excerpt()),
    'provider' => array(
        '@type' => 'GovernmentOrganization',
        'name' => $organization ?: '政府機関'
    ),
    'areaServed' => $main_prefecture ? $main_prefecture->name : '日本',
    'serviceType' => $main_category ? $main_category->name : '助成金・補助金',
    'offers' => array(
        '@type' => 'Offer',
        'price' => $max_amount_numeric ?: 0,
        'priceCurrency' => 'JPY'
    )
);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> | <?php bloginfo('name'); ?></title>
    
    <!-- 構造化データ -->
    <script type="application/ld+json">
        <?php echo json_encode($structured_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
    </script>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo esc_attr(wp_strip_all_tags(get_the_excerpt())); ?>">
    <meta property="og:title" content="<?php the_title(); ?>">
    <meta property="og:description" content="<?php echo esc_attr(wp_strip_all_tags(get_the_excerpt())); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo get_permalink(); ?>">
    
    <!-- スタイルシート -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-purple: #7C3AED;
            --primary-blue: #3B82F6;
            --primary-green: #10B981;
            --accent-pink: #EC4899;
            --accent-orange: #F59E0B;
            --neutral-50: #F9FAFB;
            --neutral-100: #F3F4F6;
            --neutral-800: #1F2937;
            --neutral-900: #111827;
        }
        
        body {
            font-family: 'Noto Sans JP', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* アニメーション定義 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translateY(0);
            }
            40%, 43% {
                transform: translateY(-15px);
            }
            70% {
                transform: translateY(-7px);
            }
            90% {
                transform: translateY(-3px);
            }
        }
        
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        /* クラス適用 */
        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        
        .animate-slideInLeft {
            animation: slideInLeft 0.8s ease-out forwards;
        }
        
        .animate-slideInRight {
            animation: slideInRight 0.8s ease-out forwards;
        }
        
        .animate-pulse-slow {
            animation: pulse 3s ease-in-out infinite;
        }
        
        .animate-bounce-slow {
            animation: bounce 2s infinite;
        }
        
        .animate-gradient {
            background-size: 400% 400%;
            animation: gradient 6s ease infinite;
        }
        
        /* グラデーション背景 */
        .gradient-bg-primary {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-blue) 50%, var(--primary-green) 100%);
        }
        
        .gradient-bg-secondary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* カスタムカード */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .neo-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 20px 20px 60px #d9d9d9, -20px -20px 60px #ffffff;
            transition: all 0.3s ease;
        }
        
        .neo-card:hover {
            box-shadow: 25px 25px 75px #d0d0d0, -25px -25px 75px #ffffff;
            transform: translateY(-5px);
        }
        
        /* プログレスバー */
        .progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
        }
        
        .progress-circle {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .progress-value {
            stroke-dasharray: 314;
            stroke-dashoffset: 314;
            transition: stroke-dashoffset 2s ease;
        }
        
        /* カスタムスクロールバー */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--neutral-100);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-purple), var(--primary-blue));
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
        }
        
        /* タブ */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeInUp 0.5s ease-out;
        }
        
        /* ツールチップ */
        .tooltip {
            position: relative;
        }
        
        .tooltip:hover .tooltip-content {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(-10px);
        }
        
        .tooltip-content {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(0);
            background: var(--neutral-800);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .tooltip-content::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--neutral-800) transparent transparent transparent;
        }
        
        /* スクロールインジケーター */
        .scroll-indicator {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-purple), var(--primary-blue), var(--primary-green));
            transform-origin: left;
            transform: scaleX(0);
            z-index: 9999;
            transition: transform 0.1s ease;
        }
        
        /* レスポンシブ対応 */
        @media (max-width: 1024px) {
            .hero-title {
                font-size: 3rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .metric-card {
                min-height: auto;
            }
            
            .glass-card {
                backdrop-filter: blur(5px);
            }
        }
        
        @media (max-width: 640px) {
            .hero-title {
                font-size: 1.75rem;
            }
            
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
        
        /* 印刷対応 */
        @media print {
            .no-print,
            .scroll-indicator,
            #scrollToTop,
            #helpBtn,
            #shareModal,
            .hero-section,
            .floating-actions {
                display: none !important;
            }
            
            .tab-content {
                display: block !important;
            }
            
            body {
                background: white !important;
            }
            
            .neo-card,
            .glass-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>

<body class="gradient-bg-secondary">
    <!-- スクロール進行インジケーター -->
    <div class="scroll-indicator" id="scrollIndicator"></div>
    
    <!-- メインコンテナ -->
    <div class="min-h-screen">
        
        <!-- ヒーローセクション -->
        <section class="hero-section relative overflow-hidden">
            <!-- 装飾的背景パターン -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23ffffff" fill-opacity="0.3"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z"/%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
            
            <div class="relative z-10 py-16 lg:py-24">
                <div class="container mx-auto px-4 lg:px-6">
                    <div class="max-w-7xl mx-auto">
                        
                        <!-- パンくずナビゲーション -->
                        <nav class="mb-8 animate-fadeInUp" style="animation-delay: 0.1s;">
                            <ol class="flex flex-wrap items-center text-sm text-white/80 space-x-2">
                                <li>
                                    <a href="<?php echo home_url(); ?>" class="hover:text-white transition-colors duration-300 flex items-center">
                                        <i class="fas fa-home text-lg"></i>
                                    </a>
                                </li>
                                <li><i class="fas fa-chevron-right text-xs"></i></li>
                                <li>
                                    <a href="<?php echo get_post_type_archive_link('grant'); ?>" class="hover:text-white transition-colors duration-300">
                                        助成金一覧
                                    </a>
                                </li>
                                <?php if ($main_category): ?>
                                <li><i class="fas fa-chevron-right text-xs"></i></li>
                                <li>
                                    <a href="<?php echo get_term_link($main_category); ?>" class="hover:text-white transition-colors duration-300">
                                        <?php echo esc_html($main_category->name); ?>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><i class="fas fa-chevron-right text-xs"></i></li>
                                <li class="text-white font-semibold">
                                    <?php echo mb_strimwidth(get_the_title(), 0, 40, '...'); ?>
                                </li>
                            </ol>
                        </nav>
                        
                        <!-- ステータスバッジ群 -->
                        <div class="flex flex-wrap gap-3 mb-8 animate-fadeInUp" style="animation-delay: 0.2s;">
                            
                            <?php if ($is_featured): ?>
                            <span class="inline-flex items-center px-4 py-2 bg-yellow-400 text-yellow-900 rounded-full font-bold text-sm shadow-lg animate-pulse-slow">
                                <i class="fas fa-star mr-2"></i>
                                注目の助成金
                            </span>
                            <?php endif; ?>
                            
                            <!-- ステータス表示 -->
                            <?php 
                            $status_badges = array(
                                'open' => array('bg' => 'bg-green-500', 'icon' => 'fas fa-circle', 'text' => '募集中', 'animation' => 'animate-pulse-slow'),
                                'closed' => array('bg' => 'bg-red-500', 'icon' => 'fas fa-times-circle', 'text' => '募集終了', 'animation' => ''),
                                'upcoming' => array('bg' => 'bg-blue-500', 'icon' => 'fas fa-clock', 'text' => '募集予定', 'animation' => 'animate-bounce-slow'),
                                'suspended' => array('bg' => 'bg-gray-500', 'icon' => 'fas fa-pause-circle', 'text' => '一時停止', 'animation' => '')
                            );
                            
                            $status_config = $status_badges[$application_status] ?? $status_badges['open'];
                            ?>
                            <span class="inline-flex items-center px-4 py-2 <?php echo $status_config['bg']; ?> text-white rounded-full font-bold text-sm shadow-lg <?php echo $status_config['animation']; ?>">
                                <i class="<?php echo $status_config['icon']; ?> mr-2"></i>
                                <?php echo $status_config['text']; ?>
                            </span>
                            
                            <!-- 締切警告 -->
                            <?php if ($days_remaining > 0): ?>
                                <?php if ($deadline_status === 'critical'): ?>
                                <span class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-full font-bold text-sm shadow-lg animate-bounce-slow">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    緊急！残り<?php echo $days_remaining; ?>日
                                </span>
                                <?php elseif ($deadline_status === 'urgent'): ?>
                                <span class="inline-flex items-center px-4 py-2 bg-orange-500 text-white rounded-full font-bold text-sm shadow-lg animate-pulse-slow">
                                    <i class="fas fa-clock mr-2"></i>
                                    締切間近！残り<?php echo $days_remaining; ?>日
                                </span>
                                <?php elseif ($deadline_status === 'soon'): ?>
                                <span class="inline-flex items-center px-4 py-2 bg-yellow-500 text-yellow-900 rounded-full font-medium text-sm shadow-lg">
                                    <i class="fas fa-hourglass-half mr-2"></i>
                                    残り<?php echo $days_remaining; ?>日
                                </span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- 難易度バッジ -->
                            <span class="inline-flex items-center px-4 py-2 <?php echo $difficulty_info['bg_color']; ?> text-white rounded-full font-medium text-sm shadow-lg">
                                <?php for ($i = 1; $i <= $difficulty_info['stars']; $i++): ?>
                                    <i class="fas fa-star mr-1"></i>
                                <?php endfor; ?>
                                申請<?php echo esc_html($difficulty_info['label']); ?>
                            </span>
                            
                        </div>
                        
                        <!-- メインタイトル -->
                        <h1 class="hero-title text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-black mb-8 leading-tight text-white animate-fadeInUp" style="animation-delay: 0.3s;">
                            <?php the_title(); ?>
                        </h1>
                        
                        <!-- AI要約セクション -->
                        <?php if ($ai_summary): ?>
                        <div class="glass-card rounded-3xl p-8 mb-10 animate-fadeInUp" style="animation-delay: 0.4s;">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-pink-400 rounded-2xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-robot text-white text-2xl"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center mb-4">
                                        <h2 class="text-xl font-bold text-white mr-3">AI要約</h2>
                                        <span class="inline-flex items-center px-3 py-1 bg-purple-500/30 text-purple-100 rounded-full text-xs font-medium">
                                            <i class="fas fa-sparkles mr-1"></i>
                                            自動生成
                                        </span>
                                    </div>
                                    <div class="text-white/90 leading-relaxed text-lg">
                                        <?php echo wp_kses_post($ai_summary); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- キーメトリクス（拡張版） -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6 mb-10 animate-fadeInUp" style="animation-delay: 0.5s;">
                            
                            <!-- 助成金額 -->
                            <div class="metric-card glass-card rounded-2xl p-6 text-center min-h-[140px] flex flex-col justify-center">
                                <div class="text-white/70 text-sm mb-2">
                                    <i class="fas fa-yen-sign mr-1"></i>最大助成額
                                </div>
                                <div class="text-2xl lg:text-3xl font-black text-white mb-1">
                                    <?php echo esc_html($formatted_amount); ?>
                                </div>
                                <?php if ($subsidy_rate): ?>
                                <div class="text-xs text-white/70">
                                    補助率: <span class="font-semibold"><?php echo esc_html($subsidy_rate); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($amount_note): ?>
                                <div class="text-xs text-white/60 mt-1">
                                    <?php echo esc_html($amount_note); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 締切情報 -->
                            <div class="metric-card glass-card rounded-2xl p-6 text-center min-h-[140px] flex flex-col justify-center">
                                <div class="text-white/70 text-sm mb-2">
                                    <i class="fas fa-calendar-alt mr-1"></i>申請締切
                                </div>
                                <div class="text-2xl lg:text-3xl font-black text-white mb-1">
                                    <?php if ($days_remaining > 0): ?>
                                        <?php echo $days_remaining; ?><span class="text-lg">日</span>
                                    <?php else: ?>
                                        <span class="text-lg"><?php echo esc_html($deadline_formatted); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-white/70">
                                    <?php if ($days_remaining > 0): ?>
                                        残り時間
                                    <?php else: ?>
                                        申請期限
                                    <?php endif; ?>
                                </div>
                                <?php if ($deadline_note): ?>
                                <div class="text-xs text-white/60 mt-1">
                                    <?php echo esc_html($deadline_note); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 採択率 -->
                            <div class="metric-card glass-card rounded-2xl p-6 text-center min-h-[140px] flex flex-col justify-center">
                                <div class="text-white/70 text-sm mb-2">
                                    <i class="fas fa-chart-line mr-1"></i>採択率
                                </div>
                                <div class="text-2xl lg:text-3xl font-black text-white mb-1">
                                    <?php echo $grant_success_rate; ?><span class="text-lg">%</span>
                                </div>
                                <div class="text-xs text-white/70">
                                    <?php 
                                    if ($grant_success_rate >= 70) {
                                        echo '高い確率';
                                    } elseif ($grant_success_rate >= 50) {
                                        echo '標準的';
                                    } elseif ($grant_success_rate >= 30) {
                                        echo '競争率あり';
                                    } else {
                                        echo '高競争';
                                    }
                                    ?>
                                </div>
                                <?php if ($similar_success_rate): ?>
                                <div class="text-xs text-white/60 mt-1">
                                    類似: <?php echo $similar_success_rate; ?>%
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 申請難易度 -->
                            <div class="metric-card glass-card rounded-2xl p-6 text-center min-h-[140px] flex flex-col justify-center">
                                <div class="text-white/70 text-sm mb-2">
                                    <i class="fas fa-layer-group mr-1"></i>申請難易度
                                </div>
                                <div class="flex justify-center mb-2">
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $difficulty_info['stars'] ? 'text-yellow-400' : 'text-white/30'; ?> text-xl mx-1"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-xs text-white/70">
                                    <?php echo esc_html($difficulty_info['label']); ?>レベル
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- CTAボタン群 -->
                        <div class="flex flex-wrap gap-4 animate-fadeInUp" style="animation-delay: 0.6s;">
                            
                            <!-- お気に入りボタン -->
                            <button id="favoriteBtn" class="favorite-btn group px-6 py-4 glass-card rounded-2xl font-semibold transition-all duration-300 hover:scale-105 flex items-center" data-post-id="<?php echo $post_id; ?>">
                                <i id="favoriteIcon" class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart mr-3 text-2xl text-pink-400 group-hover:scale-110 transition-transform duration-300"></i>
                                <span id="favoriteText" class="text-white">
                                    <?php echo $is_favorite ? 'お気に入り済み' : 'お気に入りに追加'; ?>
                                </span>
                            </button>
                            
                            <!-- 公式サイトボタン -->
                            <?php if ($official_url || $external_link): ?>
                            <a href="<?php echo esc_url($official_url ?: $external_link); ?>" target="_blank" rel="noopener noreferrer" class="group px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-2xl font-semibold transition-all duration-300 hover:scale-105 flex items-center shadow-lg">
                                <i class="fas fa-external-link-alt mr-3 text-xl group-hover:scale-110 transition-transform duration-300"></i>
                                公式サイトで詳細を見る
                            </a>
                            <?php endif; ?>
                            
                            <!-- シェアボタン -->
                            <button id="shareBtn" class="group px-6 py-4 glass-card rounded-2xl font-semibold text-white transition-all duration-300 hover:scale-105 flex items-center">
                                <i class="fas fa-share-alt mr-3 text-xl group-hover:scale-110 transition-transform duration-300"></i>
                                この助成金をシェア
                            </button>
                            
                            <!-- 印刷ボタン -->
                            <button id="printBtn" class="group px-6 py-4 glass-card rounded-2xl font-semibold text-white transition-all duration-300 hover:scale-105 flex items-center">
                                <i class="fas fa-print mr-3 text-xl group-hover:scale-110 transition-transform duration-300"></i>
                                印刷用表示
                            </button>
                            
                        </div>
                        
                    </div>
                </div>
            </div>
        </section>
        
        <!-- メインコンテンツエリア -->
        <section class="main-content py-16 bg-gray-50">
            <div class="container mx-auto px-4 lg:px-6">
                <div class="max-w-7xl mx-auto">
                    <div class="grid lg:grid-cols-3 gap-8 xl:gap-12">
                        
                        <!-- 左側：メインコンテンツ -->
                        <div class="lg:col-span-2 space-y-8">
                            
                            <!-- タブナビゲーション付きコンテンツ -->
                            <div class="neo-card overflow-hidden animate-slideInLeft">
                                
                                <!-- タブヘッダー -->
                                <div class="border-b border-gray-200 bg-gradient-to-r from-purple-50 to-blue-50">
                                    <nav class="flex -mb-px overflow-x-auto" role="tablist">
                                        <button class="tab-btn px-6 py-4 text-sm font-semibold text-gray-600 border-b-3 border-transparent hover:text-purple-600 hover:border-purple-300 focus:outline-none whitespace-nowrap transition-all duration-300 active" data-tab="overview">
                                            <i class="fas fa-info-circle mr-2"></i>概要
                                        </button>
                                        <button class="tab-btn px-6 py-4 text-sm font-semibold text-gray-600 border-b-3 border-transparent hover:text-purple-600 hover:border-purple-300 focus:outline-none whitespace-nowrap transition-all duration-300" data-tab="eligibility">
                                            <i class="fas fa-users mr-2"></i>対象・条件
                                        </button>
                                        <button class="tab-btn px-6 py-4 text-sm font-semibold text-gray-600 border-b-3 border-transparent hover:text-purple-600 hover:border-purple-300 focus:outline-none whitespace-nowrap transition-all duration-300" data-tab="application">
                                            <i class="fas fa-clipboard-list mr-2"></i>申請方法
                                        </button>
                                        <button class="tab-btn px-6 py-4 text-sm font-semibold text-gray-600 border-b-3 border-transparent hover:text-purple-600 hover:border-purple-300 focus:outline-none whitespace-nowrap transition-all duration-300" data-tab="documents">
                                            <i class="fas fa-file-alt mr-2"></i>必要書類
                                        </button>
                                        <button class="tab-btn px-6 py-4 text-sm font-semibold text-gray-600 border-b-3 border-transparent hover:text-purple-600 hover:border-purple-300 focus:outline-none whitespace-nowrap transition-all duration-300" data-tab="tips">
                                            <i class="fas fa-lightbulb mr-2"></i>申請のコツ
                                        </button>
                                    </nav>
                                </div>
                                
                                <!-- タブコンテンツ -->
                                <div class="p-8 lg:p-10">
                                    
                                    <!-- 概要タブ -->
                                    <div id="overview" class="tab-content active">
                                        <div class="mb-8">
                                            <h2 class="text-3xl font-bold mb-6 gradient-text flex items-center">
                                                <i class="fas fa-info-circle mr-4 text-blue-500"></i>
                                                助成金概要
                                            </h2>
                                            
                                            <?php if ($organization): ?>
                                            <div class="mb-6 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200">
                                                <div class="flex items-center mb-3">
                                                    <i class="fas fa-building text-blue-500 text-xl mr-3"></i>
                                                    <h3 class="text-lg font-bold text-blue-900">実施機関</h3>
                                                </div>
                                                <p class="text-blue-800 text-lg font-medium"><?php echo esc_html($organization); ?></p>
                                                <?php if ($organization_type): ?>
                                                <p class="text-blue-600 text-sm mt-2">
                                                    <i class="fas fa-tag mr-1"></i><?php echo esc_html($organization_type); ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="prose prose-lg prose-blue max-w-none">
                                                <?php 
                                                $content = get_the_content();
                                                if ($content) {
                                                    echo apply_filters('the_content', $content);
                                                } else {
                                                    echo '<p class="text-gray-600">詳細な概要は公式サイトをご確認ください。</p>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <!-- 重要なポイント -->
                                        <div class="grid md:grid-cols-2 gap-6 mt-8">
                                            <div class="bg-green-50 rounded-2xl p-6 border border-green-200">
                                                <h4 class="text-lg font-bold text-green-800 mb-3 flex items-center">
                                                    <i class="fas fa-check-circle mr-2"></i>
                                                    メリット
                                                </h4>
                                                <ul class="text-green-700 space-y-2">
                                                    <li class="flex items-start">
                                                        <i class="fas fa-plus text-green-500 mt-1 mr-2"></i>
                                                        返済不要の資金調達
                                                    </li>
                                                    <li class="flex items-start">
                                                        <i class="fas fa-plus text-green-500 mt-1 mr-2"></i>
                                                        事業成長の加速
                                                    </li>
                                                    <li class="flex items-start">
                                                        <i class="fas fa-plus text-green-500 mt-1 mr-2"></i>
                                                        社会的信用の向上
                                                    </li>
                                                </ul>
                                            </div>
                                            
                                            <div class="bg-yellow-50 rounded-2xl p-6 border border-yellow-200">
                                                <h4 class="text-lg font-bold text-yellow-800 mb-3 flex items-center">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    注意点
                                                </h4>
                                                <ul class="text-yellow-700 space-y-2">
                                                    <li class="flex items-start">
                                                        <i class="fas fa-info text-yellow-500 mt-1 mr-2"></i>
                                                        事業計画の詳細な作成が必要
                                                    </li>
                                                    <li class="flex items-start">
                                                        <i class="fas fa-info text-yellow-500 mt-1 mr-2"></i>
                                                        報告義務がある場合があります
                                                    </li>
                                                    <li class="flex items-start">
                                                        <i class="fas fa-info text-yellow-500 mt-1 mr-2"></i>
                                                        適切な経費管理が必要
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 対象・条件タブ -->
                                    <div id="eligibility" class="tab-content">
                                        <h2 class="text-3xl font-bold mb-6 gradient-text flex items-center">
                                            <i class="fas fa-users mr-4 text-green-500"></i>
                                            対象者・申請条件
                                        </h2>
                                        
                                        <?php if ($grant_target): ?>
                                        <div class="mb-8">
                                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                                <i class="fas fa-bullseye text-green-500 mr-3"></i>
                                                対象事業者
                                            </h3>
                                            <div class="bg-green-50 rounded-2xl p-6 border border-green-200">
                                                <div class="text-green-800 leading-relaxed">
                                                    <?php echo wp_kses_post(nl2br($grant_target)); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($eligible_expenses): ?>
                                        <div class="mb-8">
                                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                                <i class="fas fa-receipt text-blue-500 mr-3"></i>
                                                対象経費
                                            </h3>
                                            <div class="bg-blue-50 rounded-2xl p-6 border border-blue-200">
                                                <div class="text-blue-800 leading-relaxed">
                                                    <?php echo wp_kses_post(nl2br($eligible_expenses)); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- インタラクティブ条件チェック -->
                                        <div class="mt-8">
                                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                                <i class="fas fa-tasks text-purple-500 mr-3"></i>
                                                申請前条件チェック
                                            </h3>
                                            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                                                <div class="space-y-4" id="eligibilityChecklist">
                                                    <label class="flex items-start p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300 group">
                                                        <input type="checkbox" class="mt-1 mr-4 h-5 w-5 text-green-600 rounded focus:ring-2 focus:ring-green-500 transition-colors duration-300">
                                                        <div class="flex-1">
                                                            <span class="font-medium text-gray-900 group-hover:text-gray-700">対象事業者の要件を満たしている</span>
                                                            <p class="text-sm text-gray-600 mt-1">法人格、事業規模、業種などの基本要件をご確認ください</p>
                                                        </div>
                                                    </label>
                                                    
                                                    <label class="flex items-start p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300 group">
                                                        <input type="checkbox" class="mt-1 mr-4 h-5 w-5 text-green-600 rounded focus:ring-2 focus:ring-green-500 transition-colors duration-300">
                                                        <div class="flex-1">
                                                            <span class="font-medium text-gray-900 group-hover:text-gray-700">必要書類の準備が可能</span>
                                                            <p class="text-sm text-gray-600 mt-1">申請書、決算書、事業計画書などの準備状況をご確認ください</p>
                                                        </div>
                                                    </label>
                                                    
                                                    <label class="flex items-start p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300 group">
                                                        <input type="checkbox" class="mt-1 mr-4 h-5 w-5 text-green-600 rounded focus:ring-2 focus:ring-green-500 transition-colors duration-300">
                                                        <div class="flex-1">
                                                            <span class="font-medium text-gray-900 group-hover:text-gray-700">申請期限内の提出が可能</span>
                                                            <p class="text-sm text-gray-600 mt-1">書類作成から提出まで十分な時間的余裕があることを確認してください</p>
                                                        </div>
                                                    </label>
                                                    
                                                    <label class="flex items-start p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300 group">
                                                        <input type="checkbox" class="mt-1 mr-4 h-5 w-5 text-green-600 rounded focus:ring-2 focus:ring-green-500 transition-colors duration-300">
                                                        <div class="flex-1">
                                                            <span class="font-medium text-gray-900 group-hover:text-gray-700">事業計画が明確である</span>
                                                            <p class="text-sm text-gray-600 mt-1">何にどれくらいの費用を使うか、具体的な計画が立っている</p>
                                                        </div>
                                                    </label>
                                                </div>
                                                
                                                <div id="checklistResult" class="mt-6 p-4 rounded-xl hidden">
                                                    <div id="resultContent"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 申請方法タブ -->
                                    <div id="application" class="tab-content">
                                        <h2 class="text-3xl font-bold mb-6 gradient-text flex items-center">
                                            <i class="fas fa-clipboard-list mr-4 text-purple-500"></i>
                                            申請方法・手順
                                        </h2>
                                        
                                        <div class="grid md:grid-cols-2 gap-8 mb-8">
                                            <!-- 申請方法 -->
                                            <?php if ($application_method): ?>
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                                    <i class="fas fa-laptop text-purple-500 mr-3"></i>
                                                    申請方法
                                                </h3>
                                                <div class="bg-purple-50 rounded-2xl p-6 border border-purple-200">
                                                    <div class="inline-flex items-center px-4 py-2 bg-purple-500 text-white rounded-full font-bold">
                                                        <i class="fas fa-desktop mr-2"></i>
                                                        <?php echo esc_html($application_method); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- 申請期間 -->
                                            <?php if ($application_period): ?>
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                                    <i class="fas fa-calendar-check text-blue-500 mr-3"></i>
                                                    申請期間
                                                </h3>
                                                <div class="bg-blue-50 rounded-2xl p-6 border border-blue-200">
                                                    <div class="text-blue-800 font-medium">
                                                        <?php echo esc_html($application_period); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- ステップバイステップガイド -->
                                        <div class="mt-8">
                                            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                                                <i class="fas fa-route text-green-500 mr-3"></i>
                                                申請の流れ
                                            </h3>
                                            <div class="space-y-6">
                                                
                                                <div class="flex items-start group hover:bg-green-50 p-4 rounded-2xl transition-colors duration-300">
                                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform duration-300">
                                                        1
                                                    </div>
                                                    <div class="ml-6 flex-1">
                                                        <h4 class="text-lg font-bold text-gray-900 mb-2">情報収集・要件確認</h4>
                                                        <p class="text-gray-700 mb-3">公募要領を詳しく確認し、対象要件や必要書類をリストアップします。</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">公募要領確認</span>
                                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">対象要件チェック</span>
                                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">書類リスト作成</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-start group hover:bg-green-50 p-4 rounded-2xl transition-colors duration-300">
                                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform duration-300">
                                                        2
                                                    </div>
                                                    <div class="ml-6 flex-1">
                                                        <h4 class="text-lg font-bold text-gray-900 mb-2">申請書類の準備</h4>
                                                        <p class="text-gray-700 mb-3">事業計画書、申請書、添付書類を準備・作成します。</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">事業計画書作成</span>
                                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">申請書記入</span>
                                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">添付書類収集</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-start group hover:bg-green-50 p-4 rounded-2xl transition-colors duration-300">
                                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform duration-300">
                                                        3
                                                    </div>
                                                    <div class="ml-6 flex-1">
                                                        <h4 class="text-lg font-bold text-gray-900 mb-2">申請提出</h4>
                                                        <p class="text-gray-700 mb-3">オンラインシステムまたは郵送で申請書類を提出します。</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">最終確認</span>
                                                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">システム入力</span>
                                                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">提出完了</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-start group hover:bg-green-50 p-4 rounded-2xl transition-colors duration-300">
                                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-2xl flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform duration-300">
                                                        4
                                                    </div>
                                                    <div class="ml-6 flex-1">
                                                        <h4 class="text-lg font-bold text-gray-900 mb-2">審査・結果通知</h4>
                                                        <p class="text-gray-700 mb-3">書面審査、場合によっては面接審査を経て結果が通知されます。</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">書面審査</span>
                                                            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">面接審査</span>
                                                            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">結果通知</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-start group hover:bg-green-50 p-4 rounded-2xl transition-colors duration-300">
                                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 text-white rounded-2xl flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform duration-300">
                                                        5
                                                    </div>
                                                    <div class="ml-6 flex-1">
                                                        <h4 class="text-lg font-bold text-gray-900 mb-2">交付決定・事業実施</h4>
                                                        <p class="text-gray-700 mb-3">採択後、交付決定を受けて事業を実施し、実績報告を行います。</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">交付申請</span>
                                                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">事業実施</span>
                                                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">実績報告</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 必要書類タブ -->
                                    <div id="documents" class="tab-content">
                                        <h2 class="text-3xl font-bold mb-6 gradient-text flex items-center">
                                            <i class="fas fa-file-alt mr-4 text-orange-500"></i>
                                            必要書類
                                        </h2>
                                        
                                        <?php if ($required_documents): ?>
                                        <div class="bg-orange-50 rounded-2xl p-8 border border-orange-200 mb-8">
                                            <h3 class="text-xl font-bold text-orange-900 mb-4">
                                                <i class="fas fa-list-check mr-2"></i>
                                                提出書類一覧
                                            </h3>
                                            <div class="text-orange-800 leading-relaxed">
                                                <?php echo wp_kses_post(nl2br($required_documents)); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- 書類準備チェックリスト -->
                                        <div class="mt-8">
                                            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                                                <i class="fas fa-clipboard-check text-green-500 mr-3"></i>
                                                書類準備チェックリスト
                                            </h3>
                                            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                                                <div class="grid md:grid-cols-2 gap-4">
                                                    
                                                    <label class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300">
                                                        <input type="checkbox" class="mr-3 h-5 w-5 text-orange-600 rounded focus:ring-2 focus:ring-orange-500">
                                                        <span class="font-medium">申請書（指定様式）</span>
                                                    </label>
                                                    
                                                    <label class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300">
                                                        <input type="checkbox" class="mr-3 h-5 w-5 text-orange-600 rounded focus:ring-2 focus:ring-orange-500">
                                                        <span class="font-medium">事業計画書</span>
                                                    </label>
                                                    
                                                    <label class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300">
                                                        <input type="checkbox" class="mr-3 h-5 w-5 text-orange-600 rounded focus:ring-2 focus:ring-orange-500">
                                                        <span class="font-medium">決算書（直近2期分）</span>
                                                    </label>
                                                    
                                                    <label class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300">
                                                        <input type="checkbox" class="mr-3 h-5 w-5 text-orange-600 rounded focus:ring-2 focus:ring-orange-500">
                                                        <span class="font-medium">会社概要・登記簿謄本</span>
                                                    </label>
                                                    
                                                    <label class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300">
                                                        <input type="checkbox" class="mr-3 h-5 w-5 text-orange-600 rounded focus:ring-2 focus:ring-orange-500">
                                                        <span class="font-medium">見積書・カタログ等</span>
                                                    </label>
                                                    
                                                    <label class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors duration-300">
                                                        <input type="checkbox" class="mr-3 h-5 w-5 text-orange-600 rounded focus:ring-2 focus:ring-orange-500">
                                                        <span class="font-medium">その他指定書類</span>
                                                    </label>
                                                    
                                                </div>
                                                
                                                <div class="mt-6 p-4 bg-blue-50 rounded-xl">
                                                    <div class="flex items-center text-blue-800">
                                                        <i class="fas fa-info-circle mr-2"></i>
                                                        <span class="font-medium">書類作成のポイント</span>
                                                    </div>
                                                    <ul class="mt-2 text-blue-700 text-sm space-y-1">
                                                        <li>• 公募要領の最新版を必ず確認してください</li>
                                                        <li>• 指定様式がある場合は必ずそちらを使用してください</li>
                                                        <li>• 記入漏れや不備がないよう複数回チェックしましょう</li>
                                                        <li>• 提出前にコピーを取って保管しておきましょう</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 申請のコツタブ -->
                                    <div id="tips" class="tab-content">
                                        <h2 class="text-3xl font-bold mb-6 gradient-text flex items-center">
                                            <i class="fas fa-lightbulb mr-4 text-yellow-500"></i>
                                            申請のコツ
                                        </h2>
                                        
                                        <!-- 難易度別アドバイス -->
                                        <div class="mb-8 p-6 bg-gradient-to-r from-<?php echo $difficulty_info['color']; ?>-50 to-<?php echo $difficulty_info['color']; ?>-100 rounded-2xl border border-<?php echo $difficulty_info['color']; ?>-200">
                                            <h3 class="text-xl font-bold <?php echo $difficulty_info['text_color']; ?> mb-4 flex items-center">
                                                <i class="fas fa-star mr-2"></i>
                                                この助成金の申請アドバイス（<?php echo $difficulty_info['label']; ?>レベル）
                                            </h3>
                                            <p class="<?php echo $difficulty_info['text_color']; ?> mb-4"><?php echo esc_html($difficulty_info['description']); ?></p>
                                            <div class="bg-white/80 rounded-xl p-4">
                                                <p class="<?php echo $difficulty_info['text_color']; ?> font-medium">
                                                    <i class="fas fa-arrow-right mr-2"></i>
                                                    <?php echo esc_html($difficulty_info['tips']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- 成功のポイント -->
                                        <div class="grid md:grid-cols-2 gap-6">
                                            
                                            <div class="bg-green-50 rounded-2xl p-6 border border-green-200">
                                                <h4 class="text-lg font-bold text-green-800 mb-4 flex items-center">
                                                    <i class="fas fa-trophy mr-2"></i>
                                                    採択率を上げるポイント
                                                </h4>
                                                <ul class="text-green-700 space-y-3">
                                                    <li class="flex items-start">
                                                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                                        <div>
                                                            <span class="font-medium">明確な事業計画</span>
                                                            <p class="text-sm text-green-600">具体的で実現可能な計画を作成</p>
                                                        </div>
                                                    </li>
                                                    <li class="flex items-start">
                                                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                                        <div>
                                                            <span class="font-medium">社会的意義の明示</span>
                                                            <p class="text-sm text-green-600">地域や社会への貢献度をアピール</p>
                                                        </div>
                                                    </li>
                                                    <li class="flex items-start">
                                                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                                                        <div>
                                                            <span class="font-medium">適切な資金計画</span>
                                                            <p class="text-sm text-green-600">必要な経費を詳細に算出</p>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                            
                                            <div class="bg-red-50 rounded-2xl p-6 border border-red-200">
                                                <h4 class="text-lg font-bold text-red-800 mb-4 flex items-center">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    よくある失敗パターン
                                                </h4>
                                                <ul class="text-red-700 space-y-3">
                                                    <li class="flex items-start">
                                                        <i class="fas fa-times-circle text-red-500 mt-1 mr-3"></i>
                                                        <div>
                                                            <span class="font-medium">記入漏れ・書類不備</span>
                                                            <p class="text-sm text-red-600">提出前の最終確認を怠らない</p>
                                                        </div>
                                                    </li>
                                                    <li class="flex items-start">
                                                        <i class="fas fa-times-circle text-red-500 mt-1 mr-3"></i>
                                                        <div>
                                                            <span class="font-medium">抽象的な計画内容</span>
                                                            <p class="text-sm text-red-600">具体性と実現可能性の欠如</p>
                                                        </div>
                                                    </li>
                                                    <li class="flex items-start">
                                                        <i class="fas fa-times-circle text-red-500 mt-1 mr-3"></i>
                                                        <div>
                                                            <span class="font-medium">締切直前の申請</span>
                                                            <p class="text-sm text-red-600">余裕を持ったスケジュール設定を</p>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                            
                                        </div>
                                        
                                        <!-- 専門家のアドバイス -->
                                        <div class="mt-8 bg-blue-50 rounded-2xl p-6 border border-blue-200">
                                            <h4 class="text-lg font-bold text-blue-800 mb-4 flex items-center">
                                                <i class="fas fa-user-tie mr-2"></i>
                                                専門家からのアドバイス
                                            </h4>
                                            <div class="text-blue-700 leading-relaxed">
                                                <p class="mb-3">
                                                    助成金申請において最も重要なのは、<strong>事業の意義と計画の妥当性</strong>を明確に示すことです。
                                                    単に資金調達の手段として捉えるのではなく、社会課題解決や地域貢献といった観点から事業価値を伝えることが採択への近道となります。
                                                </p>
                                                <p>
                                                    また、<?php echo $difficulty_info['label']; ?>レベルの申請では、<?php echo esc_html($difficulty_info['tips']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                            
                            <!-- FAQ セクション -->
                            <div class="neo-card p-8 animate-slideInLeft" style="animation-delay: 0.2s;">
                                <h2 class="text-3xl font-bold mb-6 gradient-text flex items-center">
                                    <i class="fas fa-question-circle mr-4 text-indigo-500"></i>
                                    よくある質問
                                </h2>
                                
                                <div class="space-y-4">
                                    
                                    <div class="faq-item border border-gray-200 rounded-xl overflow-hidden">
                                        <button class="faq-toggle w-full px-6 py-4 text-left font-medium text-gray-900 hover:bg-gray-50 focus:outline-none flex justify-between items-center transition-colors duration-300">
                                            <span>申請に費用はかかりますか？</span>
                                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                        </button>
                                        <div class="faq-content hidden">
                                            <div class="px-6 py-4 text-gray-700 border-t border-gray-200 bg-gray-50">
                                                <p>申請自体に費用はかかりません。ただし、事業計画書の作成を外部のコンサルタントに依頼する場合や、必要書類の取得（登記簿謄本等）には別途費用が発生する可能性があります。</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="faq-item border border-gray-200 rounded-xl overflow-hidden">
                                        <button class="faq-toggle w-full px-6 py-4 text-left font-medium text-gray-900 hover:bg-gray-50 focus:outline-none flex justify-between items-center transition-colors duration-300">
                                            <span>採択後の流れはどうなりますか？</span>
                                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                        </button>
                                        <div class="faq-content hidden">
                                            <div class="px-6 py-4 text-gray-700 border-t border-gray-200 bg-gray-50">
                                                <p>採択通知後、交付申請を行い、事業を実施します。事業完了後は実績報告書を提出し、適正に実施されていることが確認されれば補助金が交付されます。期間中は適宜進捗報告が求められる場合があります。</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="faq-item border border-gray-200 rounded-xl overflow-hidden">
                                        <button class="faq-toggle w-full px-6 py-4 text-left font-medium text-gray-900 hover:bg-gray-50 focus:outline-none flex justify-between items-center transition-colors duration-300">
                                            <span>同じ助成金に複数回申請できますか？</span>
                                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                        </button>
                                        <div class="faq-content hidden">
                                            <div class="px-6 py-4 text-gray-700 border-t border-gray-200 bg-gray-50">
                                                <p>原則として、同一事業・同一内容での重複申請はできません。ただし、異なる事業内容であれば申請可能な場合があります。詳細は公募要領をご確認いただくか、実施機関にお問い合わせください。</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="faq-item border border-gray-200 rounded-xl overflow-hidden">
                                        <button class="faq-toggle w-full px-6 py-4 text-left font-medium text-gray-900 hover:bg-gray-50 focus:outline-none flex justify-between items-center transition-colors duration-300">
                                            <span>不採択の場合、理由を教えてもらえますか？</span>
                                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                        </button>
                                        <div class="faq-content hidden">
                                            <div class="px-6 py-4 text-gray-700 border-t border-gray-200 bg-gray-50">
                                                <p>多くの助成金では、不採択の詳細な理由は開示されません。ただし、一般的な評価項目や審査基準は公開されているため、次回申請の参考にすることができます。</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- 右側：サイドバー -->
                        <div class="lg:col-span-1 space-y-6">
                            
                            <!-- 重要情報カード（粘着） -->
                            <div class="neo-card p-6 bg-gradient-to-br from-purple-50 via-blue-50 to-indigo-50 border-2 border-purple-200 sticky top-8 animate-slideInRight">
                                <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                                    <i class="fas fa-star text-yellow-500 mr-3"></i>
                                    重要情報
                                </h3>
                                
                                <div class="space-y-6">
                                    
                                    <!-- 助成金額 -->
                                    <div class="text-center">
                                        <div class="text-sm text-gray-600 mb-2">最大助成額</div>
                                        <div class="text-4xl font-black gradient-text mb-2">
                                            <?php echo esc_html($formatted_amount); ?>
                                        </div>
                                        <?php if ($subsidy_rate): ?>
                                        <div class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                            <i class="fas fa-percentage mr-1"></i>
                                            補助率: <?php echo esc_html($subsidy_rate); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <hr class="border-purple-200">
                                    
                                    <!-- 締切カウントダウン -->
                                    <?php if ($days_remaining > 0): ?>
                                    <div class="text-center">
                                        <div class="text-sm text-gray-600 mb-2">申請締切まで</div>
                                        <div class="flex items-center justify-center mb-3">
                                            <div class="text-5xl font-black text-<?php echo $deadline_status === 'critical' ? 'red' : ($deadline_status === 'urgent' ? 'orange' : 'gray'); ?>-600">
                                                <?php echo $days_remaining; ?>
                                            </div>
                                            <div class="ml-2 text-lg text-gray-600 font-medium">日</div>
                                        </div>
                                        
                                        <!-- プログレスバー -->
                                        <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                                            <?php 
                                            $progress_percentage = max(5, min(100, (60 - $days_remaining) / 60 * 100));
                                            $progress_color = $deadline_status === 'critical' ? 'bg-red-500' : ($deadline_status === 'urgent' ? 'bg-orange-500' : 'bg-blue-500');
                                            ?>
                                            <div class="<?php echo $progress_color; ?> h-3 rounded-full transition-all duration-1000 ease-out" style="width: <?php echo $progress_percentage; ?>%"></div>
                                        </div>
                                        
                                        <div class="text-xs text-gray-500">
                                            <?php echo esc_html($deadline_formatted); ?>
                                        </div>
                                        
                                        <?php if ($deadline_status === 'critical'): ?>
                                        <div class="mt-3 p-3 bg-red-100 border border-red-300 rounded-lg">
                                            <div class="text-red-800 text-sm font-bold flex items-center">
                                                <i class="fas fa-exclamation-triangle mr-2 animate-bounce-slow"></i>
                                                緊急！すぐに申請準備を開始してください
                                            </div>
                                        </div>
                                        <?php elseif ($deadline_status === 'urgent'): ?>
                                        <div class="mt-3 p-3 bg-orange-100 border border-orange-300 rounded-lg">
                                            <div class="text-orange-800 text-sm font-medium flex items-center">
                                                <i class="fas fa-clock mr-2"></i>
                                                申請準備はお早めに
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <hr class="border-purple-200">
                                    <?php endif; ?>
                                    
                                    <!-- 採択率 -->
                                    <?php if ($grant_success_rate > 0): ?>
                                    <div>
                                        <div class="text-sm text-gray-600 mb-3">過去の採択率</div>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-3xl font-bold text-green-600"><?php echo $grant_success_rate; ?>%</span>
                                            <span class="text-xs px-2 py-1 rounded-full <?php 
                                                echo $grant_success_rate >= 70 ? 'bg-green-100 text-green-800' : 
                                                    ($grant_success_rate >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                            ?>">
                                                <?php 
                                                if ($grant_success_rate >= 70) echo '高確率';
                                                elseif ($grant_success_rate >= 50) echo '標準';
                                                else echo '競争激';
                                                ?>
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-1000 ease-out" style="width: <?php echo $grant_success_rate; ?>%"></div>
                                        </div>
                                        <?php if ($similar_success_rate): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            類似助成金: <?php echo $similar_success_rate; ?>%
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <hr class="border-purple-200">
                                    <?php endif; ?>
                                    
                                    <!-- 申請難易度 -->
                                    <div>
                                        <div class="text-sm text-gray-600 mb-3">申請難易度</div>
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex">
                                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $difficulty_info['stars'] ? 'text-' . $difficulty_info['color'] . '-500' : 'text-gray-300'; ?> text-xl"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-sm font-bold <?php echo $difficulty_info['text_color']; ?>">
                                                <?php echo esc_html($difficulty_info['label']); ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 leading-relaxed"><?php echo esc_html($difficulty_info['description']); ?></p>
                                    </div>
                                    
                                    <!-- 問い合わせ先 -->
                                    <?php if ($contact_info): ?>
                                    <hr class="border-purple-200">
                                    <div>
                                        <div class="text-sm text-gray-600 mb-3 flex items-center">
                                            <i class="fas fa-phone mr-2"></i>
                                            お問い合わせ
                                        </div>
                                        <div class="text-sm text-gray-700 bg-white rounded-xl p-4 border border-gray-200 leading-relaxed">
                                            <?php echo wp_kses_post(nl2br($contact_info)); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                </div>
                                
                                <!-- CTAボタン -->
                                <div class="mt-8 space-y-3">
                                    <?php if ($official_url || $external_link): ?>
                                    <a href="<?php echo esc_url($official_url ?: $external_link); ?>" target="_blank" rel="noopener noreferrer" class="block w-full text-center px-6 py-4 bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white rounded-2xl font-bold hover:from-blue-700 hover:via-purple-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 shadow-lg animate-gradient">
                                        <i class="fas fa-external-link-alt mr-2"></i>
                                        公式サイトへ
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button id="sidebarFavoriteBtn" class="block w-full text-center px-6 py-4 bg-gradient-to-r from-pink-500 to-red-500 text-white rounded-2xl font-bold hover:from-pink-600 hover:to-red-600 transition-all duration-300 transform hover:scale-105 shadow-lg" data-post-id="<?php echo $post_id; ?>">
                                        <i id="sidebarFavoriteIcon" class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart mr-2"></i>
                                        <span id="sidebarFavoriteText"><?php echo $is_favorite ? 'お気に入り済み' : 'お気に入りに追加'; ?></span>
                                    </button>
                                </div>
                                
                            </div>
                            
                            <!-- カテゴリー・タグ -->
                            <div class="neo-card p-6 animate-slideInRight" style="animation-delay: 0.1s;">
                                
                                <!-- カテゴリー -->
                                <?php if ($categories && !is_wp_error($categories)): ?>
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-folder text-blue-500 mr-2"></i>
                                        カテゴリー
                                    </h3>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($categories as $category): ?>
                                        <a href="<?php echo get_term_link($category); ?>" class="inline-flex items-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-lg text-sm font-medium transition-colors duration-300">
                                            <i class="fas fa-folder-open mr-2"></i>
                                            <?php echo esc_html($category->name); ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- 都道府県 -->
                                <?php if ($prefectures && !is_wp_error($prefectures)): ?>
                                <div class="mb-6">
                                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                        対象地域
                                    </h3>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($prefectures as $prefecture): ?>
                                        <a href="<?php echo get_term_link($prefecture); ?>" class="inline-flex items-center px-3 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg text-sm font-medium transition-colors duration-300">
                                            <i class="fas fa-location-dot mr-2"></i>
                                            <?php echo esc_html($prefecture->name); ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- タグ -->
                                <?php if ($tags && !is_wp_error($tags)): ?>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-tags text-green-500 mr-2"></i>
                                        タグ
                                    </h3>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($tags as $tag): ?>
                                        <a href="<?php echo get_term_link($tag); ?>" class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 text-green-800 rounded-full text-sm transition-colors duration-300">
                                            #<?php echo esc_html($tag->name); ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                            </div>
                            
                            <!-- 関連助成金 -->
                            <div class="neo-card p-6 animate-slideInRight" style="animation-delay: 0.2s;">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-link text-purple-500 mr-2"></i>
                                    関連する助成金
                                    <span class="ml-2 text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full"><?php echo $related_grants_count; ?>件</span>
                                </h3>
                                
                                <div id="relatedGrants" class="space-y-3">
                                    <!-- ローディング表示 -->
                                    <div class="text-center py-8">
                                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                                        <p class="text-sm text-gray-500 mt-3">関連助成金を検索中...</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- シェアボタン（拡張版） -->
                            <div class="neo-card p-6 animate-slideInRight" style="animation-delay: 0.3s;">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-share-alt text-indigo-500 mr-2"></i>
                                    この情報をシェア
                                </h3>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title() . ' | 助成金情報'); ?>&hashtags=助成金,補助金,<?php echo $main_category ? urlencode($main_category->name) : ''; ?>" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center px-4 py-3 bg-blue-400 hover:bg-blue-500 text-white rounded-xl text-sm font-medium transition-all duration-300 transform hover:scale-105">
                                        <i class="fab fa-twitter mr-2"></i>
                                        Twitter
                                    </a>
                                    
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-all duration-300 transform hover:scale-105">
                                        <i class="fab fa-facebook-f mr-2"></i>
                                        Facebook
                                    </a>
                                    
                                    <a href="https://line.me/R/msg/text/?<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center px-4 py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl text-sm font-medium transition-all duration-300 transform hover:scale-105">
                                        <i class="fab fa-line mr-2"></i>
                                        LINE
                                    </a>
                                    
                                    <button id="copyUrlBtn" class="flex items-center justify-center px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl text-sm font-medium transition-all duration-300 transform hover:scale-105">
                                        <i class="fas fa-link mr-2"></i>
                                        URLコピー
                                    </button>
                                </div>
                                
                                <!-- QRコード生成ボタン -->
                                <button id="generateQRBtn" class="w-full mt-3 px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white rounded-xl text-sm font-medium hover:from-purple-600 hover:to-indigo-600 transition-all duration-300">
                                    <i class="fas fa-qrcode mr-2"></i>
                                    QRコード生成
                                </button>
                            </div>
                            
                            <!-- 統計情報 -->
                            <div class="neo-card p-6 bg-gray-50 animate-slideInRight" style="animation-delay: 0.4s;">
                                <h3 class="text-sm font-bold text-gray-600 mb-4 flex items-center">
                                    <i class="fas fa-chart-bar text-gray-500 mr-2"></i>
                                    統計情報
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">閲覧数</span>
                                        <span class="font-bold text-gray-800"><?php echo number_format($views_count); ?>回</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">更新日</span>
                                        <span class="font-medium text-gray-700"><?php echo get_the_modified_date('Y/m/d'); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">投稿日</span>
                                        <span class="font-medium text-gray-700"><?php echo get_the_date('Y/m/d'); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">ID</span>
                                        <span class="font-mono text-gray-700 text-xs">#<?php echo $post_id; ?></span>
                                    </div>
                                    <?php if ($priority_order < 100): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">優先度</span>
                                        <span class="font-medium text-purple-600">高 (<?php echo $priority_order; ?>)</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- サイト統計 -->
                                <?php if (!empty($site_stats)): ?>
                                <hr class="my-4 border-gray-300">
                                <div class="text-xs text-gray-500 space-y-1">
                                    <div>総助成金数: <span class="font-medium"><?php echo number_format($site_stats['total_grants'] ?? 0); ?>件</span></div>
                                    <div>募集中: <span class="font-medium"><?php echo number_format($site_stats['active_grants'] ?? 0); ?>件</span></div>
                                    <?php if (isset($site_stats['avg_success_rate'])): ?>
                                    <div>平均採択率: <span class="font-medium"><?php echo $site_stats['avg_success_rate']; ?>%</span></div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
            </div>
        </section>
        
        <!-- フローティングアクションボタン -->
        <div class="fixed bottom-8 right-8 z-40 space-y-3 no-print">
            
            <!-- スクロールトップ -->
            <button id="scrollToTop" class="hidden w-14 h-14 bg-gradient-to-br from-gray-600 to-gray-800 hover:from-gray-700 hover:to-gray-900 text-white rounded-full shadow-xl transition-all duration-300 transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-gray-300">
                <i class="fas fa-arrow-up text-xl"></i>
            </button>
            
            <!-- ヘルプボタン -->
            <button id="helpBtn" class="w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white rounded-full shadow-xl transition-all duration-300 transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-blue-300">
                <i class="fas fa-question text-xl"></i>
            </button>
            
            <!-- 印刷ボタン -->
            <button id="floatingPrintBtn" class="w-14 h-14 bg-gradient-to-br from-green-600 to-green-800 hover:from-green-700 hover:to-green-900 text-white rounded-full shadow-xl transition-all duration-300 transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-green-300">
                <i class="fas fa-print text-xl"></i>
            </button>
            
        </div>
        
    </div>
    
    <!-- モーダル：シェア（拡張版） -->
    <div id="shareModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden no-print">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-8 transform transition-all duration-300 scale-95 opacity-0 modal-content">
                
                <!-- ヘッダー -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold gradient-text">この助成金をシェア</h3>
                    <button id="closeShareModal" class="text-gray-400 hover:text-gray-600 transition-colors duration-300 focus:outline-none">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <!-- URL入力エリア -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ページURL</label>
                    <div class="flex items-center bg-gray-50 rounded-xl border border-gray-200 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-200">
                        <input type="text" id="shareUrl" value="<?php echo get_permalink(); ?>" readonly class="flex-1 bg-transparent border-none focus:outline-none text-sm px-4 py-3 select-all">
                        <button id="copyShareUrl" class="mx-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors duration-300 focus:outline-none">
                            <i class="fas fa-copy mr-1"></i>
                            コピー
                        </button>
                    </div>
                </div>
                
                <!-- ソーシャルメディア -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">ソーシャルメディア</label>
                    <div class="grid grid-cols-3 gap-4">
                        
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>&hashtags=助成金,<?php echo $main_category ? urlencode($main_category->name) : ''; ?>" target="_blank" class="flex flex-col items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors duration-300 group">
                            <i class="fab fa-twitter text-3xl text-blue-400 mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                            <span class="text-sm font-medium">Twitter</span>
                        </a>
                        
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="flex flex-col items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors duration-300 group">
                            <i class="fab fa-facebook text-3xl text-blue-600 mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                            <span class="text-sm font-medium">Facebook</span>
                        </a>
                        
                        <a href="https://line.me/R/msg/text/?<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" target="_blank" class="flex flex-col items-center p-4 bg-green-50 hover:bg-green-100 rounded-xl transition-colors duration-300 group">
                            <i class="fab fa-line text-3xl text-green-500 mb-2 group-hover:scale-110 transition-transform duration-300"></i>
                            <span class="text-sm font-medium">LINE</span>
                        </a>
                        
                    </div>
                </div>
                
                <!-- QRコード表示エリア -->
                <div id="qrCodeArea" class="hidden mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">QRコード</label>
                    <div class="text-center">
                        <div id="qrCodeContainer" class="inline-block p-4 bg-white border-2 border-dashed border-gray-300 rounded-xl">
                            <!-- QRコードがここに生成されます -->
                        </div>
                        <p class="text-xs text-gray-500 mt-2">スマートフォンでスキャンしてアクセス</p>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- モーダル：QRコード -->
    <div id="qrModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden no-print">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-3xl max-w-sm w-full p-8 transform transition-all duration-300 text-center">
                <h3 class="text-xl font-bold mb-6">QRコード</h3>
                <div id="qrDisplay" class="mb-6">
                    <!-- QRコードがここに表示されます -->
                </div>
                <button id="closeQRModal" class="w-full px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition-colors duration-300">
                    閉じる
                </button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // ===== グローバル変数 =====
        const postId = <?php echo $post_id; ?>;
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = '<?php echo wp_create_nonce('gi_ajax_nonce'); ?>';
        const currentUrl = '<?php echo get_permalink(); ?>';
        const pageTitle = <?php echo json_encode(get_the_title()); ?>;
        
        // ===== ユーティリティ関数 =====
        
        // トースト通知システム
        function showToast(message, type = 'success', duration = 4000) {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : 
                           type === 'error' ? 'bg-red-500' : 
                           type === 'warning' ? 'bg-orange-500' : 'bg-blue-500';
            
            toast.className = `fixed top-20 right-4 z-[9999] px-6 py-4 ${bgColor} text-white rounded-2xl shadow-2xl transform transition-all duration-300 translate-x-full max-w-sm`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-3 text-xl"></i>
                    <div class="flex-1">
                        <div class="font-medium">${message}</div>
                    </div>
                    <button class="ml-3 text-white/80 hover:text-white" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // アニメーション
            setTimeout(() => toast.classList.remove('translate-x-full'), 100);
            
            // 自動削除
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, duration);
        }
        
        // API フェッチヘルパー
        async function apiRequest(action, data = {}) {
            const formData = new URLSearchParams({
                action: action,
                nonce: nonce,
                ...data
            });
            
            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });
                
                const result = await response.json();
                return result;
            } catch (error) {
                console.error('API Request Error:', error);
                throw error;
            }
        }
        
        // ===== スクロール機能 =====
        
        // スクロール進行インジケーター
        let ticking = false;
        function updateScrollIndicator() {
            if (!ticking) {
                requestAnimationFrame(() => {
                    const scrolled = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                    document.getElementById('scrollIndicator').style.transform = `scaleX(${Math.min(scrolled / 100, 1)})`;
                    
                    // スクロールトップボタンの表示制御
                    const scrollTopBtn = document.getElementById('scrollToTop');
                    if (window.scrollY > 400) {
                        scrollTopBtn.classList.remove('hidden');
                    } else {
                        scrollTopBtn.classList.add('hidden');
                    }
                    
                    ticking = false;
                });
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', updateScrollIndicator, { passive: true });
        
        // スクロールトップボタン
        document.getElementById('scrollToTop').addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // ===== タブ機能 =====
        
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                // ボタンのアクティブ状態を切り替え
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-purple-500', 'text-purple-600', 'bg-purple-50');
                    btn.classList.add('border-transparent', 'text-gray-600');
                });
                
                this.classList.add('active', 'border-purple-500', 'text-purple-600', 'bg-purple-50');
                this.classList.remove('border-transparent', 'text-gray-600');
                
                // コンテンツの表示切り替え
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });
                
                const targetContent = document.getElementById(targetTab);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
                
                // タブ切り替え時にアナリティクス送信
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'tab_view', {
                        'tab_name': targetTab,
                        'grant_id': postId
                    });
                }
            });
        });
        
        // ===== FAQ アコーディオン =====
        
        const faqToggles = document.querySelectorAll('.faq-toggle');
        faqToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('i');
                const isOpen = !content.classList.contains('hidden');
                
                // 他のFAQを閉じる（アコーディオン効果）
                faqToggles.forEach(otherToggle => {
                    if (otherToggle !== this) {
                        const otherContent = otherToggle.nextElementSibling;
                        const otherIcon = otherToggle.querySelector('i');
                        otherContent.classList.add('hidden');
                        otherIcon.classList.remove('rotate-180');
                    }
                });
                
                // 現在のFAQをトグル
                content.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
                
                // アナリティクス送信
                if (typeof gtag !== 'undefined' && !isOpen) {
                    gtag('event', 'faq_open', {
                        'question': this.textContent.trim(),
                        'grant_id': postId
                    });
                }
            });
        });
        
        // ===== お気に入り機能 =====
        
        async function toggleFavorite(button) {
            const icon = button.querySelector('i');
            const textElement = button.querySelector('span');
            
            // アニメーション
            button.style.transform = 'scale(1.1)';
            setTimeout(() => {
                button.style.transform = 'scale(1)';
            }, 200);
            
            try {
                const result = await apiRequest('gi_toggle_favorite