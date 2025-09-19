<?php
/**
 * Grant Insight Perfect - 5. Template Tags File (Enhanced Edition)
 *
 * 投稿カードのHTML生成など、主にテーマのテンプレートファイルから
 * 呼び出される表示関連の関数をここにまとめます。
 * 新しいカードデザインに対応した修正版です。
 *
 * @package Grant_Insight_Perfect
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 【修正】カード表示関数（完全版）- 新しいデザインを使用
 */
function gi_render_grant_card($post_id, $view = 'grid') {
    if (!$post_id || !get_post($post_id)) {
        return '';
    }

    // 新しいカードデザインを使用
    global $post;
    $original_post = $post;
    $post = get_post($post_id);
    setup_postdata($post);
    
    ob_start();
    include(get_template_directory() . '/grant-card-v4-enhanced.php');
    $output = ob_get_clean();
    
    $post = $original_post;
    if ($post) {
        setup_postdata($post);
    } else {
        wp_reset_postdata();
    }
    
    return $output;
}

/**
 * 【新機能】新しいカードデザインでのグリッド表示 ★修正版
 */
function gi_render_grant_card_grid_enhanced($grant) {
    ob_start();
    
    // データの準備（$grant配列から取得）
    $grant_id = $grant['id'];
    $grant_amount = $grant['amount_numeric'] ?? gi_safe_get_meta($grant_id, 'max_amount_numeric', 0);
    $deadline_timestamp = $grant['deadline_timestamp'] ?? gi_safe_get_meta($grant_id, 'deadline_date', '');
    $grant_rate = gi_safe_get_meta($grant_id, 'subsidy_rate', '2/3');
    $grant_target = gi_safe_get_meta($grant_id, 'grant_target', '中小企業');
    $grant_difficulty = gi_safe_get_meta($grant_id, 'grant_difficulty', 'normal');
    $grant_success_rate = gi_safe_get_meta($grant_id, 'grant_success_rate', 65);
    $is_featured = gi_safe_get_meta($grant_id, 'is_featured', false);
    $views_count = gi_safe_get_meta($grant_id, 'views_count', mt_rand(100, 500));
    
    $days_remaining = 0;
    if ($deadline_timestamp) {
        $days_remaining = ceil(((int)$deadline_timestamp - time()) / (60 * 60 * 24));
    }
    
    $difficulty_config = [
        'easy'   => ['label' => '易しい', 'color' => 'green', 'stars' => 1],
        'normal' => ['label' => '普通',   'color' => 'blue', 'stars' => 2],
        'hard'   => ['label' => '難しい', 'color' => 'orange', 'stars' => 3],
        'expert' => ['label' => '専門的', 'color' => 'red', 'stars' => 4]
    ];
    $difficulty_info = $difficulty_config[$grant_difficulty] ?? $difficulty_config['normal'];
    
    $user_favorites = function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : [];
    $is_favorite = in_array($grant_id, $user_favorites);
    ?>
    
    <article class="grant-card-enhanced group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transform transition-all duration-500 hover:-translate-y-1 overflow-hidden" data-grant-id="<?php echo esc_attr($grant_id); ?>">
        
        <?php if ($is_featured): ?>
        <div class="absolute top-0 right-0 z-10">
            <div class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold py-2 px-4 rounded-bl-2xl shadow-lg">
                <i class="fas fa-star mr-1"></i>注目
            </div>
        </div>
        <?php endif; ?>
        
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 opacity-10 group-hover:opacity-20 transition-opacity duration-500"></div>
            <div class="relative p-6 pb-4">
                <div class="flex flex-wrap gap-2 mb-3">
                    <?php if (!empty($grant['main_category'])): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                        <i class="fas fa-folder mr-1"></i><?php echo esc_html($grant['main_category']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($grant['prefecture'])): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        <i class="fas fa-map-marker-alt mr-1"></i><?php echo esc_html($grant['prefecture']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors duration-300">
                    <a href="<?php echo esc_url($grant['permalink']); ?>"><?php echo esc_html($grant['title']); ?></a>
                </h3>
                
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center">
                            <i class="fas fa-chart-line text-green-500 mr-1"></i>
                            採択率 <strong class="text-green-600 ml-1"><?php echo esc_html($grant_success_rate); ?>%</strong>
                        </span>
                        <span class="flex items-center">
                            <i class="fas fa-eye text-gray-400 mr-1"></i>
                            <?php echo number_format($views_count); ?>回閲覧
                        </span>
                    </div>
                </div>
            </div>
            
            <?php if ($deadline_timestamp && $days_remaining > 0): ?>
            <div class="px-6 pb-4">
                <?php 
                    $progress_percentage = max(0, min(100, (30 - $days_remaining) / 30 * 100));
                    $progress_color = $days_remaining <= 7 ? 'red' : ($days_remaining <= 14 ? 'yellow' : 'green');
                ?>
                <div class="flex justify-between items-center mb-1 text-xs">
                    <span class="text-gray-600">申請期限</span>
                    <span class="font-bold text-<?php echo esc_attr($progress_color); ?>-600">
                        残り<?php echo esc_html($days_remaining); ?>日
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-gradient-to-r from-<?php echo esc_attr($progress_color); ?>-400 to-<?php echo esc_attr($progress_color); ?>-600 h-2 rounded-full transition-all duration-500" 
                         style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="px-6 pb-4">
            <div class="mb-4 p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-200">
                <div class="text-sm text-gray-600 mb-1">最大支援額</div>
                <div class="flex items-baseline">
                    <span class="text-3xl font-bold text-emerald-600">
                        <?php echo ($grant_amount > 0) ? number_format($grant_amount / 10000) : ($grant['amount'] ?? '-'); ?>
                    </span>
                    <?php if ($grant_amount > 0 || (isset($grant['amount']) && $grant['amount'] !== '-')): ?>
                    <span class="text-lg text-emerald-600 ml-1">万円</span>
                    <?php endif; ?>
                    <?php if ($grant_rate): ?>
                    <span class="ml-3 text-sm text-gray-600">
                        (補助率: <strong><?php echo esc_html($grant_rate); ?></strong>)
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">申請難易度</span>
                    <div class="flex items-center gap-1">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                        <i class="fas fa-star text-<?php echo $i <= $difficulty_info['stars'] ? esc_attr($difficulty_info['color']) : 'gray'; ?>-400"></i>
                        <?php endfor; ?>
                        <span class="ml-2 text-sm font-medium text-<?php echo esc_attr($difficulty_info['color']); ?>-600">
                            <?php echo esc_html($difficulty_info['label']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <div class="text-xs text-gray-600 mb-1">対象事業者</div>
                <div class="text-sm font-medium text-gray-800"><?php echo esc_html($grant_target); ?></div>
            </div>
            
            <?php if (!empty($grant['excerpt'])): ?>
            <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                <?php echo esc_html($grant['excerpt']); ?>
            </p>
            <?php endif; ?>
        </div>
        
        <div class="px-6 pb-6">
            <div class="flex gap-3">
                <a href="<?php echo esc_url($grant['permalink']); ?>" 
                   class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 shadow-md hover:shadow-xl">
                    <span>詳細を見る</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                </a>
                
                <button type="button" 
                        class="favorite-btn p-3 bg-gray-100 hover:bg-red-100 rounded-lg transition-all duration-300 group/fav"
                        data-post-id="<?php echo esc_attr($grant_id); ?>">
                    <i class="fa-heart text-gray-600 group-hover/fav:text-red-500 transition-colors duration-300 <?php echo $is_favorite ? 'fas' : 'far'; ?>"></i>
                </button>
                
                <button type="button" 
                        class="share-btn p-3 bg-gray-100 hover:bg-blue-100 rounded-lg transition-all duration-300 group/share"
                        data-url="<?php echo esc_url($grant['permalink']); ?>"
                        data-title="<?php echo esc_attr($grant['title']); ?>">
                    <i class="fas fa-share-alt text-gray-600 group-hover/share:text-blue-500 transition-colors duration-300"></i>
                </button>
            </div>
        </div>
        
        <div class="absolute inset-0 bg-gradient-to-t from-blue-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
    </article>
    
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】新しいカードデザインでのリスト表示 ★修正版
 */
function gi_render_grant_card_list_enhanced($grant) {
    ob_start();
    
    // データの準備（$grant配列から取得）
    $grant_id = $grant['id'];
    $grant_amount = $grant['amount_numeric'] ?? gi_safe_get_meta($grant_id, 'max_amount_numeric', 0);
    $deadline_timestamp = $grant['deadline_timestamp'] ?? gi_safe_get_meta($grant_id, 'deadline_date', '');
    $grant_rate = gi_safe_get_meta($grant_id, 'subsidy_rate', '2/3');
    $grant_target = gi_safe_get_meta($grant_id, 'grant_target', '中小企業');
    $grant_difficulty = gi_safe_get_meta($grant_id, 'grant_difficulty', 'normal');
    $grant_success_rate = gi_safe_get_meta($grant_id, 'grant_success_rate', 65);
    $is_featured = gi_safe_get_meta($grant_id, 'is_featured', false);
    $views_count = gi_safe_get_meta($grant_id, 'views_count', mt_rand(100, 500));
    
    $days_remaining = 0;
    if ($deadline_timestamp) {
        $days_remaining = ceil(((int)$deadline_timestamp - time()) / (60 * 60 * 24));
    }
    
    $difficulty_config = [
        'easy'   => ['label' => '易しい', 'color' => 'green', 'stars' => 1],
        'normal' => ['label' => '普通',   'color' => 'blue', 'stars' => 2],
        'hard'   => ['label' => '難しい', 'color' => 'orange', 'stars' => 3],
        'expert' => ['label' => '専門的', 'color' => 'red', 'stars' => 4]
    ];
    $difficulty_info = $difficulty_config[$grant_difficulty] ?? $difficulty_config['normal'];
    
    $user_favorites = function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : [];
    $is_favorite = in_array($grant_id, $user_favorites);
    ?>
    
    <article class="grant-list-item-enhanced bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border border-gray-100" data-grant-id="<?php echo esc_attr($grant_id); ?>">
        <div class="flex flex-col lg:flex-row gap-6">
            <div class="lg:w-64 lg:shrink-0 relative">
                <?php if (!empty($grant['thumbnail'])): ?>
                    <img src="<?php echo esc_url($grant['thumbnail']); ?>" alt="<?php echo esc_attr($grant['title']); ?>" class="w-full h-40 lg:h-32 object-cover rounded-xl">
                <?php else: ?>
                    <div class="w-full h-40 lg:h-32 bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-coins text-3xl text-white"></i>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_featured): ?>
                <div class="absolute top-2 right-2">
                    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold py-1 px-2 rounded-lg shadow-lg">
                        <i class="fas fa-star mr-1"></i>注目
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex-1">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3 flex-wrap">
                            <?php echo gi_get_status_badge($grant['status'] ?? 'active'); ?>
                            
                            <?php if (!empty($grant['prefecture'])): ?>
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                    📍 <?php echo esc_html($grant['prefecture']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($grant['main_category'])): ?>
                                <span class="px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full">
                                    <?php echo esc_html($grant['main_category']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <button class="favorite-btn text-gray-400 hover:text-red-500 transition-colors <?php echo $is_favorite ? 'text-red-500' : ''; ?> focus:outline-none focus:ring-2 focus:ring-red-500 rounded p-1"
                                    data-post-id="<?php echo esc_attr($grant_id); ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 hover:text-emerald-600 transition-colors">
                            <a href="<?php echo esc_url($grant['permalink']); ?>"><?php echo esc_html($grant['title']); ?></a>
                        </h3>
                        
                        <?php if (!empty($grant['excerpt'])): ?>
                        <p class="text-gray-600 mb-4 line-clamp-2">
                            <?php echo esc_html($grant['excerpt']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
                            <div class="flex items-center gap-1">
                                <i class="fas fa-chart-line text-green-500"></i>
                                <span>採択率: <strong class="text-green-600"><?php echo esc_html($grant_success_rate); ?>%</strong></span>
                            </div>
                            
                            <div class="flex items-center gap-1">
                                <i class="fas fa-eye text-gray-400"></i>
                                <span><?php echo number_format($views_count); ?>回閲覧</span>
                            </div>
                            
                            <?php if (!empty($grant['organization'])): ?>
                            <div class="flex items-center gap-1">
                                <i class="fas fa-building"></i>
                                <span><?php echo esc_html($grant['organization']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($grant['deadline'])): ?>
                            <div class="flex items-center gap-1">
                                <i class="fas fa-calendar"></i>
                                <span>締切: <?php echo esc_html($grant['deadline']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-600">難易度:</span>
                                <div class="flex items-center gap-1">
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <i class="fas fa-star text-<?php echo $i <= $difficulty_info['stars'] ? esc_attr($difficulty_info['color']) : 'gray'; ?>-400 text-xs"></i>
                                    <?php endfor; ?>
                                    <span class="ml-1 text-sm font-medium text-<?php echo esc_attr($difficulty_info['color']); ?>-600">
                                        <?php echo esc_html($difficulty_info['label']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 p-2 bg-gray-50 rounded-lg">
                            <span class="text-xs text-gray-600">対象: </span>
                            <span class="text-sm font-medium text-gray-800"><?php echo esc_html($grant_target); ?></span>
                        </div>
                    </div>
                    
                    <div class="lg:w-56 lg:text-right">
                        <div class="mb-4 p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-200">
                            <div class="text-sm text-gray-600 mb-1">最大支援額</div>
                            <div class="flex lg:flex-col items-baseline lg:items-end">
                                <span class="text-2xl lg:text-3xl font-bold text-emerald-600">
                                    <?php echo ($grant_amount > 0) ? number_format($grant_amount / 10000) : ($grant['amount'] ?? '-'); ?>
                                </span>
                                <?php if ($grant_amount > 0 || (isset($grant['amount']) && $grant['amount'] !== '-')): ?>
                                <span class="text-lg text-emerald-600 ml-1">万円</span>
                                <?php endif; ?>
                                <?php if ($grant_rate): ?>
                                <div class="text-xs text-gray-600 mt-1">
                                    補助率: <strong><?php echo esc_html($grant_rate); ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($deadline_timestamp && $days_remaining > 0): ?>
                        <div class="mb-4">
                            <?php 
                                $progress_percentage = max(0, min(100, (30 - $days_remaining) / 30 * 100));
                                $progress_color = $days_remaining <= 7 ? 'red' : ($days_remaining <= 14 ? 'yellow' : 'green');
                            ?>
                            <div class="flex justify-between items-center mb-1 text-xs">
                                <span class="text-gray-600">申請期限</span>
                                <span class="font-bold text-<?php echo esc_attr($progress_color); ?>-600">
                                    残り<?php echo esc_html($days_remaining); ?>日
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-<?php echo esc_attr($progress_color); ?>-400 to-<?php echo esc_attr($progress_color); ?>-600 h-2 rounded-full transition-all duration-500" 
                                     style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex lg:flex-col gap-2">
                            <a href="<?php echo esc_url($grant['permalink']); ?>" 
                               class="flex-1 lg:flex-none bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-center py-2 px-4 rounded-lg font-medium transition-all duration-300 shadow-md hover:shadow-xl transform hover:scale-105">
                                詳細を見る
                            </a>
                            <button class="share-btn px-3 py-2 bg-gray-100 hover:bg-blue-100 text-gray-600 hover:text-blue-500 rounded-lg transition-all duration-300"
                                    data-url="<?php echo esc_url($grant['permalink']); ?>"
                                    data-title="<?php echo esc_attr($grant['title']); ?>"
                                    title="共有">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
    
    <?php
    return ob_get_clean();
}

/**
 * ステータスバッジ取得（新デザイン対応）
 */
function gi_get_status_badge($status) {
    $badges = array(
        'active' => '<span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-gradient-to-r from-green-400 to-green-600 text-white rounded-full shadow-md"><i class="fas fa-circle mr-1 animate-pulse"></i>募集中</span>',
        'upcoming' => '<span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-full shadow-md"><i class="fas fa-clock mr-1"></i>募集予定</span>',
        'closed' => '<span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-gradient-to-r from-red-400 to-red-600 text-white rounded-full shadow-md"><i class="fas fa-times-circle mr-1"></i>募集終了</span>'
    );
    return $badges[$status] ?? $badges['active'];
}

/**
 * 複数カード表示関数（新デザイン対応）
 */
function gi_render_multiple_grants($post_ids, $view = 'grid', $columns = 3) {
    if (empty($post_ids) || !is_array($post_ids)) {
        return '<div class="text-center py-12 text-gray-500">表示する助成金がありません。</div>';
    }

    $grid_classes = array(
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4'
    );

    ob_start();
    
    if ($view === 'grid') {
        $grid_class = $grid_classes[$columns] ?? $grid_classes[3];
        echo '<div class="grid ' . $grid_class . ' gap-8">';
        
        foreach ($post_ids as $post_id) {
            echo gi_render_grant_card($post_id, 'grid');
        }
        
        echo '</div>';
    } else {
        echo '<div class="space-y-6">';
        
        foreach ($post_ids as $post_id) {
            echo gi_render_grant_card($post_id, 'list');
        }
        
        echo '</div>';
    }
    
    return ob_get_clean();
}

/**
 * 【新機能】難易度スター表示関数
 */
function gi_render_difficulty_stars($difficulty) {
    $difficulty_config = array(
        'easy'   => array('label' => '易しい', 'color' => 'green', 'stars' => 1),
        'normal' => array('label' => '普通',   'color' => 'blue', 'stars' => 2),
        'hard'   => array('label' => '難しい', 'color' => 'orange', 'stars' => 3),
        'expert' => array('label' => '専門的', 'color' => 'red', 'stars' => 4)
    );
    
    $info = $difficulty_config[$difficulty] ?? $difficulty_config['normal'];
    
    ob_start();
    ?>
    <div class="flex items-center gap-1">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <i class="fas fa-star text-<?php echo $i <= $info['stars'] ? esc_attr($info['color']) : 'gray'; ?>-400"></i>
        <?php endfor; ?>
        <span class="ml-2 text-sm font-medium text-<?php echo esc_attr($info['color']); ?>-600">
            <?php echo esc_html($info['label']); ?>
        </span>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】採択率バッジ表示関数
 */
function gi_render_success_rate_badge($rate) {
    $rate = intval($rate);
    $color = $rate >= 70 ? 'green' : ($rate >= 50 ? 'yellow' : 'red');
    
    ob_start();
    ?>
    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-<?php echo esc_attr($color); ?>-100 text-<?php echo esc_attr($color); ?>-800">
        <i class="fas fa-chart-line mr-1"></i>採択率 <?php echo esc_html($rate); ?>%
    </span>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】プログレスバー表示関数
 */
function gi_render_deadline_progress($deadline_timestamp) {
    if (!$deadline_timestamp) {
        return '';
    }
    
    $days_remaining = ceil(((int)$deadline_timestamp - time()) / (60 * 60 * 24));
    
    if ($days_remaining <= 0) {
        return '<div class="text-xs text-red-600 font-bold">募集終了</div>';
    }
    
    $progress_percentage = max(0, min(100, (30 - $days_remaining) / 30 * 100));
    $progress_color = $days_remaining <= 7 ? 'red' : ($days_remaining <= 14 ? 'yellow' : 'green');
    
    ob_start();
    ?>
    <div class="w-full">
        <div class="flex justify-between items-center mb-1 text-xs">
            <span class="text-gray-600">申請期限</span>
            <span class="font-bold text-<?php echo esc_attr($progress_color); ?>-600">
                残り<?php echo esc_html($days_remaining); ?>日
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-gradient-to-r from-<?php echo esc_attr($progress_color); ?>-400 to-<?php echo esc_attr($progress_color); ?>-600 h-2 rounded-full transition-all duration-500" 
                 style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】カード用アニメーション CSS クラス生成
 */
function gi_get_card_animation_class($index = 0) {
    return 'animate-fade-in-up';
}

/**
 * 【新機能】カード用ホバーエフェクト CSS 生成
 */
function gi_generate_card_hover_styles() {
    ob_start();
    ?>
    <style>
    .grant-card-enhanced {
        animation: fadeInUp 0.6s ease-out;
        animation-fill-mode: both;
    }
    
    .grant-card-enhanced:nth-child(1) { animation-delay: 0.1s; }
    .grant-card-enhanced:nth-child(2) { animation-delay: 0.2s; }
    .grant-card-enhanced:nth-child(3) { animation-delay: 0.3s; }
    .grant-card-enhanced:nth-child(4) { animation-delay: 0.4s; }
    .grant-card-enhanced:nth-child(5) { animation-delay: 0.5s; }
    .grant-card-enhanced:nth-child(6) { animation-delay: 0.6s; }
    
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
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* ホバーエフェクトの強化 */
    .grant-card-enhanced:hover {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    .grant-list-item-enhanced:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    /* お気に入りボタンのアニメーション */
    .favorite-btn:active {
        transform: scale(0.95);
    }
    
    /* 共有ボタンのアニメーション */
    .share-btn:active {
        transform: scale(0.95);
    }
    
    /* プログレスバーのアニメーション */
    @keyframes progressAnimation {
        0% { width: 0%; }
        100% { width: var(--progress-width); }
    }
    
    /* レスポンシブ対応 */
    @media (max-width: 768px) {
        .grant-card-enhanced {
            margin-bottom: 1rem;
        }
        
        .grant-card-enhanced .text-3xl {
            font-size: 1.875rem;
        }
        
        .grant-card-enhanced .px-6 {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .grant-list-item-enhanced .lg\\:flex-row {
            flex-direction: column;
        }
        
        .grant-list-item-enhanced .lg\\:w-64 {
            width: 100%;
        }
        
        .grant-list-item-enhanced .lg\\:w-56 {
            width: 100%;
            text-align: left;
        }
    }
    
    /* グリッドレイアウトの調整 */
    .search-results-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
    
    @media (min-width: 640px) {
        .search-results-grid {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        }
    }
    
    @media (min-width: 1024px) {
        .search-results-grid {
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        }
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】レスポンシブ グリッドクラス取得
 */
function gi_get_responsive_grid_classes($columns = 3, $gap = 8) {
    $grid_classes = array(
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4',
        5 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
        6 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6'
    );
    
    $gap_classes = array(
        2 => 'gap-2',
        4 => 'gap-4', 
        6 => 'gap-6',
        8 => 'gap-8',
        10 => 'gap-10',
        12 => 'gap-12'
    );
    
    $grid_class = $grid_classes[$columns] ?? $grid_classes[3];
    $gap_class = $gap_classes[$gap] ?? $gap_classes[8];
    
    return "grid {$grid_class} {$gap_class}";
}

/**
 * 【新機能】カードメタ情報表示関数
 */
function gi_render_card_meta_info($grant_id) {
    $grant_rate = gi_safe_get_meta($grant_id, 'subsidy_rate', '');
    $grant_target = gi_safe_get_meta($grant_id, 'grant_target', '');
    $grant_difficulty = gi_safe_get_meta($grant_id, 'grant_difficulty', 'normal');
    $grant_success_rate = gi_safe_get_meta($grant_id, 'grant_success_rate', 0);
    $views_count = gi_safe_get_meta($grant_id, 'views_count', 0);
    
    ob_start();
    ?>
    <div class="card-meta-info text-sm text-gray-600 space-y-2">
        <?php if ($grant_rate): ?>
        <div class="flex items-center gap-2">
            <i class="fas fa-percentage text-blue-500"></i>
            <span>補助率: <strong><?php echo esc_html($grant_rate); ?></strong></span>
        </div>
        <?php endif; ?>
        
        <?php if ($grant_target): ?>
        <div class="flex items-center gap-2">
            <i class="fas fa-users text-green-500"></i>
            <span>対象: <?php echo esc_html($grant_target); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($grant_success_rate > 0): ?>
        <div class="flex items-center gap-2">
            <i class="fas fa-chart-line text-emerald-500"></i>
            <span>採択率: <strong class="text-emerald-600"><?php echo esc_html($grant_success_rate); ?>%</strong></span>
        </div>
        <?php endif; ?>
        
        <?php if ($views_count > 0): ?>
        <div class="flex items-center gap-2">
            <i class="fas fa-eye text-gray-400"></i>
            <span><?php echo number_format($views_count); ?>回閲覧</span>
        </div>
        <?php endif; ?>
        
        <div class="difficulty-display">
            <?php echo gi_render_difficulty_stars($grant_difficulty); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】カード統計情報表示関数
 */
function gi_render_card_statistics($data = array()) {
    $defaults = array(
        'total_grants' => 0,
        'active_grants' => 0,
        'average_amount' => 0,
        'success_rate' => 0
    );
    $data = array_merge($defaults, $data);
    
    ob_start();
    ?>
    <div class="card-statistics bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($data['total_grants']); ?></div>
                <div class="text-sm text-gray-600">総助成金数</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo number_format($data['active_grants']); ?></div>
                <div class="text-sm text-gray-600">募集中</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-emerald-600"><?php echo number_format($data['average_amount'] / 10000); ?>万</div>
                <div class="text-sm text-gray-600">平均支援額</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600"><?php echo number_format($data['success_rate']); ?>%</div>
                <div class="text-sm text-gray-600">平均採択率</div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】カード表示用のJavaScript関数群
 */
function gi_render_card_javascript() {
    ob_start();
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // お気に入りボタンのクリックイベント
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                
                // アニメーション効果
                this.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 200);
                
                // AJAX処理（テーマのメインJavaScriptが担当）
                if (typeof window.toggleFavorite === 'function') {
                    window.toggleFavorite(postId, this);
                }
            });
        });
        
        // 共有ボタンのクリックイベント
        const shareButtons = document.querySelectorAll('.share-btn');
        shareButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.dataset.url;
                const title = this.dataset.title;
                
                if (navigator.share) {
                    navigator.share({
                        title: title,
                        url: url
                    }).catch(err => console.log('Error sharing:', err));
                } else {
                    // フォールバック：URLをクリップボードにコピー
                    navigator.clipboard.writeText(url).then(() => {
                        // 簡単な通知表示
                        showNotification('URLをコピーしました', 'success');
                    }).catch(err => {
                        console.log('Copy failed:', err);
                        showNotification('コピーに失敗しました', 'error');
                    });
                }
            });
        });
        
        // カードのホバーエフェクト
        const cards = document.querySelectorAll('.grant-card-enhanced, .grant-list-item-enhanced');
        cards.forEach((card, index) => {
            // アニメーション遅延の設定
            card.style.animationDelay = `${index * 0.1}s`;
            
            // ホバー時のエフェクト
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
    
    // 通知表示関数
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg z-50 text-white font-medium transition-all duration-300 ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 
            'bg-blue-600'
        }`;
        
        document.body.appendChild(notification);
        
        // フェードイン
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // フェードアウト・削除
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Intersection Observer による遅延読み込みアニメーション
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const cardObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                cardObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // 新しく追加されたカードの監視
    function observeNewCards() {
        const newCards = document.querySelectorAll('.grant-card-enhanced:not(.observed), .grant-list-item-enhanced:not(.observed)');
        newCards.forEach(card => {
            card.classList.add('observed');
            cardObserver.observe(card);
        });
    }
    
    // 初期実行
    observeNewCards();
    
    // AJAX読み込み後の再実行用（グローバル関数として公開）
    window.initializeNewCards = observeNewCards;
    </script>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】カード表示の初期化関数（テーマから呼び出し用）
 */
function gi_initialize_card_system() {
    // CSS スタイルの出力
    echo gi_generate_card_hover_styles();
    
    // JavaScript の出力
    echo gi_render_card_javascript();
}

/**
 * 【新機能】フィルター結果表示用のラッパー関数
 */
function gi_render_filtered_grants($grants_data, $view = 'grid') {
    if (empty($grants_data)) {
        return gi_render_no_results_message();
    }
    
    ob_start();
    
    if ($view === 'grid') {
        echo '<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8" id="grants-grid">';
        foreach ($grants_data as $grant) {
            echo gi_render_grant_card_grid_enhanced($grant);
        }
        echo '</div>';
    } else {
        echo '<div class="space-y-6" id="grants-list">';
        foreach ($grants_data as $grant) {
            echo gi_render_grant_card_list_enhanced($grant);
        }
        echo '</div>';
    }
    
    return ob_get_clean();
}

/**
 * 【新機能】結果なしメッセージの表示
 */
function gi_render_no_results_message($message = '') {
    $default_message = '該当する助成金が見つかりませんでした。検索条件を変更して再度お試しください。';
    $message = $message ?: $default_message;
    
    ob_start();
    ?>
    <div class="text-center py-20">
        <div class="w-32 h-32 bg-gradient-to-r from-gray-400 via-gray-500 to-gray-600 rounded-full flex items-center justify-center mx-auto mb-8">
            <i class="fas fa-search text-white text-4xl"></i>
        </div>
        <h3 class="text-3xl font-bold text-gray-900 mb-6">結果が見つかりませんでした</h3>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed mb-8">
            <?php echo esc_html($message); ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button onclick="resetFilters()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                フィルターをリセット
            </button>
            <a href="<?php echo esc_url(home_url('/grants')); ?>" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                全ての助成金を見る
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】ローディング状態の表示
 */
function gi_render_loading_state() {
    ob_start();
    ?>
    <div class="text-center py-20" id="loading-state">
        <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
        <p class="text-gray-600 text-lg">助成金情報を読み込み中...</p>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 【新機能】カードシステムのフッター出力（テーマのfooter.phpから呼び出し）
 */
function gi_card_system_footer() {
    if (is_page_template('archive-grant.php') || is_post_type_archive('grant') || is_page('grants')) {
        gi_initialize_card_system();
    }
}
add_action('wp_footer', 'gi_card_system_footer');