<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .title {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .message-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .footer {
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name', 'ServeWise') }}</div>
            <h1 class="title">{{ $emailSubject }}</h1>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>

            <div class="message-box">
                {!! nl2br(e($emailMessage)) !!}
            </div>

            @if($user->restaurantData)
            <p style="margin-top: 20px;">
                <strong>Your Restaurant:</strong> {{ $user->restaurantData->restaurant_name }}
            </p>
            @endif

            <p style="margin-top: 30px;">
                If you have any questions or concerns, please don't hesitate to contact our support team.
            </p>

            <p>Best regards,<br>
            <strong>{{ config('app.name') }} Administration Team</strong></p>
        </div>

        <div class="footer">
            <p>This email was sent from {{ config('app.name') }} Admin Panel.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
