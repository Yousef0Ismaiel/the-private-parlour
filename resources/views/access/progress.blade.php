<ol class="steps" aria-label="Sign-in progress">
    <li @if($step === 1) aria-current="step" @endif>Password</li>
    <li @if($step === 2) aria-current="step" @endif>Authenticator</li>
</ol>
