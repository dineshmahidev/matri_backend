<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Password Reset OTP — Ungalkalyanam</title>
</head>
<body style="margin:0;padding:0;background:#0D0404;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(180deg,#0D0404 0%,#1A0808 100%);">
    <tr>
      <td align="center" style="padding:48px 20px;">
        <table width="520" cellpadding="0" cellspacing="0" style="background:#1A0808;border-radius:24px;box-shadow:0 20px 60px rgba(212,175,55,0.18);border:1px solid rgba(212,175,55,0.18);">

          <!-- Gold accent line -->
          <tr>
            <td style="height:6px;background:linear-gradient(90deg,#4A0404,#D4AF37,#C2185B,#D4AF37,#4A0404);border-radius:24px 24px 0 0;"></td>
          </tr>

          <!-- Header with logo -->
          <tr>
            <td align="center" style="padding:40px 40px 8px;">
              <img src="https://ungalkalyanam.in/logo-light.png" alt="Ungalkalyanam" width="200" style="display:block;margin:0 auto 8px;max-width:220px;">
              <div style="width:48px;height:2px;background:linear-gradient(90deg,#D4AF37,#C2185B);margin:14px auto;"></div>
              <h1 style="font-size:22px;color:#FFD966;margin:12px 0 0;letter-spacing:-0.3px;font-weight:700;">Password Reset OTP</h1>
              <p style="font-size:14px;color:#C4A882;margin:8px 0 0;line-height:1.6;">Hi {{ $user->name }}, use the OTP below to reset your password. It expires in <strong style="color:#FFD966;">10 minutes</strong>.</p>
            </td>
          </tr>

          <!-- OTP box -->
          <tr>
            <td align="center" style="padding:32px 40px;">
              <div style="background:linear-gradient(135deg,#4A0404 0%,#2A0A0A 100%);border-radius:16px;padding:28px 20px;border:2px solid rgba(212,175,55,0.35);letter-spacing:14px;font-size:34px;font-weight:800;color:#FFD966;box-shadow:0 0 30px rgba(212,175,55,0.25);">
                {{ $otp }}
              </div>
              <p style="font-size:12px;color:#8a6d4d;margin:16px 0 0;letter-spacing:0.5px;">THIS IS YOUR ONE-TIME PASSWORD</p>
            </td>
          </tr>

          <!-- CTA -->
          <tr>
            <td align="center" style="padding:0 40px 8px;">
              <p style="font-size:13px;color:#C4A882;margin:0;line-height:1.6;">If you can't see the code above, it is:</p>
              <p style="font-size:18px;font-weight:700;color:#FFD966;margin:8px 0 0;letter-spacing:3px;">{{ $otp }}</p>
            </td>
          </tr>

          <!-- Security note -->
          <tr>
            <td align="center" style="padding:28px 40px;">
              <div style="background:rgba(212,175,55,0.08);border:1px solid rgba(212,175,55,0.15);border-radius:12px;padding:16px 20px;text-align:left;">
                <p style="font-size:13px;color:#C4A882;margin:0;line-height:1.7;">
                  <span style="color:#FFD966;font-weight:700;">🔒 Security Tip:</span> Never share this OTP with anyone. Our team will never ask for it. If you didn't request this reset, you can safely ignore this email — your account is secure.
                </p>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:28px 40px 36px;border-top:1px solid rgba(212,175,55,0.12);">
              <p style="font-size:13px;color:#FFD966;margin:0 0 6px;font-weight:600;">Ungalkalyanam — Find your perfect match</p>
              <p style="font-size:12px;color:#8a6d4d;margin:0;line-height:1.7;">
                <a href="https://ungalkalyanam.in" style="color:#D4AF37;text-decoration:none;">Home</a>
                &nbsp;•&nbsp;
                <a href="https://ungalkalyanam.in/pricing" style="color:#D4AF37;text-decoration:none;">Pricing</a>
                &nbsp;•&nbsp;
                <a href="https://ungalkalyanam.in/contact" style="color:#D4AF37;text-decoration:none;">Contact</a>
              </p>
              <p style="font-size:11px;color:#6b5636;margin:14px 0 0;line-height:1.6;">© {{ date('Y') }} Ungalkalyanam. All rights reserved.<br>Madurai, Tamil Nadu, India</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>