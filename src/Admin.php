<?php

namespace MaxAccess;

use MaxAccess\Traits\Singleton;

class Admin {

    use Singleton;

    function __construct() {
        add_action( 'admin_init', [$this, 'settings_init'] );
        add_action( 'admin_menu', [$this, 'menu_init'] );
        add_action( 'rest_api_init', [$this, 'api_init']);

        add_action('admin_notices', [$this, 'admin_notice_success']);
        add_action('admin_notices', [$this, 'admin_notice_freetrial']);

        add_action('admin_head', [$this, 'max_access_dashicon']);

        add_option('oada_activation_token', '0');

        add_option('license_activated', 'false');

        add_option('error_message', 'There was an error activating your license.');

        
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_fonts'] );

        
    }

    function enqueue_fonts( $hook ) {
        
        if ( 'toplevel_page_settings' != $hook ) {
            return;
        }
        
        wp_enqueue_style( 'josefinstyle', "https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap" );
    }

    function settings_init() {
        register_setting( 'MaxAccessPlugin', 'max_access_settings' );

        add_settings_section(
            'Max Access',
            "General",
            [$this, 'setting_section_callback'],
            'MaxAccessPlugin'
        );

        add_settings_field(  
            'max_access_enabled',  
            'Enabled',  
            [$this, 'checkbox_callback'],  
            'MaxAccessPlugin',  
            'Max Access'  
        );

        add_settings_field(
            'oada_license_key',
            'License Key', [$this, 'license_key_callback'],
            'MaxAccessPlugin',
            'Max Access'
        );
    }

    function menu_init()
    {
        add_menu_page( 'Max Access', 'Max Access', 'administrator',
        'settings', [$this, 'settings_page'], 'dashicons-maxaccess' );
    }

    function settings_page() {
        
        ?>
        <form action='options.php' method='post'>
            <div style="width: 1080px; margin:auto; background-color:white;">
                <div style=" height:129px; width: 1080px; background-image:url('<?php echo plugin_dir_url(__FILE__); ?>assets/headerbkgd.gif'); height: 72px; display: flex;" >
                    <img src= "<?php echo plugin_dir_url(__FILE__); ?>assets/maxaccesslogo.png" style="padding-left: 422px; padding-right:422px; padding-top: 5px; padding-bottom: 5px" >            
                </div>
                <div style="padding: 15px;">
                    <?php
                    settings_fields( 'MaxAccessPlugin' );
                    do_settings_sections( 'MaxAccessPlugin' );
                    
                    
                    echo "<p>Please make sure you <a href='https://maxaccess.io/'>create an account on maxaccess.io</a> and register your site's domain. Otherwise the plugin will not work. </p>";
                    echo "<p>Setup instructions can be found <a href='https://maxaccess.io/setup/'>here</a></p>";

                    echo "<p>Max Access options can be edited <a href='https://dashboard.onlineada.com/maxaccess'>here</a></p>";

                    // Save button //
                    ?><br><input type="submit" name="submit" id="submit" class="button button-primary" value="Save" style="border-color: #c80a00; line-height: normal; background-color:#c80a00; font-size:18px; font-weight: 700; font-style: italic; border-radius: 4px; padding: 15px 30px; font-family: Josefin Sans;"  /><?php

                    ?>
                </div>

            </div>
     
        </form>
        <?php
    }

    function setting_section_callback( $arg ) {
        
    }

    function license_key_callback() {
        $options = get_option( 'max_access_settings' );
        echo '<input type="text" name="max_access_settings[oada_license_key]" id="oada_license_key" value=' . $options['oada_license_key'] . ' >';
        
        $activation_token = wp_generate_password(10, false);
        echo '<span id="test" style="display: none;">' . $activation_token . '</span>';
        update_option('oada_activation_token', $activation_token);
        
        $endpoint = get_rest_url(null, 'maxaccess/v1/license/');

        echo '<script>
            function activate_license() {
                var div = document.getElementById("test").textContent;
                var return_url = "https://accounts.onlineada.com/signin/?oada_redirect=";
                return_url = return_url + "' . $endpoint . '&oada_activation_token=";
                return_url = return_url + div + "&oada_site=activatelicense&oada_activation_service=maxaccess";
                window.location = return_url;
            }
        </script>';
        echo ' <input type="button" name="activate" id="activate" onClick="activate_license()" class="button button-primary" value="Link Account" style="border-color:#c80a00; background-color:#c80a00;" />';
    }

    function checkbox_callback() {
        
        $options = get_option( 'max_access_settings' );
        
        $enabled = false;
        if(isset(get_option( 'max_access_settings' )['max_access_enabled'])) {
            $enabled = get_option( 'max_access_settings' )['max_access_enabled'];
        }

        $html = '<input type="checkbox" id="max_access_enabled" name="max_access_settings[max_access_enabled]" value="1"' . checked( 1, $enabled, false ) . '/>';
    
        echo $html;
    
    }

    function activate( $request ) {

        $options = get_option('max_access_settings');
        if ( $request['oada_license_key'] != "FALSE" && $request['oada_license_key'] != "" )
        {
            update_option( 'max_access_settings', array( 'oada_license_key' => $request['oada_license_key'] ) );
            update_option('license_activated', 'true');
        }
        else
        {
            update_option('license_activated', 'error');
            update_option('error_message', str_replace('"', "", $request['oada_response_message']) );
        }
        wp_redirect(get_admin_url(null, 'admin.php?page=settings'));
        exit;
    }

    function api_init() {
        register_rest_route( 'maxaccess/v1', '/license/', array(
            'methods' => 'GET',
            'callback' => [$this, 'activate'],
            'permission_callback' => function($request){

                if ( $request['oada_activation_token'] != get_option('oada_activation_token') )
                {
                    update_option('license_activated', 'error');
                    update_option('error_message', 'There was an error activating your license.');
                    wp_redirect(get_admin_url(null, 'admin.php?page=settings'));
                    exit;
                    return false;
                }
                else
                    return true;
              }
        ) );
    }
    
    function admin_notice_success() {
        if ( get_option('license_activated') == "true" )
        {
            echo '<div class="notice notice-success is-dismissible"> 
                    <p><strong>License successfuly activated.</strong></p>
                </div>';
                update_option('license_activated', 'false');
        }
        if ( get_option('license_activated') == "error" )
        {
            echo '<div class="notice notice-error is-dismissible"> 
                    <p><strong>' .  get_option('error_message') . ' </strong> </p>
                </div>';
                update_option('license_activated', 'false');
        }
    }

    function admin_notice_freetrial() {
        $page = get_admin_page_title();
        if ( $page == 'Max Access' ) 
        {
            $options = get_option('max_access_settings');
            if ( $options['oada_license_key'] == "" )
            {
                echo '<div class="notice notice-success is-dismissible"> 
                        <p><strong>Click <a href="https://maxaccess.io/freetrial/">here</a> to start your 14 day free trial now.</strong></p>
                    </div>';
            }
        }
    }

    function max_access_dashicon() {
        echo '
          <style>
          .dashicons-maxaccess {
              background-image: url("' . plugin_dir_url(__FILE__) . 'assets/maxaccessicon.png");
              background-repeat: no-repeat;
              background-position: center;
              width:16px;
              height:16px;
          }
          </style>
      '; }

}
