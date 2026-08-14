<?php

if (!defined('ABSPATH')) {
    exit;
}

final class KomArena_Agent_Core_REST {
    private KomArena_Agent_Core_Security $security;
    private KomArena_Agent_Core_Actions $actions;

    public function __construct(KomArena_Agent_Core_Security $security, KomArena_Agent_Core_Actions $actions) {
        $this->security = $security;
        $this->actions = $actions;
    }

    public function register_hooks(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('komarena-agent/v1', '/status', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'status'],
            'permission_callback' => [$this->security, 'verify_request'],
        ]);

        register_rest_route('komarena-agent/v1', '/capabilities', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'capabilities'],
            'permission_callback' => [$this->security, 'verify_request'],
        ]);

        register_rest_route('komarena-agent/v1', '/tasks', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'tasks'],
            'permission_callback' => [$this->security, 'verify_request'],
        ]);
    }

    public function status(WP_REST_Request $request): WP_REST_Response {
        unset($request);
        return new WP_REST_Response([
            'ok' => true,
            'site_id' => (string) get_option('komarena_agent_core_site_id', ''),
            'core_version' => KOMARENA_AGENT_CORE_VERSION,
            'autonomy_mode' => (string) get_option('komarena_agent_core_autonomy_mode', 'audit_only'),
            'timestamp' => gmdate('c'),
        ]);
    }

    public function capabilities(WP_REST_Request $request): WP_REST_Response {
        unset($request);
        return new WP_REST_Response($this->actions->capabilities());
    }

    public function tasks(WP_REST_Request $request) {
        $body = $request->get_json_params();
        if (!is_array($body)) {
            return new WP_Error('komarena_json_required', 'JSON request body is required.', ['status' => 400]);
        }

        $result = $this->actions->execute($body);
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }
}
