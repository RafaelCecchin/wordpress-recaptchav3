<?php

    class wordpress_recaptchav3 {
        
        public $pageSlug = 'recaptchav3';
        public $sectionSlug = 'recaptchav3-section';
        public $groupSlug = 'recaptchav3-group';

        public $optionSiteKeySlug = 'recaptchav3-sitekey';
        public $optionSecretKeySlug = 'recaptchav3-secretkey';

        function __construct() {            
            add_action('admin_menu', array(&$this, 'registerConfigCm'));
        }
        
        public function registerConfigCm() {
            $this->createOptions();
            add_options_page( 'reCAPTCHA v3', 'reCAPTCHA v3', 'manage_options', $this->pageSlug, array(&$this, 'displayRecaptchaV3Submenu'));
        }    
        public function displayRecaptchaV3Submenu() {
            $this->enqueueScripts();
        
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
        function enqueueScripts() {
            // Adding js in the footer
            wp_enqueue_script( 'recaptchav3-js', WORDPRESS_RECAPTCHA_V3_URL . 'assets/script/recaptchav3-script.js', array(), "1.0.0", true );
            wp_enqueue_media();
          
            // Register admin stylesheet
            wp_enqueue_style( 'recaptchav3-css', WORDPRESS_RECAPTCHA_V3_URL . 'assets/style/recaptchav3-style.css', array(), "1.0.0", 'all' );
        }
        

    }