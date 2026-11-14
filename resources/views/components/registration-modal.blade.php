     <!-- Sign Up Modal -->
        <div class="modal-overlay" id="signup-modal" hidden>
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="signup-modal-title">
                <button class="modal-close" type="button" aria-label="Close sign up dialog">×</button>
                <h2 id="signup-modal-title">Create your account</h2>
                @include('auth.register')
            </div>
        </div>