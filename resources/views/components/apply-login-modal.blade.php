 <!-- Apply Login Modal -->
        <div id="apply-login-modal" class="login-modal" onclick="closeApplyLoginModal()">
            <div class="login-modal-content" onclick="event.stopPropagation();">
                <span class="login-modal-close" onclick="closeApplyLoginModal();">&times;</span>
                <div class="login-modal-inner">
                    <div class="login-header">
                        <h2>Login to Apply</h2>
                    </div>
<<<<<<< HEAD
                 @include('auth.login', [
                     'formId' => 'apply-login-form',
                     'emailInputId' => 'apply-email',
                     'passwordInputId' => 'apply-password',
                     'loginContext' => 'apply',
                 ])
=======
                 @include('auth.login')
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                    <div class="login-note">Don’t have an account? <a href="#" id="apply-login-signup-link">Sign up</a></div>
                </div>
            </div>
        </div>
