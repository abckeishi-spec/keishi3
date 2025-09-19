<?php
/**
 * Grant Insight Perfect - Ultra Simple Footer Template
 * 超シンプル版 - functions.phpと完全連携
 * 
 * @package Grant_Insight_Perfect
 * @version 8.0.0-ultra-simple
 */

// 既存ヘルパー関数との完全連携
if (!function_exists('gi_get_sns_urls')) {
    function gi_get_sns_urls() {
        return [
            'twitter' => gi_get_theme_option('sns_twitter_url', ''),
            'facebook' => gi_get_theme_option('sns_facebook_url', ''),
            'linkedin' => gi_get_theme_option('sns_linkedin_url', ''),
            'instagram' => gi_get_theme_option('sns_instagram_url', ''),
            'youtube' => gi_get_theme_option('sns_youtube_url', '')
        ];
    }
}
?>

    </main>

    <!-- Tailwind CSS + Font Awesome + Google Fonts -->
    <?php if (!wp_script_is('tailwind-cdn', 'enqueued')): ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                        'space': ['Space Grotesk', 'sans-serif'],
                        'noto': ['Noto Sans JP', 'sans-serif']
                    },
                    boxShadow: {
                        'elegant': '0 20px 40px -12px rgba(0, 0, 0, 0.15)',
                        'elegant-dark': '0 25px 50px -12px rgba(0, 0, 0, 0.3)'
                    },
                    borderRadius: {
                        '4xl': '2rem',
                        '5xl': '2.5rem'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&family=Noto+Sans+JP:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <?php endif; ?>

    <!-- 超シンプル・エレガントフッター -->
    <footer class="site-footer relative overflow-hidden bg-gradient-to-br from-gray-50 via-slate-50 to-blue-50/20 transition-all duration-700 font-inter">
        
        <!-- 控えめな背景装飾 -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-40">
            <!-- グリッド -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,theme(colors.gray.200)_1px,transparent_1px),linear-gradient(to_bottom,theme(colors.gray.200)_1px,transparent_1px)] bg-[size:4rem_4rem] opacity-30"></div>
            
            <!-- 控えめなグラデーション -->
            <div class="absolute -top-20 -right-20 w-48 h-48 bg-gradient-to-br from-blue-400/10 to-purple-400/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 -left-16 w-40 h-40 bg-gradient-to-br from-emerald-400/10 to-teal-400/10 rounded-full blur-2xl"></div>
        </div>

        <div class="relative z-10 py-16 lg:py-20">
            <div class="container mx-auto px-6 lg:px-8">
                
                <!-- メインブランドセクション -->
                <div class="text-center mb-16">
                    <div class="inline-flex items-center space-x-6 mb-8 group">
                        <div class="relative">
                            <img src="<?php echo esc_url(gi_get_media_url('名称未設定のデザイン.png', false)) ?: gi_get_asset_url('assets/images/logo.png'); ?>" 
                                 alt="<?php bloginfo('name'); ?>" 
                                 class="h-16 w-auto drop-shadow-lg group-hover:drop-shadow-xl transition-all duration-300 group-hover:scale-105">
                        </div>
                        
                        <div class="text-left">
                            <h2 class="text-3xl lg:text-4xl font-black text-gray-800 leading-tight font-space">
                                <?php 
                                $site_name = get_bloginfo('name');
                                $name_parts = explode('・', $site_name);
                                if (count($name_parts) > 1) {
                                    echo esc_html($name_parts[0]) . '・';
                                    echo '<span class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent">' . esc_html($name_parts[1]) . '</span>';
                                } else {
                                    echo '<span class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent">' . esc_html($site_name) . '</span>';
                                }
                                ?>
                            </h2>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-sm text-gray-600">Powered by AI Technology</span>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed font-light">
                        最先端AIテクノロジーで、あなたに最適な助成金・補助金を発見。<br class="hidden md:block">
                        <span class="font-semibold">ビジネス成長を加速</span>させましょう。
                    </p>
                </div>

                <!-- シンプルナビゲーション（2列レイアウト） -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
                    
                    <!-- 補助金検索カード -->
                    <div class="bg-white/60 backdrop-blur-xl rounded-4xl p-8 shadow-elegant hover:shadow-elegant-dark transition-all duration-500 border border-white/70 hover:border-blue-200/50 group">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-search text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2 font-space">補助金を探す</h3>
                            <p class="text-gray-600 text-sm">最適な補助金を瞬時に発見</p>
                        </div>
                        
                        <div class="space-y-2">
                            <a href="<?php echo esc_url(home_url('/grants/')); ?>" 
                               class="flex items-center justify-between p-3 bg-gray-50/80 rounded-2xl hover:bg-blue-50/80 transition-all duration-300 group/item border border-gray-200/30 hover:border-blue-300/50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-list text-blue-600 text-xs"></i>
                                    </div>
                                    <span class="font-medium text-gray-700 text-sm">助成金一覧</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 group-hover/item:text-blue-500 group-hover/item:translate-x-1 transition-all duration-200 text-xs"></i>
                            </a>
                            
                            <?php
                            // 主要カテゴリーのみ表示
                            $main_categories = [
                                ['slug' => 'it', 'name' => 'IT・デジタル化', 'icon' => 'fas fa-laptop-code', 'color' => 'indigo'],
                                ['slug' => 'manufacturing', 'name' => 'ものづくり', 'icon' => 'fas fa-industry', 'color' => 'purple'],
                                ['slug' => 'startup', 'name' => '創業・起業', 'icon' => 'fas fa-rocket', 'color' => 'emerald'],
                                ['slug' => 'employment', 'name' => '雇用促進', 'icon' => 'fas fa-users', 'color' => 'yellow']
                            ];
                            
                            foreach ($main_categories as $category):
                            ?>
                            <a href="<?php echo esc_url(home_url('/grants/?category=' . $category['slug'])); ?>" 
                               class="flex items-center justify-between p-3 bg-gray-50/80 rounded-2xl hover:bg-<?php echo $category['color']; ?>-50/80 transition-all duration-300 group/item border border-gray-200/30 hover:border-<?php echo $category['color']; ?>-300/50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-6 h-6 bg-<?php echo $category['color']; ?>-100 rounded-lg flex items-center justify-center">
                                        <i class="<?php echo $category['icon']; ?> text-<?php echo $category['color']; ?>-600 text-xs"></i>
                                    </div>
                                    <span class="font-medium text-gray-700 text-sm"><?php echo esc_html($category['name']); ?></span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 group-hover/item:text-<?php echo $category['color']; ?>-500 group-hover/item:translate-x-1 transition-all duration-200 text-xs"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- サポート・情報カード -->
                    <div class="bg-white/60 backdrop-blur-xl rounded-4xl p-8 shadow-elegant hover:shadow-elegant-dark transition-all duration-500 border border-white/70 hover:border-purple-200/50 group">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-info-circle text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2 font-space">サポート・情報</h3>
                            <p class="text-gray-600 text-sm">お困りの際はこちらから</p>
                        </div>
                        
                        <div class="space-y-2">
                            <?php
                            // 重要なサポートリンクのみ
                            $support_links = [
                                ['url' => '/about/', 'name' => 'サービスについて', 'icon' => 'fas fa-info-circle', 'color' => 'blue'],
                                ['url' => '/contact/', 'name' => 'お問い合わせ', 'icon' => 'fas fa-envelope', 'color' => 'emerald'],
                                ['url' => '/faq/', 'name' => 'よくある質問', 'icon' => 'fas fa-question-circle', 'color' => 'purple'],
                                ['url' => '/privacy/', 'name' => 'プライバシーポリシー', 'icon' => 'fas fa-shield-alt', 'color' => 'indigo'],
                                ['url' => '/terms/', 'name' => '利用規約', 'icon' => 'fas fa-file-contract', 'color' => 'gray']
                            ];
                            
                            foreach ($support_links as $link):
                            ?>
                            <a href="<?php echo esc_url(home_url($link['url'])); ?>" 
                               class="flex items-center justify-between p-3 bg-gray-50/80 rounded-2xl hover:bg-<?php echo $link['color']; ?>-50/80 transition-all duration-300 group/item border border-gray-200/30 hover:border-<?php echo $link['color']; ?>-300/50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-6 h-6 bg-<?php echo $link['color']; ?>-100 rounded-lg flex items-center justify-center">
                                        <i class="<?php echo $link['icon']; ?> text-<?php echo $link['color']; ?>-600 text-xs"></i>
                                    </div>
                                    <span class="font-medium text-gray-700 text-sm"><?php echo esc_html($link['name']); ?></span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 group-hover/item:text-<?php echo $link['color']; ?>-500 group-hover/item:translate-x-1 transition-all duration-200 text-xs"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- モバイル用シンプルメニュー -->
                <div class="lg:hidden mb-12">
                    <button id="gi-mobile-footer-toggle" class="w-full bg-white/70 backdrop-blur-xl rounded-4xl p-5 shadow-elegant border border-gray-200/50 flex items-center justify-between text-gray-800 hover:bg-white/80 transition-all duration-300 group">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center">
                                <i class="fas fa-bars text-white"></i>
                            </div>
                            <div class="text-left">
                                <h3 class="font-bold">メニュー</h3>
                                <p class="text-xs text-gray-600">サービス一覧</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300" id="gi-mobile-toggle-icon"></i>
                    </button>
                </div>

                <!-- モバイル用コンテンツ -->
                <div id="gi-mobile-footer-content" class="lg:hidden space-y-4 hidden overflow-hidden mb-12" style="max-height: 0; transition: max-height 0.3s ease-out;">
                    
                    <!-- 補助金を探す（モバイル） -->
                    <div class="bg-white/70 backdrop-blur-xl rounded-3xl p-5 shadow-elegant border border-gray-200/50">
                        <h3 class="font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-search mr-2 text-blue-600"></i>補助金を探す
                        </h3>
                        <div class="space-y-2">
                            <a href="<?php echo esc_url(home_url('/grants/')); ?>" class="flex items-center p-3 bg-gray-50/80 rounded-xl hover:bg-blue-50 transition-colors">
                                <i class="fas fa-list mr-3 text-blue-600 text-sm"></i>
                                <span class="font-medium text-gray-700 text-sm">助成金一覧</span>
                            </a>
                            <?php foreach ($main_categories as $category): ?>
                            <a href="<?php echo esc_url(home_url('/grants/?category=' . $category['slug'])); ?>" class="flex items-center p-3 bg-gray-50/80 rounded-xl hover:bg-<?php echo $category['color']; ?>-50 transition-colors">
                                <i class="<?php echo $category['icon']; ?> mr-3 text-<?php echo $category['color']; ?>-600 text-sm"></i>
                                <span class="font-medium text-gray-700 text-sm"><?php echo esc_html($category['name']); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- サポート（モバイル） -->
                    <div class="bg-white/70 backdrop-blur-xl rounded-3xl p-5 shadow-elegant border border-gray-200/50">
                        <h3 class="font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-info-circle mr-2 text-purple-600"></i>サポート・情報
                        </h3>
                        <div class="space-y-2">
                            <?php foreach ($support_links as $link): ?>
                            <a href="<?php echo esc_url(home_url($link['url'])); ?>" class="flex items-center p-3 bg-gray-50/80 rounded-xl hover:bg-<?php echo $link['color']; ?>-50 transition-colors">
                                <i class="<?php echo $link['icon']; ?> mr-3 text-<?php echo $link['color']; ?>-600 text-sm"></i>
                                <span class="font-medium text-gray-700 text-sm"><?php echo esc_html($link['name']); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- フッター下部セクション -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    
                    <!-- SNS & 特徴 -->
                    <div class="text-center lg:text-left">
                        <h4 class="text-xl font-bold text-gray-800 mb-6">フォローして最新情報をチェック</h4>
                        
                        <div class="flex justify-center lg:justify-start space-x-3 mb-6">
                            <?php
                            $sns_urls = gi_get_sns_urls();
                            $sns_data = [
                                'twitter' => ['icon' => 'fab fa-twitter', 'color' => 'from-blue-400 to-blue-600'],
                                'facebook' => ['icon' => 'fab fa-facebook-f', 'color' => 'from-blue-500 to-blue-700'], 
                                'linkedin' => ['icon' => 'fab fa-linkedin-in', 'color' => 'from-blue-600 to-blue-800'],
                                'instagram' => ['icon' => 'fab fa-instagram', 'color' => 'from-pink-400 to-purple-600'],
                                'youtube' => ['icon' => 'fab fa-youtube', 'color' => 'from-red-500 to-red-700']
                            ];

                            foreach ($sns_urls as $platform => $url): 
                                if (!empty($url)):
                            ?>
                            <a href="<?php echo esc_url($url); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               class="w-10 h-10 bg-gradient-to-br <?php echo $sns_data[$platform]['color']; ?> rounded-2xl flex items-center justify-center text-white shadow-elegant hover:shadow-elegant-dark transition-all duration-300 transform hover:-translate-y-1 hover:scale-110 group">
                                <i class="<?php echo $sns_data[$platform]['icon']; ?> text-sm"></i>
                            </a>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>

                        <!-- 特徴バッジ -->
                        <div class="flex flex-wrap justify-center lg:justify-start gap-3">
                            <span class="bg-emerald-100/80 text-emerald-700 px-3 py-2 rounded-2xl text-xs font-semibold border border-emerald-200 hover:scale-105 transition-transform duration-300 cursor-default">
                                <i class="fas fa-check-circle mr-1"></i>無料診断
                            </span>
                            <span class="bg-blue-100/80 text-blue-700 px-3 py-2 rounded-2xl text-xs font-semibold border border-blue-200 hover:scale-105 transition-transform duration-300 cursor-default">
                                <i class="fas fa-robot mr-1"></i>AI支援
                            </span>
                            <span class="bg-purple-100/80 text-purple-700 px-3 py-2 rounded-2xl text-xs font-semibold border border-purple-200 hover:scale-105 transition-transform duration-300 cursor-default">
                                <i class="fas fa-shield-alt mr-1"></i>安心・安全
                            </span>
                        </div>
                    </div>

                    <!-- コピーライト・信頼性 -->
                    <div class="text-center lg:text-right">
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="flex flex-col items-center text-emerald-600 group hover:scale-105 transition-transform duration-300">
                                <div class="w-10 h-10 bg-emerald-100 rounded-2xl flex items-center justify-center mb-2 group-hover:bg-emerald-200 transition-colors">
                                    <i class="fas fa-shield-alt text-emerald-600 text-sm"></i>
                                </div>
                                <span class="font-medium text-xs">SSL暗号化</span>
                            </div>
                            <div class="flex flex-col items-center text-blue-600 group hover:scale-105 transition-transform duration-300">
                                <div class="w-10 h-10 bg-blue-100 rounded-2xl flex items-center justify-center mb-2 group-hover:bg-blue-200 transition-colors">
                                    <i class="fas fa-lock text-blue-600 text-sm"></i>
                                </div>
                                <span class="font-medium text-xs">情報保護</span>
                            </div>
                            <div class="flex flex-col items-center text-purple-600 group hover:scale-105 transition-transform duration-300">
                                <div class="w-10 h-10 bg-purple-100 rounded-2xl flex items-center justify-center mb-2 group-hover:bg-purple-200 transition-colors">
                                    <i class="fas fa-award text-purple-600 text-sm"></i>
                                </div>
                                <span class="font-medium text-xs">専門家監修</span>
                            </div>
                            <div class="flex flex-col items-center text-yellow-600 group hover:scale-105 transition-transform duration-300">
                                <div class="w-10 h-10 bg-yellow-100 rounded-2xl flex items-center justify-center mb-2 group-hover:bg-yellow-200 transition-colors">
                                    <i class="fas fa-robot text-yellow-600 text-sm"></i>
                                </div>
                                <span class="font-medium text-xs">AI技術</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200/50 pt-4">
                            <p class="text-gray-600 mb-1 font-medium">
                                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.
                            </p>
                            <p class="text-gray-500 text-xs">
                                Powered by Next-Generation AI Technology
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- トップに戻るボタン -->
    <div id="gi-back-to-top" class="fixed bottom-6 right-6 z-50 opacity-0 pointer-events-none transition-all duration-300">
        <button class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-3xl shadow-elegant hover:shadow-elegant-dark transition-all duration-300 transform hover:-translate-y-2 hover:scale-110 group" onclick="giScrollToTop()">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // モバイルメニュー制御
        const mobileToggle = document.getElementById('gi-mobile-footer-toggle');
        const mobileContent = document.getElementById('gi-mobile-footer-content');
        const mobileIcon = document.getElementById('gi-mobile-toggle-icon');
        let isOpen = false;

        if (mobileToggle && mobileContent) {
            mobileToggle.addEventListener('click', function() {
                isOpen = !isOpen;
                
                if (isOpen) {
                    mobileContent.classList.remove('hidden');
                    mobileContent.style.maxHeight = mobileContent.scrollHeight + 'px';
                    mobileIcon.style.transform = 'rotate(180deg)';
                } else {
                    mobileContent.style.maxHeight = '0px';
                    mobileIcon.style.transform = 'rotate(0deg)';
                    setTimeout(() => {
                        mobileContent.classList.add('hidden');
                    }, 300);
                }
            });
        }

        // トップに戻るボタン制御
        let ticking = false;
        
        function updateBackToTop() {
            const backToTopButton = document.getElementById('gi-back-to-top');
            if (!backToTopButton) return;
            
            const scrolled = window.pageYOffset;
            
            if (scrolled > 300) {
                backToTopButton.classList.remove('opacity-0', 'pointer-events-none');
                backToTopButton.classList.add('opacity-100', 'pointer-events-auto');
            } else {
                backToTopButton.classList.add('opacity-0', 'pointer-events-none');
                backToTopButton.classList.remove('opacity-100', 'pointer-events-auto');
            }
            
            ticking = false;
        }

        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(updateBackToTop);
                ticking = true;
            }
        });

        // レスポンシブ対応
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024 && mobileContent && !mobileContent.classList.contains('hidden')) {
                mobileContent.classList.add('hidden');
                mobileContent.style.maxHeight = '0px';
                if (mobileIcon) {
                    mobileIcon.style.transform = 'rotate(0deg)';
                }
                isOpen = false;
            }
        });
    });

    // スムーズスクロール
    function giScrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // グローバル関数として公開
    window.giScrollToTop = giScrollToTop;
    </script>

    <?php wp_footer(); ?>

</body>
</html>