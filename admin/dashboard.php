<?php
/**
 * Plugin admin template file.
 * 
 * @since   2.0.0
 */

// Exit if call this file directly.
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'ZB_Dashboard' ) ){

    class ZB_Dashboard{

        /**
         * Class constructer
         * 
         * @since   2.0.0
         */
        public function __construct(){

            /**
             *  Admin template action and filters.
             */
            add_action( 'admin_menu', array( $this, 'zb_menu' ) );

            add_action( 'before_zb_from', array( $this, 'zb_save_api_details' ) );

            add_action( 'init', array( $this, 'zb_generate_token' ) );

            add_action( 'wp_ajax_zi_get_log', array( $this, 'zb_getLog' ) );
        }

        /**
         * Create plugin admin menu
         * 
         * @since   2.0.0
         * @access  public
         * @return  void
         */

        function zb_menu(){

            add_menu_page(
                __( 'Zoho Bridge', 'textdomain' ),
                __( 'Zoho Bridge', 'textdomain' ),
                'manage_options',
                'zoho-bridge',
                array( $this, 'zb_setting_page' )
            );

            /**
             * Create plugin sub-menu
             */
            /*add_submenu_page( 
                'zoho-bridge',
                __( 'Log Viwer', 'textdomain' ),
                __( 'Log Viwer', 'textdomain' ),
                'manage_options',
                'zoho-log-viwer',
                array( $this, 'zb_log_viwer' )
            );*/
        }

        /**
         * Plugin admin menu setting page.
         * 
         * @since   2.0.0
         * @access  public
         * @return  void
         */

        function zb_setting_page(){
            
            $token_data = get_option("zv2_token");
            ?>
            <div class="wrap">
                <h1>ZOHO Bridge <label></label></h1>
            <?php
             do_action( 'before_zb_from' );
             
             $api_info = get_option( 'zb_api_info' );
             
             ?>
             <form action="" method="post">
             <table class="form-table" >
                <tbody>
                    <tr>
                        <th>
                            <label>Client ID<label>
                        </th>
                        <td>
                            <input type="text" name="api_info[client_id]" value="<?php if( isset( $api_info['client_id'] ) ){ echo $api_info['client_id']; } ?>" class="client_id regular-text" >
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label>Client Secret<label>
                        </th>
                        <td>
                            <input type="text" name="api_info[client_secret]" value="<?php if( isset( $api_info['client_secret'] ) ){ echo $api_info['client_secret']; } ?>" class="client_secret regular-text" >
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label>Redirect URL<label>
                        </th>
                        <td>
                            <input type="text" name="api_info[redirect_url]" value="<?php if( isset( $api_info['redirect_url'] ) ){ echo $api_info['redirect_url']; } ?>" class="redirect_url regular-text" >
                        </td>
                    </tr>
                </tbody>
             </table>
                <p>
                    <input type="hidden" value="zb" name="zb" />
                    <input class="button-primary button" type="submit" value="Submit" name="submit">
                </p>
                <p>
                    <a href="#" class="auth">Authorize App</a>
                </p>
             </form>
             </div>
            
                <?php
                
                /*echo "<pre>";
                print_r($token_data);
                echo "</pre>";*/
                
                ?>

             <script>
                <?php if( !empty( $api_info ) ){ ?>
                    jQuery('.auth').click(function(){
                        window.open('https://accounts.zoho.<?php echo ZB_DOMAIN; ?>/oauth/v2/auth?scope=ZohoCRM.users.ALL,ZohoCRM.modules.ALL,ZohoSearch.securesearch.READ&client_id=<?php echo $api_info["client_id"]; ?>&response_type=code&access_type=offline&prompt=consent&redirect_uri=<?php echo $api_info["redirect_url"]; ?>','ZOHO App Authorization','width=700,height=700');
                    });
                <?php } ?>
             </script>
             <?php
        }

        /**
         * Save plugin api information.
         * 
         * @since   2.0.0
         * @access  public
         * @return  void
         */

        function zb_save_api_details(){

            if( isset( $_REQUEST['zb'] ) ){
        
                $info = $_REQUEST['api_info'];
        
                update_option( 'zb_api_info', $info );
        
                echo '<p style="color:mediumseagreen;"><b>API Credentials Saved</b></p>';
            }
        }

        /**
         * Genrate zoho api auth-token
         * 
         * @since   2.0.0
         * @access  public
         * @return  void
         */

        function zb_generate_token(){
            
            if( isset( $_REQUEST['code'] ) && isset( $_REQUEST['accounts-server'] ) ){
        
                update_option( 'zv2_grand_code', $_REQUEST['code'] );
        
                $api_info = get_option( 'zb_api_info' );
                
                $url    = 'https://accounts.zoho.'.ZB_DOMAIN.'/oauth/v2/token';
                $param     = 'code='.$_REQUEST["code"].'&redirect_uri='.$api_info["redirect_url"].'&client_id='.$api_info["client_id"].'&client_secret='.$api_info["client_secret"].'&grant_type=authorization_code';
        
                $response = $this->zb_curl( $url, $param );
        
                $after_1hour = strtotime(date( 'd-m-Y h:i:s', strtotime( current_time( 'd-m-Y h:i:s' )." + 1 hours" ) ));
        
                if( isset( $response['access_token'] ) ){
        
                    $response['token_expire_timestamp'] = $after_1hour;
                    update_option( 'zv2_token',$response );
                }
            }
        
        }

        /**
         * Make curl request.
         * 
         * @since   2.0.0
         * @access  public
         * @return  array
         */
        
        function zb_curl( $url, $param = '', $token = false ){

            //$this->is_token_valid();
        
            $token_data = get_option( 'zv2_token' );
            $headers = array( 'Content Type: text/xml' );
        
            if( $token ){
                $headers[] = 'Authorization: Zoho-oauthtoken '.$token_data['access_token'];
            }
        
            $ch_fetch_pot = curl_init();
            curl_setopt($ch_fetch_pot, CURLOPT_URL, $url);
            curl_setopt($ch_fetch_pot, CURLOPT_HTTPHEADER, $headers );
            curl_setopt($ch_fetch_pot, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch_fetch_pot, CURLOPT_RETURNTRANSFER, true);
        
            if( !empty( $param ) ){
        
                curl_setopt($ch_fetch_pot, CURLOPT_POSTFIELDS, $param);
            }
        
            $result_fetch_pot = curl_exec($ch_fetch_pot);
            curl_close($ch_fetch_pot);
            
            return json_decode( $result_fetch_pot, true );
        }
        
        /**
         * Check is current token is valid or not.
         * 
         * @since   2.0.0
         * @access  public
         * @return  void
         */

        function is_token_valid(){
        
            $token_data = get_option( 'zv2_token' );
            $api_info   = get_option( 'zb_api_info' );
        
            if( current_time( 'timestamp' ) > $token_data['token_expire_timestamp'] || current_time( 'timestamp' ) == $token_data['token_expire_timestamp'] ){ 
        
                $url        = 'https://accounts.zoho.'.ZB_DOMAIN.'/oauth/v2/token';
                $param      = 'refresh_token='.$token_data["refresh_token"].'&client_id='.$api_info["client_id"].'&client_secret='.$api_info["client_secret"].'&grant_type=refresh_token';
        
                $headers = array( 'Content Type: text/xml' );
        
                $ch_fetch_pot = curl_init();
                curl_setopt($ch_fetch_pot, CURLOPT_URL, $url);
                curl_setopt($ch_fetch_pot, CURLOPT_HTTPHEADER, $headers );
                curl_setopt($ch_fetch_pot, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch_fetch_pot, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_fetch_pot, CURLOPT_POSTFIELDS, $param);
               
                $result_fetch_pot = curl_exec($ch_fetch_pot);
                curl_close($ch_fetch_pot);
                
                $response = json_decode( $result_fetch_pot, true );
        
                $after_1hour = strtotime(date( 'd-m-Y h:i:s', strtotime( current_time( 'd-m-Y h:i:s' )." + 1 hours" ) ));
        
                if( isset( $response['access_token'] ) ){
        
                    $response['token_expire_timestamp'] = $after_1hour;
                    update_option( 'zv2_token',$response );
                }
            }
        }

        /**
         * Show zoho logs file.
         * 
         * @since   2.0.0
         * @access  public
         * @return  void
         */

        function zb_log_viwer(){
	
            $days_after_30 = strtotime( date( 'd-m-Y', strtotime( current_time( 'd-m-Y' ).' -30 days' ) ) );
            
            $log_directory = ABSPATH.'wp-content/uploads/zoho-bridge/';
        
            $files = array();
            
            if (is_dir($log_directory)){
               
                if ($handle = opendir($log_directory)){
                    //Notice the parentheses I added:
                    while(($file = readdir($handle)) !== FALSE){
                        $files[] = substr( $file,0, strpos( $file,'_' ) );
                    }
                    closedir($handle);
                }
                
            }
            
            $options = '';
            
            $recent_file = '';
            
            sort( $files, SORT_NUMERIC ); 
            
            $files = array_reverse( $files );
            
            if( !empty( $files ) ){
                
                foreach( $files as $log_file ){

                if( $log_file!='' ){
                   
                    if( strtotime( $log_file ) > $days_after_30 || strtotime( $log_file ) == $days_after_30 ){
                    
                    $options .= '<option '.$selected.' value="'.$log_file.'" >'.date( 'M d Y', strtotime( $log_file ) ).'</option>'; 
                    
                    if( $recent_file == '' ){
                    
                        $recent_file = $log_directory.$log_file.'_log.txt';
                    
                    }
                    }
                }
                }
            }
            
            ?>
            
            <div class="container">
                <div class="row">
                <div class="col-sm-12">
                    <h2 class="text-center">Zoho Logs</h2>
                    <form class="form-inline">
                    <div class="form-group">
                      <label for="email">Select Log File</label>
                      <select name="log_file" class="form-control">
                        <?php echo $options; ?>
                      </select>
                      <img src="images/spinner-2x.gif" width="25" id="spinner" style="transform: translateY(9px);display:none;margin-left:5px;">
                    </div>
                    </form> 
                </div>
                </div>
            </div>
            
            <br/>
            <div class="container">
                <div class="row">
                <div class="col-sm-8" id="log-div" >
                    <?php
                    
                    if( file_exists( $recent_file ) ){
                    
                    $open_file 	= fopen( $recent_file, 'r' );
                    $file_content 	=  fread( $open_file , filesize( $recent_file ));
                    
                    echo wpautop( $file_content );
                    
                    fclose( $open_file );
                    }
                    ?>
                </div>
                </div>
            </div>
            
            <script type="text/javascript" >
                jQuery('select[name="log_file"]').change(function(){
                var file = jQuery(this).val();
                
                jQuery.ajax({
                    url:ajaxurl,
                    type:'POST',
                    data:{
                    filename:file,
                    action:'zi_get_log'
                    },
                    beforeSend:function(){
                        jQuery('#spinner').show();
                        //loader_init();
                    },
                    success:function( response ){
                        jQuery('#log-div').html( response );
                        jQuery('#spinner').hide();
                        //loader_destroy();
                    }
                })
                });
            </script>

            <style>
                #log-div{
    
                    margin-top: 0px;
                    border: 2px solid dodgerblue;
                    box-shadow: inset 0px 0px 8px grey;
                    height: 520px;
                    overflow-y: scroll;
                    background: #eee;
                    padding:20px;
                    width:80%;
                }
            </style>
            <?php
        }

        /**
         * Get log file data
         * 
         * @since   2.0.0
         * @access  public
         * @return  void
         */
        
        function zb_getLog(){
        
            if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
                
                $filename = ABSPATH.'wp-content/uploads/zoho-bridge/'.$_REQUEST['filename'].'_log.txt';
                
                if( file_exists( $filename ) ){
                    
                $open_file 	= fopen( $filename, 'r' );
                $file_content 	=  fread( $open_file , filesize( $filename ));
                
                echo wpautop( $file_content );
                
                fclose( $open_file );
                }
            }
            
            wp_die();
        }
    } // class end
} // class exists check end
new ZB_Dashboard();
?>