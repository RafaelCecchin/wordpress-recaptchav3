
const captchas = [...document.querySelectorAll(`.g-recaptcha-response`)];
const inputs = [...document.querySelectorAll(`.wpcf7 input, .wpcf7 textarea, .wpcf7 button,
                                                .comment-form input, .comment-form textarea, .comment-form button`)];

setInputsEventClick();

function setInputsEventClick() {
    inputs.forEach(input => {
        input.addEventListener('click', function(event) {
            
            setCaptchasToken();
    
        });
    });
}

function setCaptchasToken() {
    grecaptcha.execute(captcha_site_key, {action:'submit'}).then(function(token) {
        captchas.forEach(captcha => {
            captcha.value = token;
        });        
    });
}