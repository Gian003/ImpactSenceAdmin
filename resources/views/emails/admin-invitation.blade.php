<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #F2F0F0; margin: 0; padding: 0; }
  .wrap { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.10); }
  .header { background: #7B1A2E; padding: 28px 32px; display: flex; align-items: center; gap: 14px; }
  .header-text h1 { color: #fff; margin: 0; font-size: 20px; letter-spacing: .06em; text-transform: uppercase; }
  .header-text p  { color: rgba(255,255,255,.60); margin: 4px 0 0; font-size: 12px; letter-spacing: .03em; }
  .body { padding: 32px; color: #333; line-height: 1.65; font-size: 14px; }
  .role-badge { display: inline-block; background: #F4C5D0; color: #7B1A2E;
                font-weight: 700; padding: 4px 16px; border-radius: 20px;
                font-size: 13px; margin-bottom: 20px; letter-spacing: .03em; }
  .btn { display: inline-block; margin: 24px 0 8px;
         background: #7B1A2E; color: #fff !important; text-decoration: none;
         padding: 13px 30px; border-radius: 30px; font-size: 14px; font-weight: 700;
         letter-spacing: .06em; text-transform: uppercase; }
  .btn:hover { background: #5C1020; }
  .note { font-size: 12px; color: #999; margin-top: 16px; word-break: break-all; }
  .divider { border: none; border-top: 1px solid #f0eded; margin: 20px 0; }
  .footer { background: #f9f9f9; padding: 14px 32px; font-size: 11px; color: #aaa; border-top: 1px solid #eee; text-align: center; }
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <div class="header-text">
      <h1>ImpactSense</h1>
      <p>PNP Urdaneta &mdash; Motorcycle Accident Detection System</p>
    </div>
  </div>

  <div class="body">
    <p>Hello,</p>
    <p>You have been invited to join the <strong>ImpactSense</strong> system as:</p>

    <div class="role-badge">
      {{ $invitation->role === 'toc' ? 'TOC Officer' : 'Investigation Officer' }}
    </div>

    <p>Click the button below to set up your account and create your password. This invitation link expires in <strong>24 hours</strong>.</p>

    <a href="{{ route('admin.invitations.accept', $invitation->token) }}" class="btn">
      Accept Invitation &amp; Set Password
    </a>

    <hr class="divider">

    <p class="note">
      <strong>Can't click the button?</strong> Copy and paste this URL into your browser:<br>
      {{ route('admin.invitations.accept', $invitation->token) }}
    </p>

    <p class="note">
      If you did not expect this invitation, you can safely ignore this email. The link will expire automatically after 24 hours.
    </p>
  </div>

  <div class="footer">
    ImpactSense &mdash; PNP Urdaneta City, Pangasinan &mdash; Automated message, do not reply.
  </div>

</div>
</body>
</html>
