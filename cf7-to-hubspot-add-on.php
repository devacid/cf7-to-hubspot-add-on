<?php
/*
Plugin Name: CF7 to HubSpot Add-on
Plugin URI: https://github.com/devacid/cf7-to-hubspot-add-on
Description: This plugin enables HubSpot forms integration with Contact Form 7 forms. In order for this plugin to work <a href="http://php.net/manual/en/book.curl.php" target="_blank">cURL for PHP</a> should be enabled. This is a fork from <a href="https://wordpress.org/plugins/cf7-hubspot-forms-add-on-for-contact-form-7/">CF7 HubSpot Forms Add-on For Contact Form 7</a> plugin
Author: devAcid
Version: 1.0.2
Author URI: https://devacid.xyz/

PREFIX: cf7tohs (CF7 to HubSpot Add-on)

*/

// check to make sure contact form 7 is installed and active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

//plugin-update-checker
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/devacid/cf7-to-hubspot-add-on/',
	__FILE__,
	'cf7-to-hubspot-add-on'
);

//Set the branch that contains the stable release.
//$myUpdateChecker->setBranch('main');

//Set update by releases
$myUpdateChecker->getVcsApi()->enableReleaseAssets();


//Optional: If you're using a private repository, specify the access token like this:
$myUpdateChecker->setAuthentication('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');

//chech WP_DEBUG state
$wpDebug = false;
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    $wpDebug = true;
}

if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {

	function cf7tohs_root_url( $append = false ) {

		$base_url = plugin_dir_url( __FILE__ );

		return ($append ? $base_url . $append : $base_url);

	}
	
	function cf7tohs_root_dir( $append = false ) {

		$base_dir = plugin_dir_path( __FILE__ );

		return ($append ? $base_dir . $append : $base_dir);

	}

	include_once( cf7tohs_root_dir('inc/constants.php') );

	function cf7tohs_enqueue( $hook ) {

    if ( !strpos( $hook, 'wpcf7' ) )
    	return;

    wp_enqueue_style( 'cf7tohs-styles',
    	cf7tohs_root_url('assets/css/styles.css'),
    	false,
    	cf7tohs_VERSION );

		wp_enqueue_script( 'cf7tohs-scripts',
    	cf7tohs_root_url('assets/js/scripts.js'),
			array('jquery'),
			cf7tohs_VERSION );

	}
	add_action( 'admin_enqueue_scripts', 'cf7tohs_enqueue' );

	function cf7tohs_admin_panel ( $panels ) {

		$new_page = array(
			'hubspot-forms-integration-addon' => array(
				'title' => __( 'HubSpot Form Integration', 'contact-form-7' ),
				'callback' => 'cf7tohs_admin_panel_content'
			)
		);
		
		$panels = array_merge($panels, $new_page);
		
		return $panels;
		
	}
	add_filter( 'wpcf7_editor_panels', 'cf7tohs_admin_panel' );

	function cf7tohs_admin_panel_content( $cf7 ) {
		
		$post_id = sanitize_text_field($_GET['post']);

		$enabled = get_post_meta($post_id, "_cf7tohs_enabled", true);
		$portal_id = get_post_meta($post_id, "_cf7tohs_portal_id", true);
		$form_id = get_post_meta($post_id, "_cf7tohs_form_id", true);
		$form_page_url = get_post_meta($post_id, "_cf7tohs_form_page_url", true);
		$form_page_name = get_post_meta($post_id, "_cf7tohs_form_page_name", true);
		$form_fields_str = get_post_meta($post_id, "_cf7tohs_form_fields", true);
		$form_fields = $form_fields_str ? unserialize($form_fields_str) : false;
		$debug_log = get_post_meta($post_id, "_cf7tohs_debug_log", true);

		$template = cf7tohs_get_view_template('form-fields.tpl.php');

		if($form_fields) {

			$form_fields_html = '';
			$count = 1;

			foreach ($form_fields as $key => $value) {

				$search_replace = array(
					'{first_field}' => ' first_field',
					'{field_name}' => $key,
					'{field_value}' => $value,
					'{add_button}' => '<a href="#" class="button add_field">Add Another Field</a>',
					'{remove_button}' => '<a href="#" class="button remove_field">Remove Field</a>',
				);

				$search = array_keys($search_replace);
				$replace = array_values($search_replace);

				if($count >  1) $replace[0] = $replace[3] = '';				
				if($count == 1) $replace[4] = '';

				$form_fields_html .= str_replace($search, $replace, $template);

				$count++;

			}

		} else {

			$search_replace = array(
				'{first_field}' => ' first_field',
				'{field_name}' => '',
				'{field_value}' => '',
				'{add_button}' => '<a href="#" class="button add_field">Add Another Field</a>',
				'{remove_button}' => '',
			);

			$search = array_keys($search_replace);
			$replace = array_values($search_replace);

			$form_fields_html = str_replace($search, $replace, $template);

		}

		$debug_log = unserialize($debug_log);
		$debug_log_str = is_array($debug_log) ? print_r($debug_log, true) : $debug_log;

		$search_replace = array(
			'{enabled}' => ($enabled == 1 ? ' checked' : ''),
			'{portal_id}' => $portal_id,
			'{form_id}' => $form_id,
			'{form_page_url}' => $form_page_url,
			'{form_page_name}' => $form_page_name,
			'{form_fields_html}' => $form_fields_html,
			'{debug_log}' => $debug_log_str,
		);

		$search = array_keys($search_replace);
		$replace = array_values($search_replace);

		$template = cf7tohs_get_view_template('ui-tabs-panel.tpl.php');

		$admin_table_output = str_replace($search, $replace, $template);

		echo $admin_table_output;

	}

	function cf7tohs_get_view_template( $template_name ) {

		$template_content = false;
		$template_path = cf7tohs_VIEWS_DIR . $template_name;

		if( file_exists($template_path) ) {

			$search_replace = array(
				"<?php if(!defined( 'ABSPATH')) exit; ?>" => '',
				"{plugin_url}" => cf7tohs_root_url(),
				"{site_url}" => get_site_url(),
			);

			$search = array_keys($search_replace);
			$replace = array_values($search_replace);

			$template_content = str_replace($search, $replace, file_get_contents( $template_path ));

		}

		return $template_content;

	}

	function cf7tohs_admin_save_form( $cf7 ) {
		
		$post_id = sanitize_text_field($_GET['post']);

		$form_fields = array();

		foreach ($_POST['cf7tohs_hs_field'] as $key => $value) {

			if($_POST['cf7tohs_cf7_field'][$key] == '' && $value == '') continue;

			$form_fields[$value] = $_POST['cf7tohs_cf7_field'][$key];

		}

		update_post_meta($post_id, '_cf7tohs_enabled', $_POST['cf7tohs_enabled']);
		update_post_meta($post_id, '_cf7tohs_portal_id', $_POST['cf7tohs_portal_id']);
		update_post_meta($post_id, '_cf7tohs_form_id', $_POST['cf7tohs_form_id']);
		update_post_meta($post_id, '_cf7tohs_form_page_url', $_POST['cf7tohs_form_page_url']);
		update_post_meta($post_id, '_cf7tohs_form_page_name', $_POST['cf7tohs_form_page_name']);
		update_post_meta($post_id, '_cf7tohs_form_fields', serialize($form_fields));

	}
	add_action('wpcf7_save_contact_form', 'cf7tohs_admin_save_form');

	function cf7tohs_frontend_submit_form( $wpcf7_data ) {

        if ( $wpDebug ) {
            $post_id = $wpcf7_data->id();
        } else {
            $post_id = $wpcf7_data->id;
        }
		$enabled = get_post_meta($post_id, "_cf7tohs_enabled", true);
		$portal_id = get_post_meta($post_id, "_cf7tohs_portal_id", true);
		$form_id = get_post_meta($post_id, "_cf7tohs_form_id", true);
		$form_page_url = get_post_meta($post_id, "_cf7tohs_form_page_url", true);
		$form_page_name = get_post_meta($post_id, "_cf7tohs_form_page_name", true);
		$form_fields_str = get_post_meta($post_id, "_cf7tohs_form_fields", true);
		$form_fields = $form_fields_str ? unserialize($form_fields_str) : false;

    if( $enabled == 1 && $form_fields ) {
            if ( $wpDebug ) {
                $user_ip = $_SERVER["REMOTE_ADDR"];
                $hs_context = array(
                    "ipAddress" => $user_ip
                );
            } else {
                $hs_cookie = $_COOKIE["hubspotutk"];
                $user_ip = $_SERVER["REMOTE_ADDR"];
                $hs_context = array(
                    "hutk" => $hs_cookie,
                    "ipAddress" => $user_ip
                );
            }


			if( !empty($form_page_url) ) $hs_context["pageUrl"] = $form_page_url;
			if( !empty($form_page_name) ) $hs_context["pageName"] = $form_page_name;

			$hs_context_json = json_encode($hs_context);

			$post_array = array();

			foreach ($form_fields as $key => $value) {

				$search = array("[", "]");
				$post_key = str_replace($search, "", $value);

				$post_array[$key] = $_POST[$post_key];

			}

			$post_array["hs_context"] = $hs_context_json;
			$post_string = http_build_query($post_array);

			$post_url = "https://forms.hubspot.com/uploads/form/v2/{$portal_id}/{$form_id}";

			$cURL = @curl_init();
			@curl_setopt($cURL, CURLOPT_POST, true);
			@curl_setopt($cURL, CURLOPT_POSTFIELDS, $post_string);
			@curl_setopt($cURL, CURLOPT_URL, $post_url);
			@curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/x-www-form-urlencoded'
			));
			@curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);

			$response = @curl_exec($cURL);
			$status_code = @curl_getinfo($cURL, CURLINFO_HTTP_CODE);

			@curl_close($cURL);

			$debug_log = array(
				'STATUS_CODE' => $status_code,
				'HUBSPOT_RESPONSE' => $response
			);

			update_post_meta($post_id, '_cf7tohs_debug_log', serialize($debug_log));

    }

	}
	add_action("wpcf7_before_send_mail", "cf7tohs_frontend_submit_form");

}
