  <!-- Login Modal -->
        <div id="login-modal" class="login-modal" onclick="closeLoginModal()">
            <div class="login-modal-content" onclick="event.stopPropagation();">
                <span class="login-modal-close" onclick="closeLoginModal();">&times;</span>
                <div class="login-modal-inner">
                    <div class="login-header">
                        <img src="Image/SMMS Logo.png" alt="School Logo" class="login-logo">
                        <h2>St. Mary Magdalene School</h2>
                    </div>
                    <div class="login-tabs">
                        <button class="login-tab active" data-role="admin">Admin</button>
                        <button class="login-tab" data-role="hr">HR</button>
                        <button class="login-tab" data-role="accounting">Accounting</button>
                        <button class="login-tab" data-role="employee">Employee</button>
                    </div>
                    @include('auth.login')
                <div class="demo-credentials" id="demo-credentials">
                    <h3>Demo Credentials</h3>
                    <p id="credentials-text">Choose a role button to view the matching demo email and password.</p>
                    </div>
                </div>
            </div>
        </div>