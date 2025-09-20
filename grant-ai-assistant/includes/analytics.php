<?php
/**
 * Grant AI Assistant - Analytics & Logging
 * 軽量なイベントログと日次集計を提供
 *
 * @package Grant_AI_Assistant
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GAA_Analytics {
    const DB_VERSION = '1.0.0';

    /**
     * 必要なDBテーブルを作成（存在しなければ）
     */
    public static function maybe_create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $events_table = $wpdb->prefix . 'gaa_events';
        $daily_table  = $wpdb->prefix . 'gaa_daily_metrics';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_events = "CREATE TABLE {$events_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(32) NOT NULL,
            user_id BIGINT UNSIGNED DEFAULT 0,
            ip VARCHAR(45) DEFAULT NULL,
            payload LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_daily = "CREATE TABLE {$daily_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            metric_date DATE NOT NULL,
            metric_key VARCHAR(64) NOT NULL,
            metric_value BIGINT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_date_key (metric_date, metric_key)
        ) {$charset_collate};";

        dbDelta($sql_events);
        dbDelta($sql_daily);

        update_option('gaa_analytics_db_version', self::DB_VERSION);
    }

    /**
     * イベントを1件記録
     */
    public static function log_event($event_type, $payload = array()) {
        global $wpdb;
        $events_table = $wpdb->prefix . 'gaa_events';

        $data = array(
            'event_type' => sanitize_text_field($event_type),
            'user_id'    => get_current_user_id(),
            'ip'         => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null,
            'payload'    => wp_json_encode($payload),
            'created_at' => current_time('mysql')
        );

        // テーブルが無い場合は作成を試みる
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $events_table)) !== $events_table) {
            self::maybe_create_tables();
        }

        $wpdb->insert($events_table, $data, array('%s','%d','%s','%s','%s'));
    }

    /**
     * 日次メトリクス加算
     */
    public static function increment_daily_metric($metric_key, $by = 1) {
        global $wpdb;
        $daily_table = $wpdb->prefix . 'gaa_daily_metrics';

        $date = current_time('Y-m-d');
        $metric_key = sanitize_key($metric_key);

        // テーブルが無い場合は作成を試みる
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $daily_table)) !== $daily_table) {
            self::maybe_create_tables();
        }

        // 既存なら加算、無ければ挿入
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, metric_value FROM {$daily_table} WHERE metric_date=%s AND metric_key=%s",
            $date, $metric_key
        ));

        if ($existing) {
            $wpdb->update(
                $daily_table,
                array('metric_value' => intval($existing->metric_value) + intval($by)),
                array('id' => intval($existing->id)),
                array('%d'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $daily_table,
                array(
                    'metric_date' => $date,
                    'metric_key' => $metric_key,
                    'metric_value' => intval($by)
                ),
                array('%s','%s','%d')
            );
        }
    }

    /**
     * 簡易サマリーを取得（直近7日）
     */
    public static function get_summary($days = 7) {
        global $wpdb;
        $daily_table = $wpdb->prefix . 'gaa_daily_metrics';

        // 無ければ空
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $daily_table)) !== $daily_table) {
            return array(
                'totals' => array('chats' => 0, 'clicks' => 0, 'errors' => 0),
                'series' => array()
            );
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT metric_date, metric_key, metric_value
             FROM {$daily_table}
             WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
            intval($days)
        ), ARRAY_A);

        $totals = array('chats' => 0, 'clicks' => 0, 'errors' => 0);
        $series = array();

        foreach ($rows as $r) {
            $date = $r['metric_date'];
            $key  = $r['metric_key'];
            $val  = intval($r['metric_value']);
            if (!isset($series[$date])) $series[$date] = array('chats' => 0, 'clicks' => 0, 'errors' => 0);
            if (isset($series[$date][$key])) {
                $series[$date][$key] += $val;
            }
            if (isset($totals[$key])) {
                $totals[$key] += $val;
            }
        }

        ksort($series);
        return array('totals' => $totals, 'series' => $series);
    }

    /**
     * 助成金カードのクリックをAJAXで記録
     */
    public static function handle_click_ajax() {
        // nonce検証
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'gaa_chat_nonce')) {
            wp_send_json_error('invalid_nonce');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $score   = isset($_POST['score']) ? floatval($_POST['score']) : 0;
        $query   = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

        if ($post_id <= 0) {
            wp_send_json_error('invalid_post_id');
        }

        self::log_event('click', array(
            'post_id' => $post_id,
            'score'   => $score,
            'query'   => $query,
        ));
        self::increment_daily_metric('clicks', 1);

        wp_send_json_success(array('ok' => true));
    }
}
