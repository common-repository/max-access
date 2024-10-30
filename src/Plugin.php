<?php

namespace MaxAccess;

use MaxAccess\Traits\Singleton;

class Plugin {

    use Singleton;

    function __construct() {
        Admin::getInstance();

        if ( get_option('max_access_settings') === null) {
            set_option( 'max_access_settings' )['max_access_enabled'] = true;
            set_option( 'max_access_settings' )['oada_license_key'] = null;
        }
        
        if(isset(get_option( 'max_access_settings' )['max_access_enabled'])) {
            if ( get_option( 'max_access_settings' )['max_access_enabled'] )
                add_action( 'wp_head', [$this, 'print_script'] );
        }
    }
    
    function print_script()
    {
        ?>
        <script id="oada_ma_toolbar_script">
            var oada_ma_license_key="<?php echo get_option( 'max_access_settings' )['oada_license_key']; ?>";
            var oada_ma_license_url="https://api.maxaccess.io/scripts/toolbar/";
            (function(s,o,g){a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.src=g;a.setAttribute("async","");
            a.setAttribute("type","text/javascript");a.setAttribute("crossorigin","anonymous");
            m.parentNode.insertBefore(a,m)})(document,"script",oada_ma_license_url+oada_ma_license_key);
        </script>
        <?php
    }

}

?>