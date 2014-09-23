<?php

/*
Plugin Name: Search Types Custom Fields Widget
Plugin URI: http://alttypes.wordpress.com/
Description: Widget for searching Types custom fields and custom taxonomies.
Version: 0.4.6.1.3
Author: Magenta Cuda (PHP), Black Charger (JavaScript)
Author URI: http://magentacuda.wordpress.com
License: GPL2
Documentation: http://alttypes.wordpress.com/
 */
 
# The check for version is in its own file since if the file contains PHP 5.4 code an ugly fatal parse error will be triggered instead

list( $major, $minor ) = sscanf( phpversion(), '%D.%D' );
$tested_major = 5;
$tested_minor = 4;
if ( !( $major > $tested_major || ( $major == $tested_major && $minor >= $tested_minor ) ) ) {
    add_action( 'admin_notices', function() use ( $major, $minor, $tested_major, $tested_minor ) {
        echo <<<EOD
<div style="padding:10px 20px;border:2px solid red;margin:50px 20px;font-weight:bold;">
    Search Types Custom Fields Widget will not work with PHP version $major.$minor;
    Please uninstall it or upgrade your PHP version to $tested_major.$tested_minor or later.
</div>
EOD;
    } );
    return;
}

# ok to start loading PHP 5.4 code

require( dirname( __FILE__ ) . '/search-types-custom-fields-widget.php' );
