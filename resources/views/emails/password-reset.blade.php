<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password</title>
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
            margin-bottom: 24px;
        }
        .message {
            color: #71717a;
            margin-bottom: 24px;
        }
        .reset-button {
            display: inline-block;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin: 24px 0;
        }
        .reset-button:hover {
            background-color: #5a67d8;
        }
        .expiry-notice {
            color: #ef4444;
            font-size: 14px;
            margin-top: 16px;
        }
        .fallback-link {
            color: #71717a;
            font-size: 12px;
            margin-top: 24px;
            word-break: break-all;
        }
        .fallback-link a {
            color: #667eea;
        }
        .footer {
            color: #a1a1aa;
            font-size: 14px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e4e4e7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Glyph</div>
        <h2>Reset Your Password</h2>
        <p class="message">We received a request to reset your password. Click the button below to create a new password:</p>

        <a href="{{ $resetUrl }}" class="reset-button">Reset Password</a>

        <p class="expiry-notice">This link will expire in {{ $expiresInMinutes }} minutes.</p>

        <p class="fallback-link">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
        </p>

        <p class="footer">
            If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
        </p>
    </div>
</body>
</html>
