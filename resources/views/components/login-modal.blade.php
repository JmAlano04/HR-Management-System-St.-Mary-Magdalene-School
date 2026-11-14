  <!-- Login Modal -->
        <div id="login-modal" class="login-modal" onclick="closeLoginModal()">
            <div class="login-modal-content" onclick="event.stopPropagation();">
                <span class="login-modal-close" onclick="closeLoginModal();">&times;</span>
                <div class="login-modal-inner">
                    <div class="login-header">
                        <img src="Image/SMMS Logo.png" alt="School Logo" class="login-logo">
                        <h2>St. Mary Magdalene School</h2>
                    </div>
                    @include('auth.login', [
                        'formId' => 'login-form',
                        'emailInputId' => 'email',
                        'passwordInputId' => 'password',
                    ])
                    <p class="login-note">Your dashboard is selected automatically based on your account role.</p>
                </div>
            </div>
        </div>