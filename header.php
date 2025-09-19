<?php
/**
 * Grant Insight Perfect - Clean Header Template
 * シンプルで洗練されたヘッダー（助成金検索に特化）
 * 
 * @package Grant_Insight_Perfect
 * @version 7.1.0-clean
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    
    <?php wp_head(); ?>
    
    <!-- Preload fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===============================================
           CLEAN HEADER COMPLETE STYLES
           =============================================== */
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        .gi-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        @media (min-width: 1024px) {
            .gi-container {
                padding: 0 2rem;
            }
        }
        
        /* Header Base Styles */
        .gi-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateY(0);
        }
        
        .gi-header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-bottom-color: rgba(229, 231, 235, 1);
        }
        
        .gi-header.hidden {
            transform: translateY(-100%);
        }
        
        /* Announcement Bar */
        .gi-announcement {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .gi-announcement::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .gi-announcement a {
            color: white;
            text-decoration: underline;
            margin-left: 0.5rem;
            font-weight: 600;
        }
        
        .gi-announcement a:hover {
            text-decoration: none;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
        }
        
        /* Main Header Layout */
        .gi-header-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 4.5rem;
            padding: 0;
        }
        
        @media (min-width: 1024px) {
            .gi-header-main {
                height: 5.5rem;
            }
        }
        
        /* Logo Section */
        .gi-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .gi-logo:hover {
            transform: translateY(-2px);
            filter: drop-shadow(0 8px 20px rgba(102, 126, 234, 0.3));
        }
        
        .gi-logo-image {
            height: 3rem;
            width: auto;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        @media (min-width: 1024px) {
            .gi-logo-image {
                height: 3.5rem;
            }
        }
        
        .gi-logo-text h1 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }
        
        @media (min-width: 1024px) {
            .gi-logo-text h1 {
                font-size: 1.5rem;
            }
        }
        
        .gi-logo-text p {
            margin: 0.25rem 0 0 0;
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        @media (min-width: 1024px) {
            .gi-logo-text p {
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 640px) {
            .gi-logo-text {
                display: none;
            }
        }
        
        /* Desktop Navigation */
        .gi-nav {
            display: none;
            align-items: center;
            gap: 2.5rem;
            flex: 1;
            justify-content: center;
            margin: 0 2rem;
        }
        
        @media (min-width: 1024px) {
            .gi-nav {
                display: flex;
            }
        }
        
        .gi-nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 0.75rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .gi-nav-link:hover {
            color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .gi-nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .gi-nav-link:hover::before {
            width: 80%;
        }
        
        .gi-nav-link i {
            font-size: 0.875rem;
        }
        
        /* Current page indicator */
        .gi-nav-link.current {
            color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
        }
        
        .gi-nav-link.current::before {
            width: 80%;
        }
        
        /* Desktop Actions */
        .gi-actions {
            display: none;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }
        
        @media (min-width: 1024px) {
            .gi-actions {
                display: flex;
            }
        }
        
        .gi-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            background: transparent;
            white-space: nowrap;
        }
        
        .gi-btn-icon {
            width: 2.75rem;
            height: 2.75rem;
            padding: 0;
            color: #6b7280;
            justify-content: center;
            border-radius: 0.75rem;
        }
        
        .gi-btn-icon:hover {
            color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .gi-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .gi-btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .gi-btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .gi-btn-success:hover {
            background: linear-gradient(135deg, #0ea572 0%, #047857 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        /* Mobile Menu Button */
        .gi-mobile-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            color: #6b7280;
            background: transparent;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        @media (min-width: 1024px) {
            .gi-mobile-btn {
                display: none;
            }
        }
        
        .gi-mobile-btn:hover {
            color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: scale(1.05);
        }
        
        /* Search Bar */
        .gi-search-bar {
            border-top: 1px solid #e5e7eb;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            display: none;
            transform: translateY(-10px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .gi-search-bar.show {
            display: block;
            transform: translateY(0);
            opacity: 1;
        }
        
        .gi-search-form {
            padding: 1.5rem 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        @media (min-width: 768px) {
            .gi-search-form {
                flex-direction: row;
                align-items: end;
            }
        }
        
        .gi-search-input-wrapper {
            flex: 1;
            position: relative;
        }
        
        .gi-search-input {
            width: 100%;
            padding: 1rem 1.25rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
        }
        
        .gi-search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .gi-search-input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }
        
        .gi-search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.125rem;
        }
        
        .gi-search-filters {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .gi-search-select {
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            background: white;
            font-size: 0.875rem;
            font-weight: 500;
            min-width: 140px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .gi-search-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .gi-search-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            white-space: nowrap;
        }
        
        .gi-search-submit:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* Mobile Menu */
        .gi-mobile-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
        }
        
        .gi-mobile-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .gi-mobile-menu {
            position: fixed;
            top: 0;
            right: 0;
            height: 100%;
            width: 22rem;
            max-width: 85vw;
            background: white;
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            overflow-y: auto;
            z-index: 1000;
            box-shadow: -10px 0 25px rgba(0, 0, 0, 0.1);
        }
        
        .gi-mobile-menu.show {
            transform: translateX(0);
        }
        
        .gi-mobile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        .gi-mobile-title {
            font-size: 1.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .gi-mobile-close {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            background: transparent;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .gi-mobile-close:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            transform: scale(1.1);
        }
        
        .gi-mobile-search {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: #fafafa;
        }
        
        .gi-mobile-nav {
            padding: 1rem 0;
        }
        
        .gi-mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .gi-mobile-nav-link:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            color: #667eea;
            border-left-color: #667eea;
            padding-left: 2rem;
        }
        
        .gi-mobile-nav-link i {
            width: 1.25rem;
            text-align: center;
            font-size: 1rem;
        }
        
        .gi-mobile-actions {
            border-top: 1px solid #e5e7eb;
            padding: 1.5rem;
            background: #fafafa;
        }
        
        .gi-mobile-cta {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .gi-mobile-cta:hover {
            background: linear-gradient(135deg, #0ea572 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        /* Statistics Display */
        .gi-stats {
            display: none;
            align-items: center;
            gap: 2rem;
            font-size: 0.75rem;
            color: #6b7280;
            margin-left: 2rem;
        }
        
        @media (min-width: 1280px) {
            .gi-stats {
                display: flex;
            }
        }
        
        .gi-stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .gi-stat-number {
            font-weight: 700;
            color: #667eea;
        }
        
        /* Utility Classes */
        .gi-hidden {
            display: none !important;
        }
        
        .gi-flex {
            display: flex;
        }
        
        .gi-items-center {
            align-items: center;
        }
        
        .gi-justify-between {
            justify-content: space-between;
        }
        
        .gi-justify-center {
            justify-content: center;
        }
        
        .gi-space-x-2 > * + * {
            margin-left: 0.5rem;
        }
        
        .gi-space-x-3 > * + * {
            margin-left: 0.75rem;
        }
        
        .gi-w-full {
            width: 100%;
        }
        
        .gi-relative {
            position: relative;
        }
        
        /* Animation Classes */
        @keyframes gi-fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .gi-animate-fade-up {
            animation: gi-fadeInUp 0.4s ease-out;
        }
        
        @keyframes gi-slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .gi-animate-slide-right {
            animation: gi-slideInRight 0.3s ease-out;
        }
        
        /* Loading State */
        .gi-loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .gi-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #667eea;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Header -->
<header id="gi-site-header" class="gi-header">
    <!-- Announcement Bar -->
    <?php if (get_theme_mod('gi_show_announcement', true)): ?>
    <div class="gi-announcement">
        <div class="gi-container">
            <i class="fas fa-bullhorn" style="margin-right: 0.5rem;"></i>
            <?php echo esc_html(get_theme_mod('gi_announcement_text', '🎯 最新助成金情報を随時更新中！あなたにぴったりの支援制度を見つけよう')); ?>
            <?php if ($announcement_link = get_theme_mod('gi_announcement_link', get_post_type_archive_link('grant'))): ?>
                <a href="<?php echo esc_url($announcement_link); ?>">今すぐ検索する →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Header -->
    <div class="gi-container">
        <div class="gi-header-main">
            <!-- Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="gi-logo">
                <img src="http://joseikin-insight.com/wp-content/uploads/2025/09/名称未設定のデザイン.png" 
                     alt="<?php bloginfo('name'); ?>" 
                     class="gi-logo-image"
                     loading="eager">
                
                <div class="gi-logo-text">
                    <h1><?php bloginfo('name'); ?></h1>
                    <?php if ($tagline = get_bloginfo('description')): ?>
                        <p><?php echo esc_html($tagline); ?></p>
                    <?php endif; ?>
                </div>
            </a>
            
            <!-- Desktop Navigation -->
            <nav class="gi-nav" role="navigation">
                <?php
                // Get current page info for active state
                $current_url = home_url(add_query_arg(null, null));
                $home_url = home_url('/');
                $grants_url = get_post_type_archive_link('grant');
                
                $menu_items = array(
                    array(
                        'url' => $home_url, 
                        'title' => 'ホーム', 
                        'icon' => 'fas fa-home',
                        'current' => ($current_url === $home_url)
                    ),
                    array(
                        'url' => $grants_url, 
                        'title' => '助成金一覧', 
                        'icon' => 'fas fa-list-ul',
                        'current' => (strpos($current_url, 'grants') !== false || is_post_type_archive('grant') || is_singular('grant'))
                    ),
                    array(
                        'url' => home_url('/about/'), 
                        'title' => 'サイトについて', 
                        'icon' => 'fas fa-info-circle',
                        'current' => (strpos($current_url, '/about/') !== false)
                    ),
                );
                
                foreach ($menu_items as $item) {
                    $class = 'gi-nav-link';
                    if ($item['current']) {
                        $class .= ' current';
                    }
                    
                    echo '<a href="' . esc_url($item['url']) . '" class="' . $class . '">';
                    echo '<i class="' . esc_attr($item['icon']) . '"></i>';
                    echo '<span>' . esc_html($item['title']) . '</span>';
                    echo '</a>';
                }
                ?>
            </nav>
            
            <!-- Desktop Actions -->
            <div class="gi-actions">
                <!-- Search Toggle -->
                <button type="button" id="gi-search-toggle" class="gi-btn gi-btn-icon" title="詳細検索" aria-label="詳細検索を開く">
                    <i class="fas fa-search"></i>
                </button>
                
                <!-- Stats Display -->
                <div class="gi-stats">
                    <?php
                    $stats = gi_get_cached_stats();
                    if ($stats && !empty($stats['total_grants'])) {
                        echo '<div class="gi-stat-item">';
                        echo '<i class="fas fa-database"></i>';
                        echo '<span class="gi-stat-number">' . number_format($stats['total_grants']) . '</span>';
                        echo '<span>件の助成金</span>';
                        echo '</div>';
                        
                        if (!empty($stats['active_grants'])) {
                            echo '<div class="gi-stat-item">';
                            echo '<i class="fas fa-circle" style="color: #10b981;"></i>';
                            echo '<span class="gi-stat-number">' . number_format($stats['active_grants']) . '</span>';
                            echo '<span>募集中</span>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                
                <!-- CTA Button -->
                <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" class="gi-btn gi-btn-success">
                    <i class="fas fa-search"></i>
                    <span>助成金を探す</span>
                </a>
            </div>
            
            <!-- Mobile Menu Button -->
            <button type="button" id="gi-mobile-menu-btn" class="gi-mobile-btn" aria-label="メニューを開く">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Advanced Search Bar -->
    <div id="gi-search-bar" class="gi-search-bar">
        <div class="gi-container">
            <form id="gi-search-form" class="gi-search-form">
                <div class="gi-search-input-wrapper">
                    <input type="text" 
                           id="gi-search-input"
                           name="search" 
                           placeholder="助成金名、実施組織名、対象事業者などで検索..." 
                           class="gi-search-input"
                           autocomplete="off">
                    <i class="fas fa-search gi-search-icon"></i>
                </div>
                
                <div class="gi-search-filters">
                    <select name="category" class="gi-search-select" aria-label="カテゴリーを選択">
                        <option value="">すべてのカテゴリー</option>
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'grant_category',
                            'hide_empty' => true,
                            'orderby' => 'count',
                            'order' => 'DESC',
                            'number' => 30
                        ));
                        if ($categories && !is_wp_error($categories)) {
                            foreach ($categories as $category) {
                                echo '<option value="' . esc_attr($category->slug) . '">';
                                echo esc_html($category->name) . ' (' . $category->count . ')';
                                echo '</option>';
                            }
                        }
                        ?>
                    </select>
                    
                    <select name="prefecture" class="gi-search-select" aria-label="都道府県を選択">
                        <option value="">全国対象</option>
                        <?php
                        $prefectures = get_terms(array(
                            'taxonomy' => 'grant_prefecture',
                            'hide_empty' => true,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));
                        if ($prefectures && !is_wp_error($prefectures)) {
                            foreach ($prefectures as $prefecture) {
                                echo '<option value="' . esc_attr($prefecture->slug) . '">';
                                echo esc_html($prefecture->name) . ' (' . $prefecture->count . ')';
                                echo '</option>';
                            }
                        }
                        ?>
                    </select>
                    
                    <button type="submit" class="gi-search-submit">
                        <i class="fas fa-search"></i>
                        <span>検索実行</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div id="gi-mobile-overlay" class="gi-mobile-overlay">
    <div id="gi-mobile-menu" class="gi-mobile-menu">
        <!-- Mobile Header -->
        <div class="gi-mobile-header">
            <div class="gi-mobile-title">
                <i class="fas fa-bars"></i>
                メニュー
            </div>
            <button type="button" id="gi-mobile-close" class="gi-mobile-close" aria-label="メニューを閉じる">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Mobile Search -->
        <div class="gi-mobile-search">
            <div class="gi-search-input-wrapper">
                <input type="text" 
                       placeholder="助成金を検索..." 
                       class="gi-search-input"
                       id="gi-mobile-search-input">
                <i class="fas fa-search gi-search-icon"></i>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <nav class="gi-mobile-nav">
            <?php
            foreach ($menu_items as $item) {
                echo '<a href="' . esc_url($item['url']) . '" class="gi-mobile-nav-link">';
                echo '<i class="' . esc_attr($item['icon']) . '"></i>';
                echo '<span>' . esc_html($item['title']) . '</span>';
                if ($item['current']) {
                    echo '<i class="fas fa-circle" style="margin-left: auto; font-size: 0.5rem; color: #667eea;"></i>';
                }
                echo '</a>';
            }
            ?>
        </nav>
        
        <!-- Mobile Actions -->
        <div class="gi-mobile-actions">
            <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" class="gi-mobile-cta">
                <i class="fas fa-search"></i>
                <span>助成金を探す</span>
            </a>
            
            <?php if ($stats && !empty($stats['total_grants'])): ?>
            <div style="text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #6b7280;">
                <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                現在 <strong style="color: #667eea;"><?php echo number_format($stats['total_grants']); ?>件</strong> の助成金情報を掲載中
                <?php if (!empty($stats['active_grants'])): ?>
                （<strong style="color: #10b981;"><?php echo number_format($stats['active_grants']); ?>件</strong> 募集中）
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===============================================
    // CLEAN HEADER FUNCTIONALITY
    // ===============================================
    
    // Elements
    const header = document.getElementById('gi-site-header');
    const searchToggle = document.getElementById('gi-search-toggle');
    const searchBar = document.getElementById('gi-search-bar');
    const searchForm = document.getElementById('gi-search-form');
    const searchInput = document.getElementById('gi-search-input');
    const mobileSearchInput = document.getElementById('gi-mobile-search-input');
    const mobileMenuBtn = document.getElementById('gi-mobile-menu-btn');
    const mobileOverlay = document.getElementById('gi-mobile-overlay');
    const mobileMenu = document.getElementById('gi-mobile-menu');
    const mobileClose = document.getElementById('gi-mobile-close');
    
    // State
    let lastScrollTop = 0;
    let isSearchOpen = false;
    let isMobileMenuOpen = false;
    
    // ===============================================
    // SCROLL BEHAVIOR
    // ===============================================
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Hide/show header on scroll (only when scrolling down significantly)
        if (scrollTop > lastScrollTop && scrollTop > 150 && !isMobileMenuOpen && !isSearchOpen) {
            header.classList.add('hidden');
        } else {
            header.classList.remove('hidden');
        }
        
        // Add scrolled effect
        if (scrollTop > 20) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    }
    
    // Throttled scroll handler for better performance
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        scrollTimeout = setTimeout(handleScroll, 10);
    });
    
    // ===============================================
    // SEARCH FUNCTIONALITY
    // ===============================================
    function toggleSearch() {
        isSearchOpen = !isSearchOpen;
        
        if (isSearchOpen) {
            searchBar.classList.add('show');
            searchBar.classList.remove('gi-hidden');
            header.classList.remove('hidden'); // Always show header when search is open
            
            // Focus on search input after animation
            setTimeout(() => {
                searchInput?.focus();
            }, 150);
            
            // Update toggle button
            if (searchToggle) {
                searchToggle.innerHTML = '<i class="fas fa-times"></i>';
                searchToggle.title = '検索を閉じる';
            }
        } else {
            searchBar.classList.remove('show');
            setTimeout(() => {
                searchBar.classList.add('gi-hidden');
            }, 300);
            
            // Reset toggle button
            if (searchToggle) {
                searchToggle.innerHTML = '<i class="fas fa-search"></i>';
                searchToggle.title = '詳細検索';
            }
        }
    }
    
    searchToggle?.addEventListener('click', toggleSearch);
    
    // Search form submission with loading state
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = this.querySelector('.gi-search-submit');
            if (submitBtn) {
                submitBtn.classList.add('gi-loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>検索中...</span>';
            }
            
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                if (value.trim()) {
                    params.append(key, value);
                }
            }
            
            const archiveUrl = '<?php echo esc_url(get_post_type_archive_link("grant")); ?>';
            const searchUrl = archiveUrl + (params.toString() ? '?' + params.toString() : '');
            
            // Small delay to show loading state
            setTimeout(() => {
                window.location.href = searchUrl;
            }, 500);
        });
    }
    
    // Mobile search functionality
    if (mobileSearchInput) {
        mobileSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    const archiveUrl = '<?php echo esc_url(get_post_type_archive_link("grant")); ?>';
                    window.location.href = archiveUrl + '?search=' + encodeURIComponent(query);
                }
            }
        });
    }
    
    // ===============================================
    // MOBILE MENU
    // ===============================================
    function openMobileMenu() {
        isMobileMenuOpen = true;
        mobileOverlay?.classList.add('show');
        mobileMenu?.classList.add('show');
        header.classList.remove('hidden'); // Always show header when menu is open
        document.body.style.overflow = 'hidden';
        
        // Focus management
        setTimeout(() => {
            const firstFocusable = mobileMenu?.querySelector('input, a, button');
            firstFocusable?.focus();
        }, 400);
    }
    
    function closeMobileMenu() {
        isMobileMenuOpen = false;
        mobileOverlay?.classList.remove('show');
        mobileMenu?.classList.remove('show');
        document.body.style.overflow = '';
        
        // Return focus to menu button
        mobileMenuBtn?.focus();
    }
    
    mobileMenuBtn?.addEventListener('click', openMobileMenu);
    mobileClose?.addEventListener('click', closeMobileMenu);
    
    // Close on overlay click
    mobileOverlay?.addEventListener('click', function(e) {
        if (e.target === mobileOverlay) {
            closeMobileMenu();
        }
    });
    
    // ===============================================
    // KEYBOARD NAVIGATION
    // ===============================================
    document.addEventListener('keydown', function(e) {
        // Escape key handlers
        if (e.key === 'Escape') {
            if (isMobileMenuOpen) {
                closeMobileMenu();
            } else if (isSearchOpen) {
                toggleSearch();
            }
        }
        
        // Search shortcut (Ctrl/Cmd + K)
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (!isSearchOpen) {
                toggleSearch();
            }
        }
    });
    
    // Focus trap for mobile menu
    if (mobileMenu) {
        mobileMenu.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                const focusableElements = this.querySelectorAll('input, a, button');
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }
    
    // ===============================================
    // PERFORMANCE OPTIMIZATIONS
    // ===============================================
    
    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const animationObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('gi-animate-fade-up');
            }
        });
    }, observerOptions);
    
    // Observe animated elements
    document.querySelectorAll('.gi-nav-link, .gi-btn').forEach(el => {
        animationObserver.observe(el);
    });
    
    // ===============================================
    // ACCESSIBILITY ENHANCEMENTS
    // ===============================================
    
    // Announce search state changes to screen readers
    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'gi-hidden';
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }
    
    // Announce when search opens/closes
    const originalToggleSearch = toggleSearch;
    toggleSearch = function() {
        originalToggleSearch();
        announceToScreenReader(isSearchOpen ? '検索フォームが開きました' : '検索フォームが閉じました');
    };
    
    // ===============================================
    // INITIALIZATION
    // ===============================================
    
    // Set initial states
    searchBar?.classList.add('gi-hidden');
    
    // Add loaded class for any CSS transitions
    setTimeout(() => {
        document.body.classList.add('gi-loaded');
    }, 100);
    
    console.log('🚀 Grant Insight Clean Header initialized successfully!');
    
    // ===============================================
    // GLOBAL API
    // ===============================================
    
    // Expose useful functions globally
    window.GI_CleanHeader = {
        toggleSearch: toggleSearch,
        openMobileMenu: openMobileMenu,
        closeMobileMenu: closeMobileMenu,
        isSearchOpen: () => isSearchOpen,
        isMobileMenuOpen: () => isMobileMenuOpen
    };
});
</script>

<!-- Main Content Area -->
<main id="main-content" class="gi-main-content" style="margin-top: 4.5rem;">
<?php 
$hasAnnouncement = get_theme_mod('gi_show_announcement', true);
if ($hasAnnouncement): 
?>
<script>
// Adjust main content margin if announcement bar is present
document.getElementById('main-content').style.marginTop = '6.25rem';

// On larger screens
if (window.innerWidth >= 1024) {
    document.getElementById('main-content').style.marginTop = '7.25rem';
}
</script>
<?php endif; ?>