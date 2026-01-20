<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Contact Form Submission</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f8; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 6px; overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #1f2937; padding: 20px;">
                            <h2 style="margin: 0; color: #ffffff; font-size: 20px;">
                                New Contact Form Submission
                            </h2>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 24px; color: #374151; font-size: 14px; line-height: 1.6;">
                            
                            <p style="margin: 0 0 10px;">
                                You have received a new message via the contact form. Below are the details:
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px;">
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold; width: 120px;">
                                        Name:
                                    </td>
                                    <td style="padding: 8px 0;">
                                        {{ $data['name'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">
                                        Email:
                                    </td>
                                    <td style="padding: 8px 0;">
                                        {{ $data['email'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">
                                        Subject:
                                    </td>
                                    <td style="padding: 8px 0;">
                                        {{ $data['subject'] }}
                                    </td>
                                </tr>
                            </table>

                            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">

                            <p style="margin: 0 0 8px; font-weight: bold;">
                                Message:
                            </p>
                            <p style="margin: 0; white-space: pre-line;">
                                {{ $data['message'] }}
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 16px; text-align: center; font-size: 12px; color: #6b7280;">
                            This email was generated automatically from your website contact form.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
