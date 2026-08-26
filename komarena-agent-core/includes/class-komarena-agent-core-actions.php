<?php

if (!defined('ABSPATH')) {
    exit;
}

final class KomArena_Agent_Core_Actions {
    private const TASK_CACHE_TTL = DAY_IN_SECONDS;
    private const MAX_ROLLBACK_RECORDS = 50;

    public function capabilities(): array {
        return [
            'protocol' => 'komarena-agent/v1',
            'core_version' => KOMARENA_AGENT_CORE_VERSION,
            'autonomy_mode' => (string) get_option('komarena_agent_core_autonomy_mode', 'audit_only'),
            'actions' => [
                'site.inspect' => ['risk' => 'read_only', 'write' => false],
                'health.check' => ['risk' => 'read_only', 'write' => false],
                'plugin.list' => ['risk' => 'read_only', 'write' => false],
                'post.read' => ['risk' => 'read_only', 'write' => false],
                'post.update' => ['risk' => 'low', 'write' => true, 'backup' => 'resource_snapshot'],
                'cache.purge' => ['risk' => 'low', 'write' => true],
                'rollback.execute' => ['risk' => 'low', 'write' => true],
            ],
            'module_installation' => false,
            'arbitrary_code_execution' => false,
        ];
    }

    public function execute(array $task) {
        $task_id = isset($task['task_id']) ? sanitize_text_field((string) $task['task_id']) : '';
        $idempotency_key = isset($task['idempotency_key']) ? sanitize_text_field((string) $task['idempotency_key']) : '';
        $action = isset($task['action']) ? strtolower(trim((string) $task['action'])) : '';
        if ($action !== '' && !preg_match('/^[a-z0-9.:-]{3,64}$/', $action)) {
            return new WP_Error('komarena_action_format', 'Action name has an invalid format.', ['status' => 400]);
        }
        $payload = isset($task['payload']) && is_array($task['payload']) ? $task['payload'] : [];

        if ($task_id === '' || $idempotency_key === '' || $action === '') {
            return new WP_Error('komarena_task_invalid', 'task_id, idempotency_key and action are required.', ['status' => 400]);
        }

        $cache_key = 'kan_task_' . hash('sha256', $idempotency_key);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $cached['idempotent_replay'] = true;
            return $cached;
        }

        $started = gmdate('c');
        $result = $this->dispatch($action, $payload, $task);
        if (is_wp_error($result)) {
            return $result;
        }

        $response = [
            'task_id' => $task_id,
            'idempotency_key' => $idempotency_key,
            'action' => $action,
            'status' => 'completed',
            'started_at' => $started,
            'finished_at' => gmdate('c'),
            'idempotent_replay' => false,
            'result' => $result,
        ];

        set_transient($cache_key, $response, self::TASK_CACHE_TTL);
        return $response;
    }

    private function dispatch(string $action, array $payload, array $task) {
        switch ($action) {
            case 'site.inspect':
                return $this->site_inspect();
            case 'health.check':
                return $this->health_check();
            case 'plugin.list':
                return $this->plugin_list();
            case 'post.read':
                return $this->post_read($payload);
            case 'post.update':
                return $this->post_update($payload, $task);
            case 'cache.purge':
                return $this->cache_purge();
            case 'rollback.execute':
                return $this->rollback_execute($payload);
            default:
                return new WP_Error('komarena_action_unknown', 'Action is not allowed by this Core version.', ['status' => 400]);
        }
    }

    private function site_inspect(): array {
        $theme = wp_get_theme();
        return [
            'site_id' => (string) get_option('komarena_agent_core_site_id', ''),
            'home_url' => home_url('/'),
            'site_url' => site_url('/'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'multisite' => is_multisite(),
            'theme' => [
                'name' => $theme->get('Name'),
                'version' => $theme->get('Version'),
                'stylesheet' => $theme->get_stylesheet(),
                'template' => $theme->get_template(),
            ],
            'core_version' => KOMARENA_AGENT_CORE_VERSION,
            'autonomy_mode' => (string) get_option('komarena_agent_core_autonomy_mode', 'audit_only'),
        ];
    }

    private function health_check(): array {
        global $wpdb;
        $db_ok = $wpdb->get_var('SELECT 1') === '1';

        return [
            'ok' => $db_ok,
            'database' => $db_ok,
            'rest_url' => rest_url(),
            'cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'memory_limit' => WP_MEMORY_LIMIT,
            'timestamp' => gmdate('c'),
        ];
    }

    private function plugin_list(): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = [];
        foreach (get_plugins() as $file => $data) {
            $plugins[] = [
                'file' => $file,
                'name' => $data['Name'] ?? '',
                'version' => $data['Version'] ?? '',
                'active' => is_plugin_active($file),
            ];
        }

        return ['plugins' => $plugins];
    }

    private function post_read(array $payload) {
        $post_id = isset($payload['post_id']) ? absint($payload['post_id']) : 0;
        if ($post_id <= 0) {
            return new WP_Error('komarena_post_id', 'Valid post_id is required.', ['status' => 400]);
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('komarena_post_missing', 'Post not found.', ['status' => 404]);
        }

        return $this->post_snapshot($post);
    }

    private function post_update(array $payload, array $task) {
        $mode = (string) get_option('komarena_agent_core_autonomy_mode', 'audit_only');
        if ($mode === 'audit_only') {
            return new WP_Error('komarena_write_disabled', 'Write actions are disabled in Audit Only mode.', ['status' => 403]);
        }

        $post_id = isset($payload['post_id']) ? absint($payload['post_id']) : 0;
        if ($post_id <= 0) {
            return new WP_Error('komarena_post_id', 'Valid post_id is required.', ['status' => 400]);
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('komarena_post_missing', 'Post not found.', ['status' => 404]);
        }

        if (!in_array($post->post_type, ['post', 'page', 'product'], true)) {
            return new WP_Error('komarena_post_type', 'This post type is not allowed in Core v0.1.', ['status' => 403]);
        }

        $before = $this->post_snapshot($post);
        $expected_hash = isset($task['expected_before_sha256']) ? strtolower((string) $task['expected_before_sha256']) : '';
        $actual_hash = hash('sha256', wp_json_encode($before, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if ($expected_hash !== '' && !hash_equals($actual_hash, $expected_hash)) {
            return new WP_Error('komarena_precondition_failed', 'Resource changed since the repair plan was created.', [
                'status' => 409,
                'actual_before_sha256' => $actual_hash,
            ]);
        }

        $update = ['ID' => $post_id];
        $changed_fields = [];
        foreach (['post_title', 'post_content', 'post_excerpt'] as $field) {
            if (array_key_exists($field, $payload)) {
                $update[$field] = wp_kses_post((string) $payload[$field]);
                $changed_fields[] = $field;
            }
        }

        if ($changed_fields === []) {
            return new WP_Error('komarena_post_no_changes', 'No allowed post fields were supplied.', ['status' => 400]);
        }

        $rollback_id = wp_generate_uuid4();
        $this->store_rollback($rollback_id, [
            'type' => 'post_snapshot',
            'post_id' => $post_id,
            'snapshot' => $before,
            'created_at' => gmdate('c'),
        ]);

        $updated_id = wp_update_post(wp_slash($update), true);
        if (is_wp_error($updated_id)) {
            return $updated_id;
        }

        clean_post_cache($post_id);
        $after_post = get_post($post_id);
        $after = $this->post_snapshot($after_post);

        return [
            'post_id' => $post_id,
            'changed_fields' => $changed_fields,
            'before_sha256' => $actual_hash,
            'after_sha256' => hash('sha256', wp_json_encode($after, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'rollback_id' => $rollback_id,
            'after' => $after,
        ];
    }

    private function cache_purge(): array {
        $mode = (string) get_option('komarena_agent_core_autonomy_mode', 'audit_only');
        if ($mode === 'audit_only') {
            return new WP_Error('komarena_write_disabled', 'Write actions are disabled in Audit Only mode.', ['status' => 403]);
        }

        $flushed = function_exists('wp_cache_flush') ? (bool) wp_cache_flush() : false;
        do_action('komarena_agent_core_cache_purge');

        return [
            'object_cache_flushed' => $flushed,
            'extension_hook_fired' => true,
        ];
    }

    private function rollback_execute(array $payload) {
        $rollback_id = isset($payload['rollback_id']) ? sanitize_text_field((string) $payload['rollback_id']) : '';
        if ($rollback_id === '') {
            return new WP_Error('komarena_rollback_id', 'rollback_id is required.', ['status' => 400]);
        }

        $records = get_option('komarena_agent_core_rollbacks', []);
        if (!is_array($records) || !isset($records[$rollback_id]) || !is_array($records[$rollback_id])) {
            return new WP_Error('komarena_rollback_missing', 'Rollback record not found.', ['status' => 404]);
        }

        $record = $records[$rollback_id];
        if (($record['type'] ?? '') !== 'post_snapshot') {
            return new WP_Error('komarena_rollback_type', 'Unsupported rollback type.', ['status' => 400]);
        }

        $snapshot = $record['snapshot'] ?? null;
        if (!is_array($snapshot) || empty($record['post_id'])) {
            return new WP_Error('komarena_rollback_invalid', 'Rollback record is invalid.', ['status' => 500]);
        }

        $update = [
            'ID' => absint($record['post_id']),
            'post_title' => (string) ($snapshot['post_title'] ?? ''),
            'post_content' => (string) ($snapshot['post_content'] ?? ''),
            'post_excerpt' => (string) ($snapshot['post_excerpt'] ?? ''),
        ];

        $result = wp_update_post(wp_slash($update), true);
        if (is_wp_error($result)) {
            return $result;
        }

        clean_post_cache((int) $result);
        return [
            'rollback_id' => $rollback_id,
            'post_id' => (int) $result,
            'restored' => true,
            'after' => $this->post_snapshot(get_post((int) $result)),
        ];
    }

    private function post_snapshot($post): array {
        return [
            'ID' => (int) $post->ID,
            'post_type' => (string) $post->post_type,
            'post_status' => (string) $post->post_status,
            'post_modified_gmt' => (string) $post->post_modified_gmt,
            'post_title' => (string) $post->post_title,
            'post_content' => (string) $post->post_content,
            'post_excerpt' => (string) $post->post_excerpt,
        ];
    }

    private function store_rollback(string $rollback_id, array $record): void {
        $records = get_option('komarena_agent_core_rollbacks', []);
        if (!is_array($records)) {
            $records = [];
        }

        $records[$rollback_id] = $record;
        if (count($records) > self::MAX_ROLLBACK_RECORDS) {
            $records = array_slice($records, -self::MAX_ROLLBACK_RECORDS, null, true);
        }

        update_option('komarena_agent_core_rollbacks', $records, false);
    }
}
