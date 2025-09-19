<?php
/**
 * Grant Card Renderer Class
 * 全てのカード表示ロジックを一元管理
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GrantCardRendererクラス
 * シングルトンパターンでカード表示を統一管理
 */
class GrantCardRenderer {
    
    /**
     * シングルトンインスタンス
     */
    private static $instance = null;
    
    /**
     * ユーザーのお気に入りキャッシュ
     */
    private $user_favorites_cache = null;
    
    /**
     * シングルトンパターンのインスタンス取得
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * プライベートコンストラクタ
     */
    private function __construct() {
        // 必要な依存関数の読み込み
        $this->load_dependencies();
    }
    
    /**
     * 依存ファイルの読み込み
     */
    private function load_dependencies() {
        $template_file = get_template_directory() . '/template-parts/grant-card-unified.php';
        if (file_exists($template_file)) {
            require_once $template_file;
        } else {
            // デバッグログに記録
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GrantCardRenderer: grant-card-unified.php not found at ' . $template_file);
            }
        }
    }
    
    /**
     * メインレンダリング関数
     * 
     * @param int $post_id 投稿ID
     * @param string $view 表示形式 ('grid' または 'list')
     * @param array $options 追加オプション
     * @return string HTML文字列
     */
    public function render($post_id, $view = 'grid', $options = []) {
        // 基本的なバリデーション
        if (!$post_id || get_post_type($post_id) !== 'grant') {
            return '';
        }
        
        // ユーザーのお気に入り情報を取得
        $user_favorites = $this->get_user_favorites();
        
        // 統一テンプレート関数を使用
        if (function_exists('render_grant_card_unified')) {
            return render_grant_card_unified($post_id, $view, $user_favorites);
        }
        
        // フォールバック：既存の関数を使用
        return $this->render_fallback($post_id, $view, $user_favorites);
    }
    
    /**
     * 複数のカードをバッチレンダリング
     * 
     * @param array $post_ids 投稿IDの配列
     * @param string $view 表示形式
     * @param array $options オプション
     * @return array レンダリング結果の配列
     */
    public function render_batch($post_ids, $view = 'grid', $options = []) {
        $results = [];
        $user_favorites = $this->get_user_favorites();
        
        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) === 'grant') {
                $html = $this->render($post_id, $view, $options);
                if (!empty($html)) {
                    $results[] = [
                        'id' => $post_id,
                        'html' => $html
                    ];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * ユーザーのお気に入り情報を取得
     * 
     * @return array お気に入りの投稿ID配列
     */
    private function get_user_favorites() {
        if ($this->user_favorites_cache === null) {
            if (function_exists('gi_get_user_favorites')) {
                $this->user_favorites_cache = gi_get_user_favorites();
            } else {
                $this->user_favorites_cache = [];
            }
        }
        
        return $this->user_favorites_cache;
    }
    
    /**
     * お気に入りキャッシュをクリア
     */
    public function clear_favorites_cache() {
        $this->user_favorites_cache = null;
    }
    
    /**
     * フォールバック表示（既存の関数を使用）
     * 
     * @param int $post_id 投稿ID
     * @param string $view 表示形式
     * @param array $user_favorites お気に入り配列
     * @return string HTML文字列
     */
    private function render_fallback($post_id, $view, $user_favorites = []) {
        // データ準備（既存のロジックを流用）
        $grant_data = [
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'permalink' => get_permalink($post_id),
            'excerpt' => get_the_excerpt($post_id),
            'organization' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'organization', '') : get_post_meta($post_id, 'organization', true),
            'amount' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'max_amount', '') : get_post_meta($post_id, 'max_amount', true),
            'amount_numeric' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'max_amount_numeric', 0) : intval(get_post_meta($post_id, 'max_amount_numeric', true)),
            'deadline' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'deadline', '') : get_post_meta($post_id, 'deadline', true),
            'status' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'application_status', 'active') : get_post_meta($post_id, 'application_status', true),
            'prefecture' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'prefecture', '') : get_post_meta($post_id, 'prefecture', true),
            'main_category' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'main_category', '') : get_post_meta($post_id, 'main_category', true),
            'success_rate' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'grant_success_rate', 0) : intval(get_post_meta($post_id, 'grant_success_rate', true)),
            'difficulty' => function_exists('gi_safe_get_meta') ? gi_safe_get_meta($post_id, 'grant_difficulty', '') : get_post_meta($post_id, 'grant_difficulty', true),
        ];
        
        // 既存の関数が利用できる場合はそれを使用
        if ($view === 'grid' && function_exists('gi_render_modern_grant_card')) {
            return gi_render_modern_grant_card($grant_data);
        } elseif ($view === 'list' && function_exists('gi_render_modern_grant_list_card')) {
            return gi_render_modern_grant_list_card($grant_data);
        }
        
        // 最終フォールバック：シンプルなHTML
        return $this->render_simple_card($grant_data, $view);
    }
    
    /**
     * シンプルなカード表示（最終フォールバック）
     * 
     * @param array $grant_data カードデータ
     * @param string $view 表示形式
     * @return string HTML文字列
     */
    private function render_simple_card($grant_data, $view) {
        $title = esc_html($grant_data['title']);
        $permalink = esc_url($grant_data['permalink']);
        $amount = esc_html($grant_data['amount']);
        $organization = esc_html($grant_data['organization']);
        
        if ($view === 'grid') {
            return "
            <div class='grant-card-simple' style='border:1px solid #000;padding:16px;background:#fff;'>
                <h3 style='font-size:16px;margin:0 0 8px 0;'>{$title}</h3>
                <p style='font-size:14px;color:#666;margin:0 0 8px 0;'>{$organization}</p>
                <p style='font-size:18px;font-weight:bold;margin:0 0 12px 0;'>{$amount}</p>
                <a href='{$permalink}' style='display:block;background:#000;color:#fff;text-align:center;padding:8px;text-decoration:none;'>詳細を見る</a>
            </div>
            ";
        } else {
            return "
            <div class='grant-list-card-simple' style='border:1px solid #000;padding:16px;background:#fff;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;'>
                <div>
                    <h3 style='font-size:16px;margin:0 0 4px 0;'>{$title}</h3>
                    <p style='font-size:12px;color:#666;margin:0;'>{$organization}</p>
                </div>
                <div style='text-align:right;'>
                    <p style='font-size:16px;font-weight:bold;margin:0 0 8px 0;'>{$amount}</p>
                    <a href='{$permalink}' style='background:#000;color:#fff;padding:6px 12px;text-decoration:none;font-size:12px;'>詳細</a>
                </div>
            </div>
            ";
        }
    }
    
    /**
     * カードのスタイルCSS出力
     * 
     * @return string CSS文字列
     */
    public function get_card_styles() {
        return "
        <style>
        /* Grant Card Unified Styles */
        .grant-card-unified:hover {
            border-width: 2px !important;
            transform: translateY(-2px);
        }
        
        .grant-card-unified .favorite-btn:hover {
            transform: scale(1.1);
        }
        
        .grant-card-unified a:hover {
            background: #ffffff !important;
            color: #000000 !important;
        }
        
        .grant-list-card-unified:hover {
            border-width: 2px !important;
            transform: translateY(-1px);
        }
        
        /* レスポンシブ対応 */
        @media (max-width: 767px) {
            .grant-card-unified {
                width: 100% !important;
                max-width: 320px;
                margin: 0 auto;
            }
            
            .grant-list-card-unified {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
        
        @font-face {
            font-family: 'Montserrat';
            font-weight: 400 700;
            font-display: swap;
            src: url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap');
        </style>
        ";
    }
    
    /**
     * JavaScriptイベントハンドラー出力
     * 
     * @return string JavaScript文字列
     */
    public function get_card_scripts() {
        return "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // カードクリックイベント（統一）
            document.addEventListener('click', function(e) {
                const card = e.target.closest('.grant-card-unified, .grant-list-card-unified');
                if (card && !e.target.closest('.favorite-btn, a')) {
                    const link = card.querySelector('a[href]');
                    if (link) {
                        window.location.href = link.href;
                    }
                }
            });
            
            // お気に入りボタンイベント（統一）
            document.addEventListener('click', function(e) {
                if (e.target.closest('.favorite-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const btn = e.target.closest('.favorite-btn');
                    const postId = btn.dataset.postId;
                    
                    if (typeof gi_toggle_favorite === 'function') {
                        gi_toggle_favorite(postId, btn);
                    }
                }
            });
            
            // ホバーエフェクト強化
            document.addEventListener('mouseenter', function(e) {
                const card = e.target.closest('.grant-card-unified, .grant-list-card-unified');
                if (card) {
                    card.style.transform = card.classList.contains('grant-card-unified') ? 'translateY(-2px)' : 'translateY(-1px)';
                }
            }, true);
            
            document.addEventListener('mouseleave', function(e) {
                const card = e.target.closest('.grant-card-unified, .grant-list-card-unified');
                if (card) {
                    card.style.transform = 'translateY(0)';
                }
            }, true);
        });
        </script>
        ";
    }
}