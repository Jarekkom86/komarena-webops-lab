<?php

if (!defined('ABSPATH')) {
    exit;
}

final class KomArena_Agent_Core_Security {
    private const CLOCK_SKEW_SECONDS = 300;
    private const NONCE_TTL_SECONDS = 600;

    public function verify_request(WP_REST_Request $request) {
        $site_id = trim((string) $request->get_header('x-komarena-site'));
        $timestamp = trim((string) $request->get_header('x-komarena-timestamp'));
        $nonce = trim((string) $request->get_header('x-komarena-nonce'));
        $signature = strtolower(trim((string) $request->get_header('x-komarena-signature')));

        if ($site_id === '' || $timestamp === '' || $nonce === '' || $signature === '') {
            return new WP_Error('komarena_auth_missing', 'Missing KomArena authentication headers.', ['status' => 401]);
        }

        $expected_site_id = (string) get_option('komarena_agent_core_site_id', '');
        if ($expected_site_id === '' || !hash_equals($expected_site_id, $site_id)) {
            return new WP_Error('komarena_auth_site', 'Unknown site identity.', ['status' => 401]);
        }

        if (!ctype_digit($timestamp)) {
            return new WP_Error('komarena_auth_timestamp', 'Invalid timestamp.', ['status' => 401]);
        }

        $request_time = (int) $timestamp;
        if (abs(time() - $request_time) > self::CLOCK_SKEW_SECONDS) {
            return new WP_Error('komarena_auth_expired', 'Request timestamp is outside the allowed window.', ['status' => 401]);
        }

        if (!preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $nonce)) {
            return new WP_Error('komarena_auth_nonce', 'Invalid nonce.', ['status' => 401]);
        }

        $secret = (string) get_option('komarena_agent_core_secret', '');
        if (strlen($secret) < 32) {
            return new WP_Error('komarena_auth_unpaired', 'Agent Core is not securely paired.', ['status' => 503]);
        }

        $canonical = implode("\n", [
            $timestamp,
            $nonce,
            strtoupper($request->get_method()),
            $request->get_route(),
            $request->get_body(),
        ]);
        $expected_signature = hash_hmac('sha256', $canonical, $secret);

        if (!preg_match('/^[a-f0-9]{64}$/', $signature) || !hash_equals($expected_signature, $signature)) {
            return new WP_Error('komarena_auth_signature', 'Invalid request signature.', ['status' => 401]);
        }

        $nonce_key = 'kan_nonce_' . hash('sha256', $site_id . '|' . $nonce);
        if (get_transient($nonce_key) !== false) {
            return new WP_Error('komarena_auth_replay', 'Replay detected.', ['status' => 409]);
        }

        set_transient($nonce_key, '1', self::NONCE_TTL_SECONDS);
        return true;
    }
}
