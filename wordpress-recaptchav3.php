<?php

/**
 * Plugin Name:       Wordpress Recaptchav3
 * Description:       Adicionar reCAPTCHA v3 no formulário de comentários e CF7.
 * Version:           1.0
 * Requires at least: 5.4
 * Requires PHP:      7.2
 * Author:            Rafael Cecchin
 * Author URI:        www.rafaelcecchin.com.br
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 **/

define("WORDPRESS_RECAPTCHA_V3_URL", plugin_dir_url(__FILE__));

include 'wordpress-recaptchav3.class.php';

$objRecaptchaV3 = new wordpress_recaptchav3();