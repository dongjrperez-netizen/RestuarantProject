<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Portal Invitation</title>
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
        .supplier-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .invitation-button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .invitation-button:hover {
            background-color: #0056b3;
        }
        .footer {
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
        .url-fallback {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            margin: 15px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name', 'Restaurant Management System') }}</div>
            <h1 class="title">Supplier Portal Invitation</h1>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $supplier->supplier_name }}</strong>,</p>

            <p>You have been invited to join the supplier portal for <strong>{{ $restaurant->restaurantData->restaurant_name }}</strong>. This portal will allow you to:</p>

            <ul>
                <li>📦 View and manage purchase orders</li>
                <li>📊 Track order history and status</li>
                <li>💰 Submit and manage invoices</li>
                <li>📞 Communicate directly with the restaurant</li>
                <li>📈 Access sales reports and analytics</li>
            </ul>

            <div class="supplier-info">
                <h3>Your Supplier Information:</h3>
                <p><strong>Company:</strong> {{ $supplier->supplier_name }}</p>
                <p><strong>Email:</strong> {{ $supplier->email }}</p>
                @if($supplier->contact_number)
                <p><strong>Phone:</strong> {{ $supplier->contact_number }}</p>
                @endif
                @if($supplier->address)
                <p><strong>Address:</strong> {{ $supplier->address }}</p>
                @endif
            </div>

            <p>Click the button below to access your supplier portal and complete your registration:</p>

            <div style="text-align: center;">
                <a href="{{ $invitationUrl }}" class="invitation-button">
                    Access Supplier Portal
                </a>
            </div>

            <p><small>If the button doesn't work, copy and paste this URL into your browser:</small></p>
            <div class="url-fallback">
                {{ $invitationUrl }}
            </div>

            <p>This invitation is specifically for your company and should not be shared with others. If you have any questions or need assistance, please contact the restaurant directly.</p>

            <p>We look forward to working with you!</p>

            <p>Best regards,<br>
            <strong>{{ $restaurant->restaurantData->restaurant_name }}</strong><br>
            Restaurant Management Team</p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>