<?php
/**
 * Plugin Name: KomArena UI System
 * Plugin URI: https://komarena.sk/
 * Description: Unified frontend visual system for KomArena.sk (header, sidebar, WooCommerce listings, product pages, and responsive polish).
 * Version: 1.1.1
 * Author: KomArena.sk + Assistant
 * License: GPL-2.0-or-later
 * Text Domain: komarena-ui-system
 */

if (!defined('ABSPATH')) {
    exit;
}

final class KomArena_Ui_System {
    const VERSION = '1.1.1';
    const PORTFOLIO_TEMPLATE = 'templates/page-komarena-portfolio.php';
    const HANDLE_STYLE = 'komarena-ui-system-style';
    const HANDLE_POLISH_STYLE = 'komarena-ui-system-polish-style';

    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_filter('theme_page_templates', array(__CLASS__, 'register_page_templates'));
        add_filter('template_include', array(__CLASS__, 'load_page_template'), 999);
    }

    public static function is_portfolio_template() {
        return is_page() && self::PORTFOLIO_TEMPLATE === get_page_template_slug(get_queried_object_id());
    }

    public static function register_page_templates($templates) {
        $templates[self::PORTFOLIO_TEMPLATE] = 'KomArena – Portfólio';
        return $templates;
    }

    public static function load_page_template($template) {
        if (self::is_portfolio_template()) {
            $portfolio_template = plugin_dir_path(__FILE__) . 'templates/page-komarena-portfolio.php';
            if (file_exists($portfolio_template)) {
                return $portfolio_template;
            }
        }
        return $template;
    }

    public static function enqueue_assets() {
        if (is_admin()) {
            return;
        }

        $css_file = plugin_dir_path(__FILE__) . 'assets/css/komarena-unified-ui.css';
        $css_url  = plugin_dir_url(__FILE__) . 'assets/css/komarena-unified-ui.css';
        $version  = file_exists($css_file) ? (string) filemtime($css_file) : self::VERSION;

        wp_enqueue_style(self::HANDLE_STYLE, $css_url, array(), $version, 'all');

        $polish_css_file = plugin_dir_path(__FILE__) . 'assets/css/komarena-web-polish.css';
        $polish_css_url  = plugin_dir_url(__FILE__) . 'assets/css/komarena-web-polish.css';
        $polish_version  = file_exists($polish_css_file) ? (string) filemtime($polish_css_file) : self::VERSION;

        if (file_exists($polish_css_file)) {
            wp_enqueue_style(self::HANDLE_POLISH_STYLE, $polish_css_url, array(self::HANDLE_STYLE), $polish_version, 'all');
        }

        if (self::is_portfolio_template()) {
            $portfolio_css_file = plugin_dir_path(__FILE__) . 'assets/css/komarena-portfolio.css';
            $portfolio_css_url  = plugin_dir_url(__FILE__) . 'assets/css/komarena-portfolio.css';
            wp_enqueue_style('komarena-portfolio', $portfolio_css_url, array(self::HANDLE_POLISH_STYLE), (string) filemtime($portfolio_css_file), 'all');
        }
    }
}

KomArena_Ui_System::init();
