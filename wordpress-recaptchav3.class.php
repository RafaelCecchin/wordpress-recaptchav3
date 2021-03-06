<?php

    class wordpress_recaptchav3 {
        
        private $pageSlug = 'recaptchav3';
        private $sectionSlug = 'recaptchav3-section';
        private $groupSlug = 'recaptchav3-group';
        
        private $optionSiteKeySlug = 'recaptchav3-sitekey';
        private $optionSecretKeySlug = 'recaptchav3-secretkey';
        private $optionShowBadge = 'recaptchav3-showbadge';

        function __construct() {            
            
            add_action( 'admin_menu', array(&$this, 'registerConfigCm') );
            add_action( 'admin_enqueue_scripts', array(&$this, 'adminEnqueueScripts') );
            add_action( 'admin_init', array(&$this, 'adminNotice') );
            add_filter( 'plugin_action_links_'.WORDPRESS_RECAPTCHA_V3_BASENAME, array(&$this, 'addSettingsLinkPluginsPage'), 10, 1 );

            $this->configureAllForms();

        }        

        //Init
        function configureAllForms() {
            
            if ($this->haveCredentials()) {

                add_action( 'wp_enqueue_scripts', array(&$this, 'userEnqueueScripts') );
                add_action( 'wp_footer', array(&$this, 'hideRecaptcha') );

                $this->contactFormConfigureRecaptchaV3();
                $this->commentsConfigureRecaptchaV3();

            }            

        }
        
        //Contact Form
        function contactFormConfigureRecaptchaV3() {  
            if (defined( 'WPCF7_PLUGIN' )) {
                add_action( 'wpcf7_init', array(&$this, 'contactFormAddRecaptchaV3Input') );
                add_filter( 'wpcf7_spam', array(&$this, 'contactFormVerifyResponse'), 9, 2 );
            }              
        }
        function contactFormAddRecaptchaV3Input() {
            wpcf7_add_form_tag('recaptchav3', array(&$this, 'getRecaptchaV3Input') );
        }        
        function contactFormVerifyResponse( $spam, $submission = false ) {
            if ( $spam ) {
                return $spam;
            }

            $token = isset( $_POST['g-recaptcha-response'] )
                ? trim( $_POST['g-recaptcha-response'] ) : '';

            if ( $this->verifyReCaptcha( $token ) ) { // Human
                $spam = false;
            } else { // Bot
                $spam = true;
                if ( $submission ) {
                    $submission->add_spam_log( array(
                        'agent' => 'recaptcha',
                        'reason' => __(
                            'reCAPTCHA invalido.',
                            'contact-form-7'
                        ),
                    ) );
                }                
            }

            return $spam;
        }

        //Comments
        function commentsConfigureRecaptchaV3 () {
            add_action( 'comment_form', array( &$this, 'commentsShowRecaptchaV3Input' ) );
            add_filter( 'wp_insert_comment', array( &$this, 'commentsVerifyResponse' ) );
        }
        function commentsShowRecaptchaV3Input() {
            $this->getRecaptchaV3Input( true );
        }
        function commentsVerifyResponse( $comment_id ) {
            $token = isset( $_POST['g-recaptcha-response'] )
                ? trim( $_POST['g-recaptcha-response'] ) : '';

            if ( ! $this->verifyReCaptcha( $token ) ) { // Bot
                wp_set_comment_status( $comment_id, 'spam' );        
                wp_die( '<strong>Erro:</strong> reCAPTCHA invalido.', 'reCAPTCHA error', array( "back_link" => true ) );
            } 
        }

        //Panel Config
        function registerConfigCm() {
            $this->createOptions();
            add_options_page( 'reCAPTCHA v3', 'reCAPTCHA v3', 'manage_options', $this->pageSlug, array( &$this, 'displayRecaptchaV3Menu' ) );
        }            
        function displayRecaptchaV3Menu() {
        
            echo '<div class="wrap recaptchav3-configuracoes">
                    <img width="130" height="145" class="dashboard-image" src="'.WORDPRESS_RECAPTCHA_V3_URL.'assets/images/admin-dashboard.svg" alt="Menu administrativo"/>

                    <h1>reCAPTCHA V3</h1>
                    <p>Preencha as credenciais abaixo.</p>

                    <form method="post" action="options.php">';
                            
                        settings_fields( $this->groupSlug );
                        do_settings_sections( $this->pageSlug );
                        submit_button();
            echo '
                    </form>
                  </div>';
        }
        function createOptions() {
            add_settings_section( $this->sectionSlug, '', '', $this->pageSlug );

            register_setting( $this->groupSlug, $this->optionSiteKeySlug );
            add_settings_field(
                $this->optionSiteKeySlug,
                "Site key",
                array($this, 'showSiteKeyTextField'),
                $this->pageSlug,
                $this->sectionSlug,       
                array( 
                    'label_for' => $this->optionSiteKeySlug
                )
            );

            register_setting( $this->groupSlug, $this->optionSecretKeySlug );
            add_settings_field(
                $this->optionSecretKeySlug,
                "Secret key",
                array($this, 'showSecretKeyTextField'),
                $this->pageSlug,
                $this->sectionSlug,         
                array( 
                    'label_for' => $this->optionSecretKeySlug
                )
            );

            register_setting( $this->groupSlug, $this->optionShowBadge );
            add_settings_field(
                $this->optionShowBadge,
                "Show badge",
                array($this, 'showBadgeCheckbox'),
                $this->pageSlug,
                $this->sectionSlug,         
                array( 
                    'label_for' => $this->optionShowBadge
                )
            );
        }
        function showSiteKeyTextField() {
            $this->generateTextField( $this->optionSiteKeySlug );
        }
        function showSecretKeyTextField() {
            $this->generateTextField( $this->optionSecretKeySlug );
        }
        function showBadgeCheckbox() {
            $this->generateCheckboxField( $this->optionShowBadge );
        }
        function generateTextField( $optionName ) {
            $value = esc_attr( get_option( $optionName ) );

            printf(
                '<input type="text" id="%s" name="%s" value="%s" />',
                $optionName, 
                $optionName, 
                esc_attr( $value )
            );
        }
        function generateCheckboxField( $optionName ) {
            $value = esc_attr( get_option( $optionName ) );

            printf(
                '<input type="checkbox" id="%s" name="%s" %s/>',
                $optionName, 
                $optionName, 
                esc_attr( $value ) == true ? "checked" : ""
            );
        }
        function adminEnqueueScripts() {
            // js
            wp_enqueue_script( 'admin-recaptchav3-js', WORDPRESS_RECAPTCHA_V3_URL . 'assets/script/admin-recaptchav3-script.js', array(), "1.0.0", true );
            wp_enqueue_media();
          
            // stylesheet
            wp_enqueue_style( 'admin-recaptchav3-css', WORDPRESS_RECAPTCHA_V3_URL . 'assets/style/admin-recaptchav3-style.css', array(), "1.0.0", 'all' );
        }
        function getSiteKeyOption() {
            return esc_attr( get_option( $this->optionSiteKeySlug ) );
        }
        function getSecretKeyOption() {
            return esc_attr( get_option( $this->optionSecretKeySlug ) );
        }
        function getShowBadgeOption() {
            return esc_attr( get_option( $this->optionShowBadge ) );
        }
        function haveCredentials() {
            if ( $this->getSiteKeyOption() && $this->getSecretKeyOption() ) {
                return true;
            }

            return false;
        }
        function addSettingsLinkPluginsPage( $links ) {
            $settings_link = '<a href="'.$this->getSettingsLink().'">' . __( 'Settings' ) . '</a>';
            $links[] = $settings_link;
            
            return $links;
        }
        function adminNotice() {
            global $pagenow;

            if ( !$this->haveCredentials() && ( $pagenow == 'index.php' || $pagenow == 'plugins.php' ) ) {
                
                echo '<div class="notice notice-error is-dismissible">
                        <p>Voc?? deve preencher as credenciais <a href="'.$this->getSettingsLink().'">neste link</a> para come??ar a usar o reCAPTCHA no site.</p>
                     </div>';

            }     
        }
        function getSettingsLink() {
            return get_admin_url()."options-general.php?page=".$this->pageSlug;
        }

        //User scripts
        function userEnqueueScripts() {
            // js
            if ( $this->haveCredentials() ) {

                add_action( 'wp_head', function() {
                    echo "<script>
        
                        var captcha_site_key = '".$this->getSiteKeyOption()."';
                    
                    </script>";
                });

                wp_enqueue_script( 'recaptchav3-js', 'https://www.google.com/recaptcha/api.js?render='.$this->getSiteKeyOption(), array(), "1.0.0", false );
                wp_enqueue_script( 'user-recaptchav3-js', WORDPRESS_RECAPTCHA_V3_URL . 'assets/script/user-recaptchav3-script.js', array('recaptchav3-js'), "1.0.0", true );
            }
        }
        function hideRecaptcha() {

            if ( !$this->getShowBadgeOption() ) {

                echo '

                    <style>
                        .grecaptcha-badge { opacity: 0; visibility: hidden; }
                    </style>
                    
                    ';
            }

        }
        
        //Services
        function verifyReCaptcha( $recaptchaCode ){
            $postdata = http_build_query( ["secret"=>$this->getSecretKeyOption(),"response"=>$recaptchaCode] );


            $opts = ['http' =>
                [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata
                ]
            ];
            $context  = stream_context_create( $opts );
            $result = file_get_contents( 'https://www.google.com/recaptcha/api/siteverify', false, $context );
            $check = json_decode( $result );

            return $check->success;
        }
        function getRecaptchaV3Input( $echo = false ) {
            $tag = '<input type="hidden" class="g-recaptcha-response" name="g-recaptcha-response">';

            if ( $echo ) {
                echo $tag;
            }

            return $tag;
        } 

    }