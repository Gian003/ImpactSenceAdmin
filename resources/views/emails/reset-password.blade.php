<p>Hello <strong>{{ $recipientName }}</strong>,</p>

<p>You requested a password reset for your ImpactSense admin account.</p>

<p>Click the button below to set a new password. This link expires in <strong>60 minutes</strong>.</p>

<p style="margin:24px 0;">
    <a href="{{ $resetUrl }}"
       style="background:#1b3d52; color:#fff; padding:12px 28px;
              text-decoration:none; border-radius:6px; font-weight:bold;">
        Reset Password
    </a>
</p>

<p style="color:#666; font-size:12px;">
    If you did not request this, no action is needed.<br>
    Direct link: {{ $resetUrl }}
</p>
