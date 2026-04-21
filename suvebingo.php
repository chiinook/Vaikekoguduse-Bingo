<?php
/**
 * Plugin Name: Väikekoguduste Suvebingo
 * Plugin URI:  https://edminsters.com
 * Description: EKB summer church visiting bingo with Vaatlusmäng, game scheduling, church reports and PDF export.
 * Version:     2.0.0
 * Author:      Matt Pinson
 * Text Domain: suvebingo
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

define( 'SUVEBINGO_VERSION', '2.0.0' );
define( 'SUVEBINGO_DIR',     plugin_dir_path( __FILE__ ) );
define( 'SUVEBINGO_URL',     plugin_dir_url( __FILE__ ) );
define( 'SUVEBINGO_DB_VER',  '2' );

require_once SUVEBINGO_DIR . 'includes/database.php';
require_once SUVEBINGO_DIR . 'includes/api.php';
require_once SUVEBINGO_DIR . 'includes/admin.php';
require_once SUVEBINGO_DIR . 'includes/pdf.php';
require_once SUVEBINGO_DIR . 'includes/scraper.php';

register_activation_hook( __FILE__, 'suvebingo_activate' );
function suvebingo_activate() {
    suvebingo_create_tables();
    suvebingo_seed_churches();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'suvebingo_deactivate' );
function suvebingo_deactivate() {
    flush_rewrite_rules();
}

// ── Shortcodes ─────────────────────────────────────────────────
add_shortcode( 'suvebingo',             'suvebingo_render_game' );
add_shortcode( 'suvebingo_leaderboard', 'suvebingo_render_leaderboard' );

function suvebingo_enqueue( $extra = [] ) {
    wp_enqueue_style( 'suvebingo', SUVEBINGO_URL . 'assets/bingo.css', [], SUVEBINGO_VERSION );
    wp_enqueue_script( 'suvebingo', SUVEBINGO_URL . 'assets/bingo.js', [], SUVEBINGO_VERSION, true );

    $game = suvebingo_get_active_game();
    wp_localize_script( 'suvebingo', 'suvebingoConfig', array_merge( [
        'apiBase'    => esc_url_raw( rest_url( 'suvebingo/v1' ) ),
        'nonce'      => wp_create_nonce( 'wp_rest' ),
        'gameId'     => $game ? (int) $game->id        : 0,
        'gameYear'   => $game ? (int) $game->year       : (int) date('Y'),
        'gameName'   => $game ? esc_js( $game->name )   : '',
        'gameStart'  => $game ? esc_js( $game->start_date ) : '',
        'gameEnd'    => $game ? esc_js( $game->end_date )   : '',
        'gameActive' => $game ? true : false,
        'version'    => SUVEBINGO_VERSION,
    ], $extra ) );
}

function suvebingo_render_game() {
    suvebingo_enqueue();
    ob_start();
    include SUVEBINGO_DIR . 'templates/bingo-page.php';
    return ob_get_clean();
}

function suvebingo_render_leaderboard() {
    suvebingo_enqueue();
    ob_start();
    include SUVEBINGO_DIR . 'templates/leaderboard-page.php';
    return ob_get_clean();
}

// ── Active game helper ─────────────────────────────────────────
function suvebingo_get_active_game() {
    global $wpdb;
    return $wpdb->get_row(
        "SELECT * FROM {$wpdb->prefix}suvebingo_games WHERE active = 1 LIMIT 1"
    );
}
