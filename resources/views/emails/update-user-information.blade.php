<div>
    <p>Dear {{ $name }},</p>
    <p>Your account information has been updated successfully.</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    <p>Please note that this password is temporary and it is crucial to change it once you log in for the first time. To enhance the security of your account, follow these steps:</p>
    <ol>
        <li>Go to <a href="https://rgimpactsystem.com/">RG Impact System Manufacturing</a></li>
        <li>Log in to your account</li>
        <li>Click on your profile in the top right corner</li>
        <li>Click on "Profile"</li>
        <li>Scroll down to "Update Password" section</li>
        <li>Enter your current password and your new password</li>
        <li>Click on "Save"</li>
    </ol>
    <p>If you did not request this change, please contact our support team immediately.</p>
    <p>Thank you for choosing {{ config('app.name') }}. We look forward to providing you with a seamless and secure experience.</p>
    <p>Best regards.</p>
</div>