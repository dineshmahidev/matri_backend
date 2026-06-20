<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#FFFDFB;font-family:'Segoe UI',Tahoma,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center" style="padding:40px 20px;">
        <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:20px;box-shadow:0 4px 24px rgba(232,63,123,0.08);">
          <tr>
            <td align="center" style="padding:40px 40px 20px;">
              <img src="{{ config('app.url') }}/logo-dark.png" alt="Ungalkalyanam" height="40" style="margin-bottom:20px;">
              <h1 style="font-size:22px;color:#2D0808;margin:0 0 8px;">Password Reset OTP</h1>
              <p style="font-size:14px;color:#8B6565;margin:0;">Use the following OTP to reset your password. This OTP expires in 10 minutes.</p>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:20px 40px;">
              <div style="background:linear-gradient(135deg,rgba(232,63,123,0.06),rgba(212,175,55,0.06));border-radius:16px;padding:24px;border:1px solid rgba(232,63,123,0.12);letter-spacing:12px;font-size:32px;font-weight:700;color:#E83F7B;">
                {{ $otp }}
              </div>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:0 40px 20px;">
              <p style="font-size:13px;color:#8B6565;margin:0;">Hi {{ $user->name }},</p>
              <p style="font-size:13px;color:#8B6565;margin:8px 0 0;">If you did not request this, please ignore this email. Your account is secure.</p>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:20px 40px 40px;border-top:1px solid #f0e0e0;">
              <p style="font-size:12px;color:#B89595;margin:0;">Ungalkalyanam — Matrimony for Tamil Nadu</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
