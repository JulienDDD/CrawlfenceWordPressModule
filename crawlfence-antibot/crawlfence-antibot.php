<?php
/**
 * Plugin Name: CrawlFence Anti-Bot
 * Description: Intègre l'API CrawlFence pour protéger votre site contre les bots malveillants.
 * Version: 1.0
 * Author: Julien DD
 * License: GPL2
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche l'accès direct
}

// Inclure la classe CrawlFenceAPI
require_once plugin_dir_path( __FILE__ ) . 'includes/class-crawlfence-api.php';

// Ajouter une action pour vérifier l'accès avant le chargement du site
add_action( 'init', 'crawlfence_check_access' );

function crawlfence_check_access() {
    // Récupérer la clé API depuis les options
    $api_key = get_option( 'crawlfence_api_key', '' );

    if ( empty( $api_key ) ) {
        // La clé API n'est pas définie, ne rien faire
        return;
    }

    try {
        $crawlFence = new CrawlFenceAPI( $api_key );
        $crawlFence->handle_request();
    } catch ( Exception $e ) {
        // Gérer les exceptions en enregistrant l'erreur
        error_log( 'CrawlFence Error: ' . $e->getMessage() );
    }
}


add_action( 'admin_menu', 'crawlfence_add_admin_menu' );
add_action( 'admin_init', 'crawlfence_settings_init' );

function crawlfence_add_admin_menu() {
    add_options_page(
        'CrawlFence Anti-Bot',
        'CrawlFence Anti-Bot',
        'manage_options',
        'crawlfence_antibot',
        'crawlfence_options_page'
    );
}

function crawlfence_settings_init() {
    register_setting( 'crawlfenceSettings', 'crawlfence_api_key' );

    add_settings_section(
        'crawlfence_section',
        __( 'Paramètres CrawlFence', 'wordpress' ),
        'crawlfence_settings_section_callback',
        'crawlfenceSettings'
    );

    add_settings_field(
        'crawlfence_api_key',
        __( 'Clé API', 'wordpress' ),
        'crawlfence_api_key_render',
        'crawlfenceSettings',
        'crawlfence_section'
    );
}

function crawlfence_api_key_render() {
    $api_key = get_option( 'crawlfence_api_key', '' );
    ?>
    <input type='text' name='crawlfence_api_key' value='<?php echo esc_attr( $api_key ); ?>' size="50">
    <?php
}

function crawlfence_settings_section_callback() {
    echo __( 'Entrez votre clé API CrawlFence pour activer la protection.', 'wordpress' );
}

function crawlfence_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h1>CrawlFence Anti-Bot</h1>
        <?php
        settings_fields( 'crawlfenceSettings' );
        do_settings_sections( 'crawlfenceSettings' );
        submit_button();
        ?>
    </form>
    <?php
}
