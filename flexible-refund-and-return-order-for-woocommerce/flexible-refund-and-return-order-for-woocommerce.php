<?php
/**
 * Plugin Name: Flexible Refund and Return Order for WooCommerce
 * Plugin URI: https://wpde.sk/flexible-refunds
 * Description: The plugin to handle the refund form on My Account and automates the refund process for the WooCommerce store support.
 * Version: 1.0.31
 * Author: WP Desk
 * Author URI: https://www.wpdesk.net/
 * Text Domain: flexible-refund-and-return-order-for-woocommerce
 * Domain Path: /lang/
 * Requires at least: 6.4
 * Tested up to: 6.8
 * WC requires at least: 9.5
 * WC tested up to: 9.8
 * Requires PHP: 7.4
 * Copyright 2020 WP Desk Ltd.
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/* THESE TWO VARIABLES CAN BE CHANGED AUTOMATICALLY */
$plugin_version = '1.0.31';

$plugin_name        = 'Flexible Refund and Return Order for WooCommerce';
$plugin_class_name  = '\WPDesk\WPDeskFRFree\Plugin';
$plugin_text_domain = 'flexible-refund-and-return-order-for-woocommerce';
$product_id         = 'Flexible Refund and Return Order for WooCommerce';
$plugin_file        = __FILE__;
$plugin_dir         = __DIR__;

$requirements = [
	'php'     => '7.3',
	'wp'      => '6.1',
	'plugins' => [
		[
			'name'      => 'woocommerce/woocommerce.php',
			'nice_name' => 'WooCommerce',
		],
	],
];

require __DIR__ . '/vendor_prefixed/wpdesk/wp-plugin-flow-common/src/plugin-init-php52-free.php';
