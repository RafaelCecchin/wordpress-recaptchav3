<?php

    class wordpress_recaptchav3 {
        
        private $pageSlug = 'recaptchav3';
        private $sectionSlug = 'recaptchav3-section';
        private $groupSlug = 'recaptchav3-group';
        
        private $optionSiteKeySlug = 'recaptchav3-sitekey';
        private $optionSecretKeySlug = 'recaptchav3-secretkey';

        function __construct() {            
            add_action( 'admin_menu', array(&$this, 'registerConfigCm') );
            add_action( 'admin_enqueue_scripts', array(&$this, 'adminEnqueueScripts') );
            add_action( 'wp_enqueue_scripts', array(&$this, 'userEnqueueScripts') );

            $this->ContactFormConfigureRecaptchaV3();
            $this->CommentsConfigureRecaptchaV3();
        }        

        //Contact Form
        function ContactFormConfigureRecaptchaV3() {
            add_action( 'wpcf7_init', array(&$this, 'ContactFormAddRecaptchaV3Input') );
            add_filter( 'wpcf7_spam', array(&$this, 'ContactFormVerifyResponse'), 9, 2 );
        }
        function ContactFormAddRecaptchaV3Input() {
            wpcf7_add_form_tag('recaptchav3', array(&$this, 'getRecaptchaV3Input') );
        }        
        function ContactFormVerifyResponse( $spam, $submission ) {
            if ( $spam ) {
                return $spam;
            }

            $token = isset( $_POST['g-recaptcha-response'] )
                ? trim( $_POST['g-recaptcha-response'] ) : '';

            if ( $this->verifyReCaptcha( $token ) ) { // Human
                $spam = false;
            } else { // Bot
                $spam = true;
                $submission->add_spam_log( array(
                    'agent' => 'recaptcha',
                    'reason' => __(
                        'reCAPTCHA invalido.',
                        'contact-form-7'
                    ),
                ) );
            }

            return $spam;
        }

        //Comments
        function CommentsConfigureRecaptchaV3 () {
            add_action( 'comment_form', array(&$this, 'CommentsShowRecaptchaV3Input') );
            add_filter( 'wp_insert_comment', array(&$this, 'CommentsVerifyResponse') );
        }
        function CommentsShowRecaptchaV3Input() {
            $this->getRecaptchaV3Input(true);
        }
        function CommentsVerifyResponse($comment_id) {
            $token = isset( $_POST['g-recaptcha-response'] )
                ? trim( $_POST['g-recaptcha-response'] ) : '';

            if ( ! $this->verifyReCaptcha( $token ) ) { // Bot
                wp_set_comment_status( $comment_id, 'spam' );        
                wp_die( '<strong>Erro:</strong> reCAPTCHA invalido.', 'reCAPTCHA error', array("back_link" => true) );
            } 
        }

        
        //Panel Config
        function registerConfigCm() {
            $this->createOptions();
            add_options_page( 'reCAPTCHA v3', 'reCAPTCHA v3', 'manage_options', $this->pageSlug, array(&$this, 'displayRecaptchaV3Menu'));
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
        }
        function showSiteKeyTextField() {
            $this->generateTextField($this->optionSiteKeySlug);
        }
        function showSecretKeyTextField() {
            $this->generateTextField($this->optionSecretKeySlug);
        }
        function generateTextField($optionName) {
            $value = esc_attr( get_option($optionName) );

            printf(
                '<input type="text" id="%s" name="%s" value="%s" />',
                $optionName, 
                $optionName, 
                esc_attr( $value )
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
        function haveCredentials() {
            if ($this->getSiteKeyOption() && $this->getSecretKeyOption()) {
                return true;
            }

            return false;
        }

        //User scripts
        function userEnqueueScripts() {
            // js
            if ($this->haveCredentials()) {

                add_action( 'wp_head', function() {
                    echo "<script>
        
                        var captcha_site_key = '".$this->getSiteKeyOption()."';
                    
                    </script>";
                });

                wp_enqueue_script( 'recaptchav3-js', 'https://www.google.com/recaptcha/api.js?render='.$this->getSiteKeyOption(), array(), "1.0.0", false );
                wp_enqueue_script( 'user-recaptchav3-js', WORDPRESS_RECAPTCHA_V3_URL . 'assets/script/user-recaptchav3-script.js', array('recaptchav3-js'), "1.0.0", true );
            }
        }
        
        //Services
        function verifyReCaptcha($recaptchaCode){
            $postdata = http_build_query(["secret"=>$this->getSecretKeyOption(),"response"=>$recaptchaCode]);


            $opts = ['http' =>
                [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata
                ]
            ];
            $context  = stream_context_create($opts);
            $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
            $check = json_decode($result);

            return $check->success;
        }
        function getRecaptchaV3Input($echo = false) {
            $tag = '<input type="hidden" class="g-recaptcha-response" name="g-recaptcha-response">';

            if ($echo) {
                echo $tag;
            }

            return $tag;
        } 

    }