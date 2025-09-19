<?php
/**
 * Grant AI Assistant - AI Engine
 * AI処理ロジックと助成金データベース検索エンジン
 * 
 * @package Grant_AI_Assistant
 * @version 1.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Grant AI エンジンクラス
 * OpenAI APIとの連携、助成金データベース検索を処理
 */
class Grant_AI_Engine {
    
    /**
     * AJAX チャットメッセージハンドラー
     */
    public static function handle_chat_message() {
        // nonce検証
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gaa_chat_nonce')) {
            wp_send_json_error(__('セキュリティチェックに失敗しました', GAA_TEXT_DOMAIN));
            return;
        }
        
        $user_message = sanitize_text_field($_POST['message'] ?? '');
        $conversation_history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : array();
        
        if (empty($user_message)) {
            wp_send_json_error(__('メッセージが空です', GAA_TEXT_DOMAIN));
            return;
        }

        // プラグイン設定確認
        $settings = Grant_AI_Assistant::validate_api_settings();
        if (!$settings['is_ready']) {
            wp_send_json_error(__('AIチャット機能が設定されていません。管理者にお問い合わせください。', GAA_TEXT_DOMAIN));
            return;
        }
        
        try {
            // AI分析実行
            $ai_analysis = self::analyze_user_intent($user_message, $conversation_history);
            
            // 助成金検索実行
            $matching_grants = self::search_matching_grants($ai_analysis);
            
            // レスポンス構築
            $response = array(
                'success' => true,
                'message' => $ai_analysis['response_message'],
                'grants' => $matching_grants,
                'suggestions' => $ai_analysis['follow_up_questions'] ?? array(),
                'intent' => $ai_analysis['intent'] ?? array(),
                'search_info' => array(
                    'total_found' => count($matching_grants),
                    'search_keywords' => $ai_analysis['intent']['search_keywords'] ?? array()
                )
            );

            // デバッグ情報（デバッグモード時のみ）
            if (get_option('gaa_debug_mode', false)) {
                $response['debug'] = array(
                    'user_message' => $user_message,
                    'ai_analysis' => $ai_analysis,
                    'search_query_used' => self::$last_search_query ?? 'N/A'
                );
            }
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            self::log_error('Chat message handling failed', array(
                'message' => $e->getMessage(),
                'user_input' => $user_message
            ));
            
            wp_send_json_error(__('申し訳ございません。処理中にエラーが発生しました。しばらく経ってから再度お試しください。', GAA_TEXT_DOMAIN));
        }
    }

    /**
     * 最後に使用された検索クエリ（デバッグ用）
     */
    private static $last_search_query = null;
    
    /**
     * ユーザーの意図分析（OpenAI API）
     */
    private static function analyze_user_intent($message, $history = array()) {
        $api_key = get_option('gaa_openai_api_key');
        if (empty($api_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        // 会話履歴を文字列に変換
        $history_text = self::format_conversation_history($history);
        
        // AIプロンプト構築
        $prompt = self::build_analysis_prompt($message, $history_text);
        
        // OpenAI API呼び出し
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'model' => 'gpt-4',
                'messages' => array(
                    array('role' => 'system', 'content' => $prompt['system']),
                    array('role' => 'user', 'content' => $prompt['user'])
                ),
                'max_tokens' => 1200,
                'temperature' => 0.7,
                'top_p' => 0.9
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('OpenAI API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            throw new Exception('OpenAI API returned status code: ' . $status_code);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid OpenAI API response format');
        }
        
        // AIレスポンスを解析
        return self::parse_ai_response($data['choices'][0]['message']['content']);
    }

    /**
     * 会話履歴フォーマット
     */
    private static function format_conversation_history($history) {
        if (empty($history) || !is_array($history)) {
            return '';
        }

        $formatted = '';
        foreach ($history as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $role_label = ($msg['role'] === 'user') ? 'ユーザー' : 'AI';
                $formatted .= $role_label . ': ' . $msg['content'] . "\n";
            }
        }
        
        return $formatted;
    }
    
    /**
     * AI分析用プロンプト構築
     */
    private static function build_analysis_prompt($message, $history) {
        return array(
            'system' => 'あなたは助成金・補助金の専門コンサルタントです。
            ユーザーの質問を分析し、以下のJSON形式で回答してください：
            {
                "business_type": "事業の種類（例：IT業、製造業、サービス業、飲食業など）",
                "industry_category": "業界カテゴリ（例：technology、manufacturing、service、food等）",
                "purpose": "助成金の目的（例：設備投資、人材育成、DX化、新事業など）",
                "company_size": "会社規模（例：小規模事業者、中小企業、ベンチャー等）",
                "location": "地域（都道府県名があれば）",
                "urgency": "緊急度（高、中、低）",
                "amount_range": "希望金額帯（例：100万円以下、100-500万円等）",
                "response_message": "ユーザーへの親しみやすい返答メッセージ（150文字程度）",
                "follow_up_questions": ["次に聞くべき質問1", "質問2", "質問3"],
                "search_keywords": ["検索キーワード1", "キーワード2", "キーワード3"],
                "confidence_level": "分析の信頼度（1-10）"
            }
            
            ユーザーが具体的な質問をしていない場合は、まず業種や目的を聞き返してください。
            常に親しみやすく、専門用語は分かりやすく説明してください。',
            
            'user' => "会話履歴:\n{$history}\n\n最新のメッセージ: {$message}\n\n上記の会話を踏まえて、ユーザーの助成金ニーズを分析してください。"
        );
    }
    
    /**
     * AIレスポンス解析
     */
    private static function parse_ai_response($ai_content) {
        // JSON部分を抽出
        $json_start = strpos($ai_content, '{');
        $json_end = strrpos($ai_content, '}') + 1;
        
        if ($json_start !== false && $json_end !== false) {
            $json_string = substr($ai_content, $json_start, $json_end - $json_start);
            $parsed = json_decode($json_string, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return array(
                    'intent' => $parsed,
                    'response_message' => $parsed['response_message'] ?? __('ご質問いただき、ありがとうございます。最適な助成金を検索いたします。', GAA_TEXT_DOMAIN),
                    'follow_up_questions' => $parsed['follow_up_questions'] ?? array()
                );
            }
        }
        
        // JSONパースに失敗した場合のフォールバック
        return array(
            'intent' => array(
                'business_type' => '',
                'purpose' => '',
                'search_keywords' => self::extract_keywords_from_text($ai_content),
                'confidence_level' => 3
            ),
            'response_message' => __('ご質問いただき、ありがとうございます。関連する助成金を検索いたします。', GAA_TEXT_DOMAIN),
            'follow_up_questions' => array()
        );
    }

    /**
     * テキストからキーワード抽出（フォールバック用）
     */
    private static function extract_keywords_from_text($text) {
        $keywords = array();
        
        // 一般的な助成金関連キーワード
        $common_keywords = array(
            'IT' => array('IT', 'デジタル', 'DX', 'システム'),
            '設備投資' => array('設備', '機械', '投資', '導入'),
            '人材育成' => array('人材', '研修', '教育', '訓練'),
            '新事業' => array('新規', '事業', '創業', 'スタートアップ')
        );
        
        foreach ($common_keywords as $category => $words) {
            foreach ($words as $word) {
                if (strpos($text, $word) !== false) {
                    $keywords[] = $category;
                    break;
                }
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * 助成金データベース検索
     */
    private static function search_matching_grants($ai_analysis) {
        $intent = $ai_analysis['intent'] ?? array();
        $max_results = get_option('gaa_max_results', 6);
        
        // 基本検索クエリ構築
        $query_args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => $max_results,
            'meta_query' => array(
                array(
                    'key' => 'application_status',
                    'value' => array('open', 'active'),
                    'compare' => 'IN'
                )
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => 'priority_order',
            'order' => 'ASC'
        );

        // 税別クエリ配列初期化
        $tax_queries = array('relation' => 'AND');
        
        // 業界・事業種別での絞り込み
        if (!empty($intent['business_type']) || !empty($intent['industry_category'])) {
            $business_terms = self::map_business_to_taxonomy_terms($intent);
            if (!empty($business_terms)) {
                $tax_queries[] = array(
                    'taxonomy' => 'grant_category',
                    'field' => 'slug',
                    'terms' => $business_terms,
                    'operator' => 'IN'
                );
            }
        }
        
        // 地域での絞り込み
        if (!empty($intent['location'])) {
            $location_term = self::map_location_to_term($intent['location']);
            if ($location_term) {
                $tax_queries[] = array(
                    'taxonomy' => 'grant_prefecture',
                    'field' => 'slug',
                    'terms' => array($location_term, 'all'), // 全国対象も含める
                    'operator' => 'IN'
                );
            }
        }

        // 税別クエリをメインクエリに追加
        if (count($tax_queries) > 1) {
            $query_args['tax_query'] = $tax_queries;
        }
        
        // キーワード検索
        if (!empty($intent['search_keywords']) && is_array($intent['search_keywords'])) {
            $search_terms = implode(' ', $intent['search_keywords']);
            $query_args['s'] = $search_terms;
        }
        
        // 金額範囲での絞り込み
        if (!empty($intent['amount_range'])) {
            $amount_meta_query = self::build_amount_meta_query($intent['amount_range']);
            if ($amount_meta_query) {
                $query_args['meta_query'][] = $amount_meta_query;
            }
        }

        // デバッグ用（検索クエリ記録）
        self::$last_search_query = $query_args;
        
        // 検索実行
        $grants_query = new WP_Query($query_args);
        $grants = array();
        
        if ($grants_query->have_posts()) {
            while ($grants_query->have_posts()) {
                $grants_query->the_post();
                $post_id = get_the_ID();
                
                // 既存テーマの統一カード関数を使用
                $card_html = self::render_grant_card_with_fallback($post_id);
                
                if (!empty($card_html)) {
                    $grants[] = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'html' => $card_html,
                        'score' => self::calculate_matching_score($post_id, $intent),
                        'permalink' => get_permalink($post_id)
                    );
                }
            }
            wp_reset_postdata();
        }
        
        // スコア順にソート
        usort($grants, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return $grants;
    }

    /**
     * 助成金カードレンダリング（フォールバック付き）
     */
    private static function render_grant_card_with_fallback($post_id) {
        // 1. 既存テーマのgi_render_card関数を最優先で使用
        if (function_exists('gi_render_card')) {
            $html = gi_render_card($post_id, 'grid');
            if (!empty($html)) {
                return $html;
            }
        }

        // 2. GrantCardRendererクラスを試す
        if (class_exists('GrantCardRenderer')) {
            try {
                $renderer = GrantCardRenderer::getInstance();
                $html = $renderer->render($post_id, 'grid');
                if (!empty($html)) {
                    return $html;
                }
            } catch (Exception $e) {
                self::log_error('GrantCardRenderer failed', array('post_id' => $post_id, 'error' => $e->getMessage()));
            }
        }

        // 3. 統一テンプレート関数を試す
        if (function_exists('render_grant_card_unified')) {
            try {
                $user_favorites = function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : array();
                $html = render_grant_card_unified($post_id, 'grid', $user_favorites);
                if (!empty($html)) {
                    return $html;
                }
            } catch (Exception $e) {
                self::log_error('render_grant_card_unified failed', array('post_id' => $post_id, 'error' => $e->getMessage()));
            }
        }

        // 4. 最終フォールバック：シンプルなカード生成
        return self::generate_simple_card_fallback($post_id);
    }

    /**
     * シンプルカードフォールバック
     */
    private static function generate_simple_card_fallback($post_id) {
        $title = get_the_title($post_id);
        $permalink = get_permalink($post_id);
        $excerpt = get_the_excerpt($post_id);
        
        // 基本情報を安全に取得
        $organization = function_exists('gi_safe_get_meta') ? 
            gi_safe_get_meta($post_id, 'organization', '') : 
            get_post_meta($post_id, 'organization', true);
            
        $max_amount = function_exists('gi_safe_get_meta') ? 
            gi_safe_get_meta($post_id, 'max_amount', '') : 
            get_post_meta($post_id, 'max_amount', true);
            
        $deadline = function_exists('gi_get_formatted_deadline') ? 
            gi_get_formatted_deadline($post_id) :
            (function_exists('gi_safe_get_meta') ? 
                gi_safe_get_meta($post_id, 'deadline', '') : 
                get_post_meta($post_id, 'deadline', true));

        return sprintf(
            '<div class="gaa-grant-card-simple" data-post-id="%d">
                <div class="gaa-card-header">
                    <h3 class="gaa-card-title"><a href="%s">%s</a></h3>
                    <div class="gaa-card-org">%s</div>
                </div>
                <div class="gaa-card-body">
                    <p class="gaa-card-excerpt">%s</p>
                    <div class="gaa-card-meta">
                        <span class="gaa-card-amount">💰 %s</span>
                        <span class="gaa-card-deadline">⏰ %s</span>
                    </div>
                </div>
                <div class="gaa-card-footer">
                    <a href="%s" class="gaa-card-button">詳細を見る</a>
                </div>
            </div>',
            intval($post_id),
            esc_url($permalink),
            esc_html($title),
            esc_html($organization),
            esc_html(wp_trim_words($excerpt, 20)),
            esc_html($max_amount ?: '未定'),
            esc_html($deadline ?: '未定'),
            esc_url($permalink)
        );
    }

    /**
     * 事業種別から税別項目へのマッピング
     */
    private static function map_business_to_taxonomy_terms($intent) {
        $terms = array();
        
        // 業界マッピング
        $business_mapping = array(
            'IT' => array('it', 'digital', 'technology', 'software'),
            'サービス' => array('service', 'consulting', 'business-service'),
            '製造' => array('manufacturing', 'factory', 'production'),
            '飲食' => array('restaurant', 'food', 'hospitality'),
            '小売' => array('retail', 'shop', 'commerce'),
            '建設' => array('construction', 'building', 'real-estate'),
            '農業' => array('agriculture', 'farming', 'fishery'),
            '医療' => array('medical', 'healthcare', 'welfare'),
            '教育' => array('education', 'training', 'school'),
            '運輸' => array('logistics', 'transportation', 'delivery')
        );

        // 目的マッピング
        $purpose_mapping = array(
            '設備投資' => array('equipment', 'facility', 'machinery'),
            'DX' => array('digital', 'dx', 'it', 'automation'),
            '人材育成' => array('training', 'education', 'hr'),
            '新事業' => array('startup', 'new-business', 'innovation'),
            '研究開発' => array('research', 'development', 'innovation'),
            '海外展開' => array('export', 'overseas', 'global')
        );

        // 事業種別からterm取得
        $business_type = $intent['business_type'] ?? '';
        foreach ($business_mapping as $key => $taxonomy_terms) {
            if (strpos($business_type, $key) !== false) {
                $terms = array_merge($terms, $taxonomy_terms);
            }
        }

        // 目的からterm取得
        $purpose = $intent['purpose'] ?? '';
        foreach ($purpose_mapping as $key => $taxonomy_terms) {
            if (strpos($purpose, $key) !== false) {
                $terms = array_merge($terms, $taxonomy_terms);
            }
        }

        // 検索キーワードからも抽出
        $search_keywords = $intent['search_keywords'] ?? array();
        foreach ($search_keywords as $keyword) {
            foreach ($business_mapping as $category => $taxonomy_terms) {
                if (strpos($keyword, $category) !== false) {
                    $terms = array_merge($terms, $taxonomy_terms);
                }
            }
        }

        return array_unique($terms);
    }
    
    /**
     * 地域名から地域termへのマッピング
     */
    private static function map_location_to_term($location) {
        $prefectures = array(
            '北海道' => 'hokkaido', '青森' => 'aomori', '岩手' => 'iwate', '宮城' => 'miyagi',
            '秋田' => 'akita', '山形' => 'yamagata', '福島' => 'fukushima', '茨城' => 'ibaraki',
            '栃木' => 'tochigi', '群馬' => 'gunma', '埼玉' => 'saitama', '千葉' => 'chiba',
            '東京' => 'tokyo', '神奈川' => 'kanagawa', '新潟' => 'niigata', '富山' => 'toyama',
            '石川' => 'ishikawa', '福井' => 'fukui', '山梨' => 'yamanashi', '長野' => 'nagano',
            '岐阜' => 'gifu', '静岡' => 'shizuoka', '愛知' => 'aichi', '三重' => 'mie',
            '滋賀' => 'shiga', '京都' => 'kyoto', '大阪' => 'osaka', '兵庫' => 'hyogo',
            '奈良' => 'nara', '和歌山' => 'wakayama', '鳥取' => 'tottori', '島根' => 'shimane',
            '岡山' => 'okayama', '広島' => 'hiroshima', '山口' => 'yamaguchi', '徳島' => 'tokushima',
            '香川' => 'kagawa', '愛媛' => 'ehime', '高知' => 'kochi', '福岡' => 'fukuoka',
            '佐賀' => 'saga', '長崎' => 'nagasaki', '熊本' => 'kumamoto', '大分' => 'oita',
            '宮崎' => 'miyazaki', '鹿児島' => 'kagoshima', '沖縄' => 'okinawa'
        );
        
        foreach ($prefectures as $name => $slug) {
            if (strpos($location, $name) !== false) {
                return $slug;
            }
        }
        
        return null;
    }

    /**
     * 金額範囲メタクエリ構築
     */
    private static function build_amount_meta_query($amount_range) {
        // 金額範囲のパターンマッチング
        $patterns = array(
            '/(\d+)万円以下/' => array('max' => '$1' * 10000),
            '/(\d+)万円以上/' => array('min' => '$1' * 10000),
            '/(\d+)-(\d+)万円/' => array('min' => '$1' * 10000, 'max' => '$2' * 10000),
            '/(\d+)億円以下/' => array('max' => '$1' * 100000000),
            '/(\d+)億円以上/' => array('min' => '$1' * 100000000)
        );

        foreach ($patterns as $pattern => $range) {
            if (preg_match($pattern, $amount_range, $matches)) {
                $meta_query = array(
                    'key' => 'max_amount_numeric',
                    'type' => 'NUMERIC'
                );

                if (isset($range['min'])) {
                    $min_value = str_replace('$1', $matches[1], $range['min']);
                    $meta_query['value'] = intval($min_value);
                    $meta_query['compare'] = '>=';
                }

                if (isset($range['max'])) {
                    $max_value = str_replace('$1', $matches[1], $range['max']);
                    if (isset($range['min'])) {
                        // 範囲指定の場合
                        $min_value = str_replace('$1', $matches[1], $range['min']);
                        $max_value = str_replace('$2', $matches[2], $range['max']);
                        $meta_query = array(
                            'key' => 'max_amount_numeric',
                            'value' => array(intval($min_value), intval($max_value)),
                            'type' => 'NUMERIC',
                            'compare' => 'BETWEEN'
                        );
                    } else {
                        $meta_query['value'] = intval($max_value);
                        $meta_query['compare'] = '<=';
                    }
                }

                return $meta_query;
            }
        }

        return null;
    }
    
    /**
     * マッチングスコア計算
     */
    private static function calculate_matching_score($post_id, $intent) {
        $score = 50; // 基本スコア
        
        // 事業種別マッチング
        if (!empty($intent['business_type'])) {
            $grant_target = function_exists('gi_safe_get_meta') ? 
                gi_safe_get_meta($post_id, 'grant_target', '') :
                get_post_meta($post_id, 'grant_target', true);
            
            if (strpos($grant_target, $intent['business_type']) !== false) {
                $score += 30;
            }
        }
        
        // 目的マッチング
        if (!empty($intent['purpose'])) {
            $title = get_the_title($post_id);
            $content = get_the_content(null, false, $post_id);
            
            if (strpos($title . $content, $intent['purpose']) !== false) {
                $score += 25;
            }
        }
        
        // 採択率によるスコア調整
        $success_rate = function_exists('gi_safe_get_meta') ?
            gi_safe_get_meta($post_id, 'grant_success_rate', 0) :
            get_post_meta($post_id, 'grant_success_rate', true);
        
        $success_rate = intval($success_rate);
        if ($success_rate > 70) {
            $score += 20;
        } elseif ($success_rate > 50) {
            $score += 10;
        } elseif ($success_rate > 30) {
            $score += 5;
        }
        
        // 締切の近さ（緊急度が高い場合）
        if (isset($intent['urgency']) && $intent['urgency'] === '高') {
            $deadline = function_exists('gi_safe_get_meta') ?
                gi_safe_get_meta($post_id, 'deadline_date', 0) :
                get_post_meta($post_id, 'deadline_date', true);
            
            if (!empty($deadline)) {
                $days_left = (intval($deadline) - current_time('timestamp')) / DAY_IN_SECONDS;
                if ($days_left <= 30 && $days_left > 0) {
                    $score += 15; // 締切1ヶ月以内
                }
            }
        }

        // 優先度による調整
        $priority = function_exists('gi_safe_get_meta') ?
            gi_safe_get_meta($post_id, 'priority_order', 100) :
            get_post_meta($post_id, 'priority_order', true);
        
        if (intval($priority) < 50) {
            $score += 10; // 高優先度
        }

        // 注目助成金フラグ
        $is_featured = function_exists('gi_safe_get_meta') ?
            gi_safe_get_meta($post_id, 'is_featured', false) :
            get_post_meta($post_id, 'is_featured', true);
        
        if ($is_featured) {
            $score += 15;
        }
        
        return $score;
    }

    /**
     * エラーログ記録
     */
    private static function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = '[Grant AI Assistant Error] ' . $message;
            if (!empty($context)) {
                $log_message .= ' | Context: ' . wp_json_encode($context);
            }
            error_log($log_message);
        }

        // デバッグモードでは管理者にメール送信も考慮
        if (get_option('gaa_debug_mode', false)) {
            $admin_email = get_option('admin_email');
            if ($admin_email) {
                wp_mail(
                    $admin_email,
                    '[Grant AI Assistant] Error Alert',
                    $log_message,
                    array('Content-Type: text/plain; charset=UTF-8')
                );
            }
        }
    }
}