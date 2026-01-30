<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your OTP Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f4f4f5;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 0.15em;
            margin-bottom: 24px;
        }
        .otp-code {
            background-color: #667eea;
            color: white;
            font-size: 36px;
            letter-spacing: 8px;
            padding: 20px 40px;
            border-radius: 8px;
            display: inline-block;
            margin: 24px 0;
        }
        .message {
            color: #71717a;
            margin-bottom: 24px;
        }
        .footer {
            color: #a1a1aa;
            font-size: 14px;
            margin-top: 32px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">GLYPH</div>
        <h2>Verification Code</h2>
        <p class="message">Use this code to complete your login:</p>
        <div class="otp-code">{{ $otp }}</div>
        <p class="message">This code will expire in 10 minutes.</p>
        <p class="footer">If you didn't request this code, please ignore this email.</p>
    </div>
</body>
</html>