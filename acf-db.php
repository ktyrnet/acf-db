<?php

/*
Plugin Name: Advanced Custom Fields: DB Field
Plugin URI: https://github.com/ktyrnet/acf-db
Description: ACF DB Field
Version: 1.0.0
Author: k hisa
Author URI: https://ktyr.net
License: MIT
License URI: http://opensource.org/licenses/MIT
Text Domain: acf-db
*/

if( ! defined( 'ABSPATH' ) ) exit;

class acf_plugin_db
{
    public function __construct()
    {
        $this->settings = array(
            'version'   => '1.0.1',
            'url'       => plugin_dir_url( __FILE__ ),
            'path'      => plugin_dir_path( __FILE__ )
        );
        add_action('acf/include_field_types',   array($this, 'include_field_types'));
    }

    function include_field_types( $version = false )
    {
        require( dirname( __FILE__ )  . '/fields/acf-db-common.php');
        require( dirname( __FILE__ )  . '/fields/acf-db.php');
        new acf_db( $this->settings );
    }
}

new acf_plugin_db;