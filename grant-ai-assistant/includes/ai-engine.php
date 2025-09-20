<?php
/**
 * Grant AI Assistant - AI Engine (Production Version)
 * AI処理ロジックと助成金データベース検索エンジン
 * プロダクションレベルの安全性・パフォーマンス・エラーハンドリング
 * 
 * @package Grant_AI_Assistant
 * @version 1.0.2
 * @since 1.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Grant AI エンジンクラス
 * OpenAI APIとの連携、助成金データベース検索を処理
 * プロダクション環境での高信頼性を重視
 */
class Grant_AI_Engine {
    
    /**
     * APIリクエストのタイムアウト設定
     */
    const API_TIMEOUT = 30;
    
    /**
     * 最大リトライ回数
     */
    const MAX_RETRIES = 2;
    
    /**
     * キャッシュ有効期限（秒）
     */
    const CACHE_DURATION = 300; // 5分
    
    /**
     * 最後に使用された検索クエリ（デバッグ用）
     */
    private static $last_search_query = null;
    
    /**
     * エラーログ記録
     */
    private static $error_log = array();

    /**
     * AJAX チャットメッセージハンドラー（メインエントリーポイント）
     */
    public static function handle_chat_message() {
        // 基本セキュリティチェック
        if (!self::validate_request()) {
            return;
        }

        // レート制限チェック
        if (!self::check_rate_limit()) {
            wp_send_json_error(__('リクエストが多すぎます。少し時間をおいてから再試行してください。', GAA_TEXT_DOMAIN));
            return;
        }

        // プラグイン設定確認
        $settings = Grant_AI_Assistant::validate_api_settings();
        if (!$settings['is_ready']) {
            wp_send_json_error(__('AIチャット機能が設定されていません。管理者にお問い合わせください。', GAA_TEXT_DOMAIN));
            return;
        }

        // 入力データの取得・検証
        $input_data = self::sanitize_input_data();
        if (!$input_data) {
            return; // エラーレスポンスは sanitize_input_data 内で送信済み
        }

        try {
            // AI分析実行（キャッシュ機能付き）
            $ai_analysis = self::analyze_user_intent_cached($input_data['message'], $input_data['history']);
            
            // 助成金検索実行
            $matching_grants = self::search_matching_grants($ai_analysis);
            
            // レスポンス構築
            $response = array(
                'success' => true,
                'message' => $ai_analysis['response_message'],
                'grants' => $matching_grants,
                'suggestions' => isset($ai_analysis['follow_up_questions']) ? $ai_analysis['follow_up_questions'] : array(),
                'intent' => isset($ai_analysis['intent']) ? $ai_analysis['intent'] : array(),
                'search_info' => array(
                    'total_found' => count($matching_grants),
                    'search_keywords' => isset($ai_analysis['intent']['search_keywords']) ? $ai_analysis['intent']['search_keywords'] : array(),
                    'processing_time' => number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms'
                )
            );

            // デバッグ情報（デバッグモード時のみ）
            if (get_option('gaa_debug_mode', false) && current_user_can('manage_options')) {
                $response['debug'] = array(
                    'user_message' => $input_data['message'],
                    'ai_analysis' => $ai_analysis,
                    'search_query_used' => isset(self::$last_search_query) ? self::$last_search_query : 'N/A',
                    'memory_usage' => size_format(memory_get_peak_usage(true)),
                    'cache_status' => wp_cache_get('gaa_ai_analysis_' . md5($input_data['message'])) ? 'hit' : 'miss'
                );
            }
            
            // 成功ログ＋計測
            self::log_success('Chat message processed successfully', array(
                'grants_found' => count($matching_grants),
                'processing_time' => $response['search_info']['processing_time']
            ));
            if (class_exists('GAA_Analytics')) {
                GAA_Analytics::log_event('chat', array(
                    'query' => $input_data['message'],
                    'intent' => isset($ai_analysis['intent']) ? $ai_analysis['intent'] : array(),
                    'grants_found' => count($matching_grants)
                ));
                GAA_Analytics::increment_daily_metric('chats', 1);
            }
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            self::handle_exception($e, $input_data['message']);
        }
    }

    /**
     * リクエスト検証
     */
    private static function validate_request() {
        // nonce検証
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'gaa_chat_nonce')) {
            wp_send_json_error(__('セキュリティチェックに失敗しました。ページを再読み込みしてください。', GAA_TEXT_DOMAIN));
            return false;
        }

        // HTTPメソッドチェック
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(__('無効なリクエストメソッドです。', GAA_TEXT_DOMAIN));
            return false;
        }

        // Content-Typeチェック（可能な場合）
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (!empty($content_type) && strpos($content_type, 'application/x-www-form-urlencoded') === false && strpos($content_type, 'multipart/form-data') === false) {
            wp_send_json_error(__('無効なContent-Typeです。', GAA_TEXT_DOMAIN));
            return false;
        }

        return true;
    }

    /**
     * レート制限チェック
     */
    private static function check_rate_limit() {
        $user_id = get_current_user_id();
        $ip_address = self::get_client_ip();
        
        // ログインユーザーとIPアドレスの両方でチェック
        $rate_limit_keys = array(
            'gaa_rate_limit_user_' . $user_id,
            'gaa_rate_limit_ip_' . md5($ip_address)
        );
        
        $max_requests = 30; // 5分間で30リクエスト
        $time_window = 300; // 5分
        
        foreach ($rate_limit_keys as $key) {
            $requests = get_transient($key);
            if ($requests === false) {
                set_transient($key, 1, $time_window);
            } elseif ($requests >= $max_requests) {
                return false;
            } else {
                set_transient($key, $requests + 1, $time_window);
            }
        }
        
        return true;
    }

    /**
     * クライアントIPアドレス取得
     */
    private static function get_client_ip() {
        $headers = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * 入力データのサニタイズ・検証
     */
    private static function sanitize_input_data() {
        $user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $conversation_history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : array();

        // メッセージの検証
        if (empty($user_message)) {
            wp_send_json_error(__('メッセージが空です。質問を入力してください。', GAA_TEXT_DOMAIN));
            return false;
        }

        if (mb_strlen($user_message) > 500) {
            wp_send_json_error(__('メッセージが長すぎます。500文字以内で入力してください。', GAA_TEXT_DOMAIN));
            return false;
        }

        // 危険な文字・パターンのチェック
        if (preg_match('/[<>"\']|script|javascript|eval\(|exec\(/i', $user_message)) {
            wp_send_json_error(__('不正な文字が含まれています。', GAA_TEXT_DOMAIN));
            return false;
        }

        // 会話履歴の検証
        if (!is_array($conversation_history)) {
            $conversation_history = array();
        }

        // 履歴サイズ制限（メモリ保護）
        if (count($conversation_history) > 20) {
            $conversation_history = array_slice($conversation_history, -20);
        }

        return array(
            'message' => $user_message,
            'history' => $conversation_history
        );
    }

    /**
     * キャッシュ機能付きユーザー意図分析
     */
    private static function analyze_user_intent_cached($message, $history = array()) {
        // キャッシュキー生成
        $cache_key = 'gaa_ai_analysis_' . md5($message . serialize($history));
        
        // キャッシュから取得試行
        $cached_result = wp_cache_get($cache_key);
        if ($cached_result !== false) {
            self::log_info('AI analysis cache hit', array('message_hash' => md5($message)));
            return $cached_result;
        }

        // AI分析実行
        $result = self::analyze_user_intent($message, $history);
        
        // キャッシュに保存
        wp_cache_set($cache_key, $result, '', self::CACHE_DURATION);
        
        return $result;
    }
    
    /**
     * ユーザーの意図分析（OpenAI API）- プロダクション最適化版
     */
    private static function analyze_user_intent($message, $history = array()) {
        $settings = Grant_AI_Assistant::get_settings();
        $api_key = $settings['api_key'];
        
        if (empty($api_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        // 会話履歴をフォーマット
        $history_text = self::format_conversation_history($history);
        
        // AIプロンプト構築（最適化済み）
        $prompt = self::build_optimized_prompt($message, $history_text);
        
        // リトライ機能付きAPI呼び出し
        $response_data = null;
        $last_error = null;
        
        for ($retry = 0; $retry <= self::MAX_RETRIES; $retry++) {
            try {
                $response_data = self::call_openai_api($api_key, $prompt, $retry);
                break; // 成功時はループを抜ける
            } catch (Exception $e) {
                $last_error = $e;
                if ($retry < self::MAX_RETRIES) {
                    // 指数バックオフで待機
                    sleep(pow(2, $retry));
                    self::log_warning("API retry {$retry}", array('error' => $e->getMessage()));
                }
            }
        }
        
        if (!$response_data) {
            throw new Exception('OpenAI API failed after retries: ' . ($last_error ? $last_error->getMessage() : 'Unknown error'));
        }
        
        // AIレスポンスを解析
        return self::parse_ai_response($response_data);
    }

    /**
     * OpenAI API呼び出し（最適化・エラーハンドリング強化）
     */
    private static function call_openai_api($api_key, $prompt, $retry_count = 0) {
        $request_body = array(
            'model' => 'gpt-4',
            'messages' => array(
                array('role' => 'system', 'content' => $prompt['system']),
                array('role' => 'user', 'content' => $prompt['user'])
            ),
            'max_tokens' => 800, // 最適化：レスポンス時間短縮
            'temperature' => 0.7,
            'top_p' => 0.9,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'WordPress/GrantAI-Assistant/' . GAA_VERSION
        );

        $args = array(
            'headers' => $headers,
            'body' => wp_json_encode($request_body),
            'timeout' => self::API_TIMEOUT,
            'httpversion' => '1.1',
            'compress' => true,
            'decompress' => true
        );

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
        
        // レスポンス検証
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // ステータスコード別エラーハンドリング
        if ($status_code !== 200) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : "HTTP {$status_code}";
            
            switch ($status_code) {
                case 401:
                    throw new Exception('Invalid API key');
                case 429:
                    throw new Exception('Rate limit exceeded');
                case 503:
                    throw new Exception('Service temporarily unavailable');
                default:
                    throw new Exception("API error: {$error_message}");
            }
        }
        
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return $data['choices'][0]['message']['content'];
    }

    /**
     * 最適化されたプロンプト構築
     */
    private static function build_optimized_prompt($message, $history) {
        return array(
            'system' => 'あなたは日本の助成金・補助金の専門コンサルタントです。ユーザーの質問を分析し、以下のJSON形式で正確に回答してください。

JSON形式:
{
    "business_type": "事業の種類（例：IT業、製造業、サービス業、飲食業、建設業、小売業など）",
    "industry_category": "業界カテゴリ（例：technology、manufacturing、service、food、construction、retail等）",
    "purpose": "助成金の目的（例：設備投資、人材育成、DX化、新事業開始、研究開発、海外展開など）",
    "company_size": "会社規模（例：小規模事業者、中小企業、ベンチャー、スタートアップ等）",
    "location": "地域（都道府県名があれば記載、なければ空文字）",
    "urgency": "緊急度（高、中、低のいずれか）",
    "amount_range": "希望金額帯（例：100万円以下、100-500万円、500万円以上等）",
    "response_message": "ユーザーへの親しみやすい返答メッセージ（120文字以内、丁寧語使用）",
    "follow_up_questions": ["次に聞くべき質問1", "質問2", "質問3"],
    "search_keywords": ["検索キーワード1", "キーワード2", "キーワード3"],
    "confidence_level": "分析の信頼度（1-10の整数）"
}

重要な指示:
- 必ずJSONフォーマットで回答
- ユーザーが具体的でない場合は、follow_up_questionsで詳細を聞き返す
- response_messageは親しみやすく、専門用語は避ける
- search_keywordsは助成金検索に有効な単語を選ぶ',
            
            'user' => "会話履歴:\n{$history}\n\n最新メッセージ: {$message}\n\n上記を分析して、適切な助成金検索のためのJSONを生成してください。"
        );
    }

    /**
     * 会話履歴のフォーマット（最適化）
     */
    private static function format_conversation_history($history) {
        if (empty($history) || !is_array($history)) {
            return '';
        }

        $formatted_lines = array();
        $max_history = 10; // メモリとトークン使用量制限
        $recent_history = array_slice($history, -$max_history);
        
        foreach ($recent_history as $msg) {
            if (isset($msg['role'], $msg['content']) && !empty($msg['content'])) {
                $role_label = ($msg['role'] === 'user') ? 'ユーザー' : 'AI';
                $content = mb_substr($msg['content'], 0, 100); // 長さ制限
                $formatted_lines[] = $role_label . ': ' . $content;
            }
        }
        
        return implode("\n", $formatted_lines);
    }
    
    /**
     * AIレスポンス解析（エラーハンドリング強化）
     */
    private static function parse_ai_response($ai_content) {
        // JSONブロックを抽出
        $json_pattern = '/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s';
        
        if (preg_match($json_pattern, $ai_content, $matches)) {
            $json_string = $matches[0];
            $parsed = json_decode($json_string, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                // 必須フィールドの検証
                $required_fields = array('business_type', 'purpose', 'response_message');
                foreach ($required_fields as $field) {
                    if (!isset($parsed[$field])) {
                        $parsed[$field] = '';
                    }
                }
                
                // デフォルト値の設定
                $response_message = !empty($parsed['response_message']) ? $parsed['response_message'] : __('ご質問いただき、ありがとうございます。最適な助成金を検索いたします。', GAA_TEXT_DOMAIN);
                $follow_up_questions = isset($parsed['follow_up_questions']) && is_array($parsed['follow_up_questions']) ? $parsed['follow_up_questions'] : array();
                
                return array(
                    'intent' => $parsed,
                    'response_message' => $response_message,
                    'follow_up_questions' => $follow_up_questions
                );
            }
        }
        
        // JSONパースに失敗した場合のフォールバック
        self::log_warning('AI response JSON parse failed', array('content' => substr($ai_content, 0, 200)));
        
        return array(
            'intent' => array(
                'business_type' => '',
                'purpose' => '',
                'search_keywords' => self::extract_keywords_from_text($ai_content),
                'confidence_level' => 3
            ),
            'response_message' => __('ご質問いただき、ありがとうございます。関連する助成金を検索いたします。', GAA_TEXT_DOMAIN),
            'follow_up_questions' => array(
                '業種を教えてください（IT業、製造業、サービス業など）',
                '助成金の利用目的は何ですか？（設備投資、人材育成など）',
                'どちらの地域の事業者様ですか？'
            )
        );
    }

    /**
     * テキストからキーワード抽出（フォールバック用・改良版）
     */
    private static function extract_keywords_from_text($text) {
        $keywords = array();
        
        // 助成金関連キーワードの辞書
        $keyword_patterns = array(
            'IT関連' => array('IT', 'デジタル', 'DX', 'システム', 'AI', 'IoT', 'クラウド'),
            '設備投資' => array('設備', '機械', '投資', '導入', '購入', '更新'),
            '人材育成' => array('人材', '研修', '教育', '訓練', '講習', 'セミナー'),
            '新事業' => array('新規', '事業', '創業', 'スタートアップ', '起業', '開業'),
            'DX化' => array('デジタル化', 'DX', 'オンライン', 'EC', 'ウェブ'),
            '海外展開' => array('海外', '輸出', '国際', 'グローバル', '展開')
        );
        
        foreach ($keyword_patterns as $category => $words) {
            foreach ($words as $word) {
                if (mb_strpos($text, $word) !== false) {
                    $keywords[] = $category;
                    break;
                }
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * 助成金データベース検索（最適化・パフォーマンス改善版）
     */
    private static function search_matching_grants($ai_analysis) {
        $intent = isset($ai_analysis['intent']) ? $ai_analysis['intent'] : array();
        $max_results = get_option('gaa_max_results', 6);
        
        // 基本検索クエリ構築（最適化）
        $query_args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => min($max_results * 2, 20), // 多めに取得してスコアでフィルタ
            'no_found_rows' => true, // パフォーマンス最適化
            'update_post_meta_cache' => false, // メタキャッシュ無効化
            'update_post_term_cache' => false, // タームキャッシュ無効化
            'meta_query' => array(
                array(
                    'key' => 'application_status',
                    'value' => array('open', 'active'),
                    'compare' => 'IN'
                )
            ),
            'orderby' => array(
                'meta_value_num' => 'ASC',
                'date' => 'DESC'
            ),
            'meta_key' => 'priority_order'
        );

        // 検索条件の動的構築
        $tax_queries = array('relation' => 'OR'); // ORに変更でより多くの結果を取得
        
        // 業界・事業種別での絞り込み
        $business_terms = self::map_business_to_taxonomy_terms($intent);
        if (!empty($business_terms)) {
            $tax_queries[] = array(
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $business_terms,
                'operator' => 'IN'
            );
        }
        
        // 地域での絞り込み
        $location = isset($intent['location']) ? $intent['location'] : '';
        if (!empty($location)) {
            $location_term = self::map_location_to_term($location);
            if ($location_term) {
                $tax_queries[] = array(
                    'taxonomy' => 'grant_prefecture',
                    'field' => 'slug',
                    'terms' => array($location_term, 'all', 'nationwide'),
                    'operator' => 'IN'
                );
            }
        }

        // 税別クエリを適用
        if (count($tax_queries) > 1) {
            $query_args['tax_query'] = $tax_queries;
        }
        
        // キーワード検索（重み付け検索）
        $search_keywords = isset($intent['search_keywords']) ? $intent['search_keywords'] : array();
        if (!empty($search_keywords)) {
            $search_terms = implode(' ', $search_keywords);
            $query_args['s'] = $search_terms;
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
                
                // マッチングスコア計算
                $score = self::calculate_matching_score($post_id, $intent);
                
                // 最小スコア閾値
                if ($score < 30) {
                    continue;
                }
                
                // 既存テーマのカード関数を使用
                $card_html = self::render_grant_card_with_fallback($post_id);
                
                if (!empty($card_html)) {
                    $grants[] = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'html' => $card_html,
                        'score' => $score,
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
        
        // 最大件数でカット
        $grants = array_slice($grants, 0, $max_results);
        
        return $grants;
    }

    /**
     * 助成金カードレンダリング（フォールバック付き・最適化版）
     */
    private static function render_grant_card_with_fallback($post_id) {
        // 1. 既存テーマのgi_render_card関数を最優先で使用
        if (function_exists('gi_render_card')) {
            $html = gi_render_card($post_id, 'grid');
            if (!empty($html) && !self::is_error_html($html)) {
                return $html;
            }
        }

        // 2. GrantCardRendererクラスを試す
        if (class_exists('GrantCardRenderer')) {
            try {
                $renderer = GrantCardRenderer::getInstance();
                if (method_exists($renderer, 'render')) {
                    $html = $renderer->render($post_id, 'grid');
                    if (!empty($html) && !self::is_error_html($html)) {
                        return $html;
                    }
                }
            } catch (Exception $e) {
                self::log_warning('GrantCardRenderer failed', array('post_id' => $post_id, 'error' => $e->getMessage()));
            }
        }

        // 3. 統一テンプレート関数を試す
        if (function_exists('render_grant_card_unified')) {
            try {
                $user_favorites = function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : array();
                $html = render_grant_card_unified($post_id, 'grid', $user_favorites);
                if (!empty($html) && !self::is_error_html($html)) {
                    return $html;
                }
            } catch (Exception $e) {
                self::log_warning('render_grant_card_unified failed', array('post_id' => $post_id, 'error' => $e->getMessage()));
            }
        }

        // 4. 最終フォールバック：高品質なシンプルカード生成
        return self::generate_enhanced_fallback_card($post_id);
    }

    /**
     * エラーHTMLかどうかの判定
     */
    private static function is_error_html($html) {
        $error_patterns = array('error', 'カードレンダラーが利用できません', 'grant-card-error');
        foreach ($error_patterns as $pattern) {
            if (stripos($html, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 高品質なフォールバックカード生成
     */
    private static function generate_enhanced_fallback_card($post_id) {
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

        $application_status = function_exists('gi_safe_get_meta') ?
            gi_safe_get_meta($post_id, 'application_status', 'open') :
            get_post_meta($post_id, 'application_status', true);

        // ステータス表示の日本語化
        $status_labels = array(
            'open' => '募集中',
            'active' => '募集中',
            'closed' => '募集終了',
            'upcoming' => '募集予定'
        );
        $status_text = isset($status_labels[$application_status]) ? $status_labels[$application_status] : '確認要';
        $status_class = $application_status === 'open' || $application_status === 'active' ? 'status-open' : 'status-other';

        return sprintf(
            '<div class="gaa-grant-card-enhanced" data-post-id="%d">
                <div class="gaa-card-header">
                    <div class="gaa-card-status %s">%s</div>
                    <h3 class="gaa-card-title"><a href="%s" target="_blank" rel="noopener">%s</a></h3>
                    <div class="gaa-card-org">%s</div>
                </div>
                <div class="gaa-card-body">
                    <p class="gaa-card-excerpt">%s</p>
                    <div class="gaa-card-meta">
                        <div class="gaa-meta-item">
                            <span class="gaa-meta-icon">💰</span>
                            <span class="gaa-meta-label">金額:</span>
                            <span class="gaa-meta-value">%s</span>
                        </div>
                        <div class="gaa-meta-item">
                            <span class="gaa-meta-icon">⏰</span>
                            <span class="gaa-meta-label">締切:</span>
                            <span class="gaa-meta-value">%s</span>
                        </div>
                    </div>
                </div>
                <div class="gaa-card-footer">
                    <a href="%s" class="gaa-card-button" target="_blank" rel="noopener">詳細を見る</a>
                    <span class="gaa-card-new-tab">↗</span>
                </div>
            </div>
            <style>
            .gaa-grant-card-enhanced {
                background: white;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                border: 1px solid #e1e5e9;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            .gaa-grant-card-enhanced:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }
            .gaa-card-status {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                margin-bottom: 12px;
            }
            .gaa-card-status.status-open {
                background: #dcfce7;
                color: #166534;
            }
            .gaa-card-status.status-other {
                background: #f3f4f6;
                color: #6b7280;
            }
            .gaa-card-title {
                margin: 0 0 8px 0;
                font-size: 16px;
                font-weight: 600;
                line-height: 1.4;
            }
            .gaa-card-title a {
                color: #1e293b;
                text-decoration: none;
            }
            .gaa-card-title a:hover {
                color: #0073aa;
            }
            .gaa-card-org {
                font-size: 13px;
                color: #64748b;
                margin-bottom: 16px;
                font-weight: 500;
            }
            .gaa-card-excerpt {
                font-size: 14px;
                line-height: 1.5;
                color: #475569;
                margin-bottom: 16px;
            }
            .gaa-card-meta {
                display: flex;
                flex-direction: column;
                gap: 8px;
                margin-bottom: 20px;
            }
            .gaa-meta-item {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 13px;
            }
            .gaa-meta-icon {
                font-size: 14px;
            }
            .gaa-meta-label {
                color: #64748b;
                font-weight: 500;
                min-width: 40px;
            }
            .gaa-meta-value {
                color: #1e293b;
                font-weight: 500;
            }
            .gaa-card-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .gaa-card-button {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 10px 20px;
                background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            .gaa-card-button:hover {
                background: linear-gradient(135deg, #005a87 0%, #004066 100%);
                transform: translateY(-1px);
            }
            .gaa-card-new-tab {
                color: #64748b;
                font-size: 12px;
            }
            </style>',
            intval($post_id),
            esc_attr($status_class),
            esc_html($status_text),
            esc_url($permalink),
            esc_html($title),
            esc_html($organization ?: '実施機関不明'),
            esc_html(wp_trim_words($excerpt, 25, '...')),
            esc_html($max_amount ?: '未定'),
            esc_html($deadline ?: '未定'),
            esc_url($permalink)
        );
    }

    /**
     * 事業種別からタクソノミー項目への高度なマッピング
     */
    private static function map_business_to_taxonomy_terms($intent) {
        $terms = array();
        
        // 詳細な業界マッピング辞書
        $business_mapping = array(
            'IT' => array('it', 'digital', 'technology', 'software', 'web', 'ai', 'iot'),
            'サービス' => array('service', 'consulting', 'business-service', 'professional'),
            '製造' => array('manufacturing', 'factory', 'production', 'industrial'),
            '飲食' => array('restaurant', 'food', 'hospitality', 'catering'),
            '小売' => array('retail', 'shop', 'commerce', 'sales'),
            '建設' => array('construction', 'building', 'real-estate', 'architecture'),
            '農業' => array('agriculture', 'farming', 'fishery', 'livestock'),
            '医療' => array('medical', 'healthcare', 'welfare', 'pharmaceutical'),
            '教育' => array('education', 'training', 'school', 'learning'),
            '運輸' => array('logistics', 'transportation', 'delivery', 'shipping'),
            '金融' => array('finance', 'banking', 'insurance', 'investment'),
            '観光' => array('tourism', 'travel', 'hotel', 'leisure')
        );

        // 目的別マッピング
        $purpose_mapping = array(
            '設備投資' => array('equipment', 'facility', 'machinery', 'infrastructure'),
            'DX' => array('digital', 'dx', 'digitalization', 'automation', 'online'),
            '人材育成' => array('training', 'education', 'hr', 'skill-development'),
            '新事業' => array('startup', 'new-business', 'innovation', 'entrepreneurship'),
            '研究開発' => array('research', 'development', 'innovation', 'technology'),
            '海外展開' => array('export', 'overseas', 'global', 'international'),
            '環境' => array('environment', 'eco', 'green', 'sustainability'),
            'エネルギー' => array('energy', 'renewable', 'solar', 'efficiency')
        );

        // 事業種別からterm取得
        $business_type = isset($intent['business_type']) ? $intent['business_type'] : '';
        $industry_category = isset($intent['industry_category']) ? $intent['industry_category'] : '';
        
        // 複数のキーワードでマッチング
        $search_targets = array($business_type, $industry_category);
        
        foreach ($search_targets as $target) {
            if (empty($target)) continue;
            
            foreach ($business_mapping as $key => $taxonomy_terms) {
                if (mb_strpos($target, $key) !== false || in_array(strtolower($target), $taxonomy_terms)) {
                    $terms = array_merge($terms, $taxonomy_terms);
                }
            }
        }

        // 目的からterm取得
        $purpose = isset($intent['purpose']) ? $intent['purpose'] : '';
        foreach ($purpose_mapping as $key => $taxonomy_terms) {
            if (mb_strpos($purpose, $key) !== false) {
                $terms = array_merge($terms, $taxonomy_terms);
            }
        }

        // 検索キーワードからも抽出
        $search_keywords = isset($intent['search_keywords']) ? $intent['search_keywords'] : array();
        if (is_array($search_keywords)) {
            foreach ($search_keywords as $keyword) {
                foreach (array_merge($business_mapping, $purpose_mapping) as $category => $taxonomy_terms) {
                    if (mb_strpos($keyword, $category) !== false) {
                        $terms = array_merge($terms, $taxonomy_terms);
                    }
                }
            }
        }

        return array_unique($terms);
    }
    
    /**
     * 地域名から地域termへのマッピング（完全版）
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
        
        // 地方名マッピングも追加
        $regions = array(
            '関東' => array('tokyo', 'kanagawa', 'saitama', 'chiba', 'ibaraki', 'tochigi', 'gunma'),
            '関西' => array('osaka', 'kyoto', 'hyogo', 'nara', 'wakayama', 'shiga'),
            '東海' => array('aichi', 'gifu', 'shizuoka', 'mie'),
            '九州' => array('fukuoka', 'saga', 'nagasaki', 'kumamoto', 'oita', 'miyazaki', 'kagoshima', 'okinawa')
        );
        
        // 都道府県名での完全一致
        foreach ($prefectures as $name => $slug) {
            if (mb_strpos($location, $name) !== false) {
                return $slug;
            }
        }
        
        // 地方名での検索
        foreach ($regions as $region_name => $region_slugs) {
            if (mb_strpos($location, $region_name) !== false) {
                return $region_slugs[0]; // 代表的な都道府県を返す
            }
        }
        
        return null;
    }
    
    /**
     * 高度なマッチングスコア計算（重み付け改良版）
     */
    private static function calculate_matching_score($post_id, $intent) {
        $score = 50; // 基本スコア
        
        // 1. 事業種別マッチング（重要度：高）
        $business_type = isset($intent['business_type']) ? $intent['business_type'] : '';
        if (!empty($business_type)) {
            $grant_target = function_exists('gi_safe_get_meta') ? 
                gi_safe_get_meta($post_id, 'grant_target', '') :
                get_post_meta($post_id, 'grant_target', true);
            
            // 部分一致で段階的スコア
            if (mb_strpos($grant_target, $business_type) !== false) {
                $score += 35;
            } elseif (!empty($grant_target)) {
                // 関連キーワードでの部分マッチング
                $related_keywords = self::get_related_business_keywords($business_type);
                foreach ($related_keywords as $keyword) {
                    if (mb_strpos($grant_target, $keyword) !== false) {
                        $score += 15;
                        break;
                    }
                }
            }
        }
        
        // 2. 目的マッチング（重要度：高）
        $purpose = isset($intent['purpose']) ? $intent['purpose'] : '';
        if (!empty($purpose)) {
            $title = get_the_title($post_id);
            $content = get_the_content(null, false, $post_id);
            $combined_text = $title . ' ' . $content;
            
            if (mb_strpos($combined_text, $purpose) !== false) {
                $score += 30;
            } else {
                // 関連用語でのマッチング
                $related_purposes = self::get_related_purpose_keywords($purpose);
                foreach ($related_purposes as $keyword) {
                    if (mb_strpos($combined_text, $keyword) !== false) {
                        $score += 12;
                        break;
                    }
                }
            }
        }
        
        // 3. 採択率・成功率による調整（重要度：中）
        $success_rate = function_exists('gi_safe_get_meta') ?
            gi_safe_get_meta($post_id, 'grant_success_rate', 0) :
            get_post_meta($post_id, 'grant_success_rate', true);
        
        $success_rate = intval($success_rate);
        if ($success_rate >= 80) {
            $score += 25;
        } elseif ($success_rate >= 60) {
            $score += 20;
        } elseif ($success_rate >= 40) {
            $score += 15;
        } elseif ($success_rate >= 20) {
            $score += 10;
        }
        
        // 4. 金額適合性（重要度：中）
        $amount_range = isset($intent['amount_range']) ? $intent['amount_range'] : '';
        if (!empty($amount_range)) {
            $max_amount_numeric = function_exists('gi_safe_get_meta') ?
                gi_safe_get_meta($post_id, 'max_amount_numeric', 0) :
                get_post_meta($post_id, 'max_amount_numeric', true);
            
            if (self::is_amount_in_range($max_amount_numeric, $amount_range)) {
                $score += 20;
            }
        }
        
        // 5. 締切の近さ・緊急度（重要度：中）
        $urgency = isset($intent['urgency']) ? $intent['urgency'] : '';
        if ($urgency === '高') {
            $deadline = function_exists('gi_safe_get_meta') ?
                gi_safe_get_meta($post_id, 'deadline_date', 0) :
                get_post_meta($post_id, 'deadline_date', true);
            
            if (!empty($deadline)) {
                $days_left = (intval($deadline) - current_time('timestamp')) / DAY_IN_SECONDS;
                if ($days_left <= 7 && $days_left > 0) {
                    $score += 20; // 締切1週間以内
                } elseif ($days_left <= 30 && $days_left > 0) {
                    $score += 15; // 締切1ヶ月以内
                }
            }
        }

        // 6. 優先度・注目度による調整（重要度：中）
        $priority = function_exists('gi_safe_get_meta') ?
            gi_safe_get_meta($post_id, 'priority_order', 100) :
            get_post_meta($post_id, 'priority_order', true);
        
        $priority = intval($priority);
        if ($priority <= 10) {
            $score += 25; // 最高優先度
        } elseif ($priority <= 30) {
            $score += 15; // 高優先度
        } elseif ($priority <= 50) {
            $score += 10; // 中優先度
        }

        // 7. 注目助成金フラグ（重要度：低）
        $is_featured = function_exists('gi_safe_get_meta') ?
            gi_safe_get_meta($post_id, 'is_featured', false) :
            get_post_meta($post_id, 'is_featured', true);
        
        if ($is_featured) {
            $score += 15;
        }

        // 8. 投稿の新しさ（重要度：低）
        $post_date = get_the_date('U', $post_id);
        $days_since_published = (current_time('timestamp') - $post_date) / DAY_IN_SECONDS;
        
        if ($days_since_published <= 30) {
            $score += 10; // 1ヶ月以内の新しい助成金
        } elseif ($days_since_published <= 90) {
            $score += 5; // 3ヶ月以内
        }
        
        return max(0, min(150, $score)); // 0-150の範囲に制限
    }

    /**
     * 関連事業キーワード取得
     */
    private static function get_related_business_keywords($business_type) {
        $keyword_map = array(
            'IT' => array('情報技術', 'システム', 'ソフトウェア', 'デジタル'),
            '製造' => array('製造業', 'もの作り', 'ファクトリー', '工場'),
            'サービス' => array('サービス業', 'コンサル', 'プロフェッショナル'),
            '飲食' => array('飲食店', 'レストラン', 'フード', '食品'),
            '小売' => array('小売業', 'ショップ', '店舗', 'EC'),
            '建設' => array('建設業', '土木', '建築', '不動産')
        );
        
        foreach ($keyword_map as $key => $keywords) {
            if (mb_strpos($business_type, $key) !== false) {
                return $keywords;
            }
        }
        
        return array();
    }

    /**
     * 関連目的キーワード取得
     */
    private static function get_related_purpose_keywords($purpose) {
        $keyword_map = array(
            '設備投資' => array('機械導入', '設備更新', '生産性向上', 'インフラ'),
            'DX' => array('デジタル化', 'IT導入', 'オンライン化', '効率化'),
            '人材育成' => array('研修', '教育', 'スキルアップ', '能力開発'),
            '新事業' => array('事業展開', 'イノベーション', '新規参入', '多角化'),
            '研究開発' => array('R&D', '技術開発', '新技術', '特許'),
            '海外展開' => array('輸出', '国際展開', 'グローバル', '越境')
        );
        
        foreach ($keyword_map as $key => $keywords) {
            if (mb_strpos($purpose, $key) !== false) {
                return $keywords;
            }
        }
        
        return array();
    }

    /**
     * 金額範囲チェック
     */
    private static function is_amount_in_range($amount, $range_text) {
        $amount = intval($amount);
        if ($amount <= 0) return false;
        
        // 範囲パターンの解析
        if (preg_match('/(\d+)万円以下/', $range_text, $matches)) {
            return $amount <= (intval($matches[1]) * 10000);
        }
        
        if (preg_match('/(\d+)万円以上/', $range_text, $matches)) {
            return $amount >= (intval($matches[1]) * 10000);
        }
        
        if (preg_match('/(\d+)-(\d+)万円/', $range_text, $matches)) {
            $min = intval($matches[1]) * 10000;
            $max = intval($matches[2]) * 10000;
            return $amount >= $min && $amount <= $max;
        }
        
        return false;
    }

    /**
     * 例外処理（統一エラーハンドリング）
     */
    private static function handle_exception($exception, $user_message = '') {
        $error_message = $exception->getMessage();
        $error_context = array(
            'user_message' => substr($user_message, 0, 100),
            'error' => $error_message,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        );

        // エラーログ記録
        self::log_error('Chat message processing failed', $error_context);

        // ユーザー向けエラーメッセージの決定
        $user_error_message = __('申し訳ございません。処理中にエラーが発生しました。', GAA_TEXT_DOMAIN);
        
        if (strpos($error_message, 'API') !== false) {
            $user_error_message = __('AIサービスとの通信でエラーが発生しました。しばらく経ってから再度お試しください。', GAA_TEXT_DOMAIN);
        } elseif (strpos($error_message, 'timeout') !== false) {
            $user_error_message = __('処理がタイムアウトしました。もう一度お試しください。', GAA_TEXT_DOMAIN);
        } elseif (strpos($error_message, 'Rate limit') !== false) {
            $user_error_message = __('リクエストが多すぎます。少し時間をおいてから再試行してください。', GAA_TEXT_DOMAIN);
        }

        wp_send_json_error($user_error_message);
    }

    /**
     * ログ記録メソッド群
     */
    private static function log_error($message, $context = array()) {
        self::write_log('ERROR', $message, $context);
    }

    private static function log_warning($message, $context = array()) {
        self::write_log('WARNING', $message, $context);
    }

    private static function log_info($message, $context = array()) {
        self::write_log('INFO', $message, $context);
    }

    private static function log_success($message, $context = array()) {
        self::write_log('SUCCESS', $message, $context);
    }

    /**
     * 統一ログ出力
     */
    private static function write_log($level, $message, $context = array()) {
        // デバッグモードまたは本番エラーログ
        if ((defined('WP_DEBUG') && WP_DEBUG) || $level === 'ERROR') {
            $log_entry = array(
                'timestamp' => current_time('c'),
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'memory' => memory_get_usage(true),
                'user_id' => get_current_user_id(),
                'ip' => self::get_client_ip()
            );
            
            $log_message = sprintf(
                '[Grant AI Assistant] [%s] %s | Context: %s',
                $level,
                $message,
                wp_json_encode($context)
            );
            
            error_log($log_message);
            
            // 重要なエラーは管理者にメール通知（本番環境）
            if ($level === 'ERROR' && !defined('WP_DEBUG') && get_option('gaa_debug_mode', false)) {
                wp_mail(
                    get_option('admin_email'),
                    '[Grant AI Assistant] Critical Error',
                    $log_message,
                    array('Content-Type: text/plain; charset=UTF-8')
                );
            }
        }
    }
}

// AJAX アクションフック登録
add_action('wp_ajax_gaa_handle_chat', array('Grant_AI_Engine', 'handle_chat_message'));
add_action('wp_ajax_nopriv_gaa_handle_chat', array('Grant_AI_Engine', 'handle_chat_message'));