<?php
/**
 * Plugin Name: KomArena Agent Core
 * Plugin URI: https://komarena.sk/
 * Description: Secure execution bridge between KomArena Nexus and WordPress. Provides signed REST tasks, capabilities, idempotency, resource snapshots and rollback for explicitly allowed actions.
 * Version: 0.1.0
 * Author: KomArena
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: komarena-agent-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KOMARENA_AGENT_CORE_VERSION', '0.1.0');
define('KOMARENA_AGENT_CORE_FILE', __FILE__);
define('KOMARENA_AGENT_CORE_DIR', plugin_dir_path(__FILE__));

require_once KOMARENA_AGENT_CORE_DIR . 'includes/class-komarena-agent-core-security.php';
require_once KOMARENA_AGENT_CORE_DIR . 'includes/class-komarena-agent-core-actions.php';
require_once KOMARENA_AGENT_CORE_DIR . 'includes/class-komarena-agent-core-rest.php';

final class KomArena_Agent_Core {
    private const OPTION_SITE_ID = 'komarena_agent_core_site_id';
    private const OPTION_SECRET = 'komarena_agent_core_secret';
    private const OPTION_AUTONOMY = 'komarena_agent_core_autonomy_mode';

    public static function activate(): void {
        if (!get_option(self::OPTION_SITE_ID)) {
            add_option(self::OPTION_SITE_ID, wp_generate_uuid4(), '', false);
        }

        if (!get_option(self::OPTION_SECRET)) {
            add_option(self::OPTION_SECRET, wp_generate_password(64, false, false), '', false);
        }

        if (!get_option(self::OPTION_AUTONOMY)) {
            add_option(self::OPTION_AUTONOMY, 'audit_only', '', false);
        }
    }

    public static function init(): void {
        $security = new KomArena_Agent_Core_Security();
        $actions = new KomArena_Agent_Core_Actions();
        $rest = new KomArena_Agent_Core_REST($security, $actions);
        $rest->register_hooks();

        if (is_admin()) {
            add_action('admin_menu', [self::class, 'register_admin_page']);
            add_action('admin_post_komarena_agent_core_save', [self::class, 'save_settings']);
            add_action('admin_post_komarena_agent_core_rotate_secret', [self::class, 'rotate_secret']);
        }
    }

    public static function register_admin_page(): void {
        add_options_page(
            'KomArena Agent Core',
            'KomArena Agent Core',
            'manage_options',
            'komarena-agent-core',
            [self::class, 'render_admin_page']
        );
    }

    public static function save_settings(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'komarena-agent-core'));
        }

        check_admin_referer('komarena_agent_core_save');

        $allowed = ['audit_only', 'assisted', 'safe_auto', 'full_auto'];
        $mode = isset($_POST['autonomy_mode']) ? sanitize_key(wp_unslash($_POST['autonomy_mode'])) : 'audit_only';
        if (!in_array($mode, $allowed, true)) {
            $mode = 'audit_only';
        }

        update_option(self::OPTION_AUTONOMY, $mode, false);
        wp_safe_redirect(add_query_arg(['page' => 'komarena-agent-core', 'updated' => '1'], admin_url('options-general.php')));
        exit;
    }

    public static function rotate_secret(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'komarena-agent-core'));
        }

        check_admin_referer('komarena_agent_core_rotate_secret');
        update_option(self::OPTION_SECRET, wp_generate_password(64, false, false), false);
        wp_safe_redirect(add_query_arg(['page' => 'komarena-agent-core', 'rotated' => '1'], admin_url('options-general.php')));
        exit;
    }

    public static function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $site_id = (string) get_option(self::OPTION_SITE_ID, '');
        $secret = (string) get_option(self::OPTION_SECRET, '');
        $mode = (string) get_option(self::OPTION_AUTONOMY, 'audit_only');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('KomArena Agent Core', 'komarena-agent-core'); ?></h1>
            <p><?php echo esc_html__('Secure bridge for KomArena Nexus. Keep the pairing secret private.', 'komarena-agent-core'); ?></p>

            <table class="widefat striped" style="max-width: 920px">
                <tbody>
                <tr>
                    <th scope="row"><?php echo esc_html__('Core version', 'komarena-agent-core'); ?></th>
                    <td><code><?php echo esc_html(KOMARENA_AGENT_CORE_VERSION); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Site ID', 'komarena-agent-core'); ?></th>
                    <td><code><?php echo esc_html($site_id); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Pairing secret', 'komarena-agent-core'); ?></th>
                    <td><code style="word-break: break-all"><?php echo esc_html($secret); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('REST namespace', 'komarena-agent-core'); ?></th>
                    <td><code><?php echo esc_html(rest_url('komarena-agent/v1/')); ?></code></td>
                </tr>
                </tbody>
            </table>

            <h2><?php echo esc_html__('Autonomy mode', 'komarena-agent-core'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="komarena_agent_core_save">
                <?php wp_nonce_field('komarena_agent_core_save'); ?>
                <select name="autonomy_mode">
                    <?php
                    $modes = [
                        'audit_only' => 'Audit Only',
                        'assisted' => 'Assisted Repair',
                        'safe_auto' => 'Safe Auto Repair',
                        'full_auto' => 'Full Auto',
                    ];
                    foreach ($modes as $value => $label) {
                        printf(
                            '<option value="%1$s" %2$s>%3$s</option>',
                            esc_attr($value),
                            selected($mode, $value, false),
                            esc_html($label)
                        );
                    }
                    ?>
                </select>
                <?php submit_button(__('Save mode', 'komarena-agent-core'), 'primary', 'submit', false); ?>
            </form>

            <h2><?php echo esc_html__('Security', 'komarena-agent-core'); ?></h2>
            <p><?php echo esc_html__('Rotating the secret immediately invalidates the old Nexus pairing.', 'komarena-agent-core'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="komarena_agent_core_rotate_secret">
                <?php wp_nonce_field('komarena_agent_core_rotate_secret'); ?>
                <?php submit_button(__('Rotate pairing secret', 'komarena-agent-core'), 'secondary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }
}

register_activation_hook(__FILE__, [KomArena_Agent_Core::class, 'activate']);
add_action('plugins_loaded', [KomArena_Agent_Core::class, 'init']);
