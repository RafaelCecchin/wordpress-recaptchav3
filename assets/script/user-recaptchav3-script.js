
const inputs = [...document.querySelectorAll('.wpcf7 input, .wpcf7 textarea, .wpcf7 button')];
const captchas = [...document.querySelectorAll('.g-recaptcha-response')];

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