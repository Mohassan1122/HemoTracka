<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compliance Request Rejected</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }

        .reason-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }

        .notes-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Compliance Request Rejected</h1>
    </div>
    <div class="content">
        <p>Dear <strong>{{ $organizationName }}</strong>,</p>

        <p>We regret to inform you that your compliance request has been <strong>rejected</strong>.</p>

        <div class="reason-box">
            <strong>Reason for Rejection:</strong>
            <p>{{ $rejectionReason }}</p>
        </div>

        @if($notes)
            <div class="notes-box">
                <strong>Additional Notes:</strong>
                <p>{{ $notes }}</p>
            </div>
        @endif

        <p>If you believe this decision was made in error or would like to submit additional documentation, please
            contact the regulatory body or resubmit your compliance request with the necessary corrections.</p>

        <p>Thank you for your understanding.</p>

        <p>Best regards,<br>HemoTrackr Regulatory Team</p>
    </div>
    <div class="footer">
        <p>This is an automated message from HemoTrackr. Please do not reply directly to this email.</p>
    </div>
</body>

</html>