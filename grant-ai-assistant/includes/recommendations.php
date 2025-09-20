<?php
/**
 * Grant AI Assistant - Recommendations & Search Integration
 * ユーザー別レコメンド保存、検索結果ブースト、関連ショートコード
 */

if (!defined('ABSPATH')) {
    exit;
}

class GAA_Recommendations {
    const COOKIE_NAME = 'gaa_uid';
    const COOKIE_LIFETIME = 31536000; // 1 year
    const TRANSIENT_PREFIX = 'gaa_rec_';
    const REC_TTL = DAY_IN_SECONDS; // 24h

    /**
     * ユーザー識別子をCookieに設定
     */
    public static function ensure_user_token() {
        if (is_admin()) return;
        if (!isset($_COOKIE[self::COOKIE_NAME]) || empty($_COOKIE[self::COOKIE_NAME])) {
            $uid = wp_generate_password(16, false, false);
            // SameSite=Lax; SecureはHTTPSのみ
            setcookie(self::COOKIE_NAME, $uid, time() + self::COOKIE_LIFETIME, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            $_COOKIE[self::COOKIE_NAME] = $uid; // 現在リクエストでも参照できるように
        }
    }

    public static function get_uid() {
        return isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_text_field($_COOKIE[self::COOKIE_NAME]) : '';
    }

    /**
     * レコメンド保存（AJAX）
     * POST: ids (array|csv), keywords (array|csv), nonce
     */
    public static function ajax_save_recommendations() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'gaa_chat_nonce')) {
            wp_send_json_error('invalid_nonce');
        }

        $uid = self::get_uid();
        if (empty($uid)) {
            self::ensure_user_token();
            $uid = self::get_uid();
        }

        $ids = array();
        if (isset($_POST['ids'])) {
            if (is_array($_POST['ids'])) {
                $ids = array_map('intval', $_POST['ids']);
            } else {
                // CSV文字列
                $ids = array_map('intval', array_filter(array_map('trim', explode(',', (string) $_POST['ids']))));
            }
        }

        $keywords = array();
        if (isset($_POST['keywords'])) {
            if (is_array($_POST['keywords'])) {
                $keywords = array_map('sanitize_text_field', $_POST['keywords']);
            } else {
                $keywords = array_filter(array_map('sanitize_text_field', array_map('trim', explode(',', (string) $_POST['keywords']))));
            }
        }

        if (empty($ids)) {
            wp_send_json_error('no_ids');
        }

        $payload = array(
            'ids' => array_values(array_unique(array_filter($ids))),
            'keywords' => array_values(array_unique(array_filter($keywords))),
            'timestamp' => current_time('timestamp')
        );

        set_transient(self::TRANSIENT_PREFIX . $uid, $payload, self::REC_TTL);

        if (class_exists('GAA_Analytics')) {
            GAA_Analytics::log_event('save_recs', $payload);
        }

        wp_send_json_success(array('ok' => true));
    }

    /**
     * 現在のユーザーのレコメンド取得
     */
    public static function get_current_recommendations() {
        $uid = self::get_uid();
        if (empty($uid)) return null;
        $data = get_transient(self::TRANSIENT_PREFIX . $uid);
        if (!is_array($data) || empty($data['ids'])) return null;
        return $data;
    }

    /**
     * 検索クエリでレコメンドをブースト
     */
    public static function boost_recommendations_in_search($clauses, $query) {
        if (is_admin() || !$query->is_main_query() || !$query->is_search()) return $clauses;
        $recs = self::get_current_recommendations();
        if (!$recs || empty($recs['ids'])) return $clauses;

        global $wpdb;
        $ids = array_map('intval', $recs['ids']);
        $ids = array_filter($ids);
        if (empty($ids)) return $clauses;

        // FIELDで上位に（既存orderbyの先頭に追加）
        $field_list = implode(',', $ids);
        $prefix = "FIELD({$wpdb->posts}.ID, {$field_list}) DESC";
        if (!empty($clauses['orderby'])) {
            $clauses['orderby'] = $prefix . ', ' . $clauses['orderby'];
        } else {
            $clauses['orderby'] = $prefix;
        }
        return $clauses;
    }

    /**
     * ショートコード: [grant_ai_related limit="3" title="関連の助成金"]
     */
    public static function shortcode_related($atts) {
        $atts = shortcode_atts(array(
            'limit' => 3,
            'title' => __('関連の助成金', 'grant-ai-assistant')
        ), $atts, 'grant_ai_related');

        $recs = self::get_current_recommendations();
        if (!$recs || empty($recs['ids'])) return '';

        $ids = array_slice($recs['ids'], 0, max(1, intval($atts['limit'])));
        $html_items = array();
        foreach ($ids as $post_id) {
            $post_id = intval($post_id);
            if (!$post_id) continue;
            // テーマのカード関数があれば利用
            if (function_exists('gi_render_card')) {
                $card = gi_render_card($post_id, 'grid');
                if (!empty($card)) {
                    $html_items[] = '<div class="gaa-related-card">' . $card . '</div>';
                    continue;
                }
            }
            // フォールバック簡易表示
            $title = get_the_title($post_id);
            $permalink = get_permalink($post_id);
            $html_items[] = sprintf('<li><a href="%s">%s</a></li>', esc_url($permalink), esc_html($title));
        }

        if (empty($html_items)) return '';

        $inner = implode("\n", $html_items);
        $wrap = '<div class="gaa-related-wrap">';
        if (!empty($atts['title'])) {
            $wrap .= '<h3 class="gaa-related-title">' . esc_html($atts['title']) . '</h3>';
        }
        if (strpos($inner, '<div class="gaa-related-card">') !== false) {
            $wrap .= '<div class="gaa-related-grid">' . $inner . '</div>';
        } else {
            $wrap .= '<ul class="gaa-related-list">' . $inner . '</ul>';
        }
        $wrap .= '</div>';

        return $wrap;
    }
}

// Hook registrations
add_action('init', array('GAA_Recommendations', 'ensure_user_token'));
add_filter('posts_clauses', array('GAA_Recommendations', 'boost_recommendations_in_search'), 10, 2);
add_action('wp_ajax_gaa_save_recs', array('GAA_Recommendations', 'ajax_save_recommendations'));
add_action('wp_ajax_nopriv_gaa_save_recs', array('GAA_Recommendations', 'ajax_save_recommendations'));
add_shortcode('grant_ai_related', array('GAA_Recommendations', 'shortcode_related'));
