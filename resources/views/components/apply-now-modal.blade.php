@php
    $hasOpenings = \Illuminate\Support\Facades\DB::table('job_openings')->exists();
@endphp

<!-- Apply Now Modal -->
<div class="modal-overlay" id="apply-modal" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="apply-modal-title">
        <button class="modal-close" type="button" aria-label="Close dialog">×</button>
        <h2 id="apply-modal-title">Continue your application</h2>

        <div class="modal-choice">
            <div class="modal-choicetext">
                <p>Already have an account?</p>
                <span>Continue to login.</span>
            </div>
            <button class="modal-btn secondary" type="button" id="apply-login-btn">Login</button>
        </div>

        <div class="modal-choice">
            <div class="modal-choicetext">
                <p>Don't have an account?</p>
                @if($hasOpenings)
                    <span>Start your application.</span>
                @else
                    <span>No job openings available right now.</span>
                @endif
            </div>

            @if($hasOpenings)
                <button class="modal-btn primary" type="button" id="signup-open-btn">Sign up</button>
            @else
                <button class="modal-btn primary is-disabled" type="button" disabled aria-disabled="true">Sign up</button>
            @endif
        </div>
    </div>
</div>

<style>
    #apply-modal .modal-btn.is-disabled,
    #apply-modal .modal-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
        background: #999;
        border-color: #999;
        color: #fff;
        box-shadow: none;
        transform: none;
    }

    #apply-modal .modal-choicetext span {
        display: block;
        color: #777;
        font-size: 13px;
        line-height: 1.4;
    }
</style>