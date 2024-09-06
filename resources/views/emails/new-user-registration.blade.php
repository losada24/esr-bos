<div>
    <p>Dear {{ $name }},</p>
    <p>Welcome to {{ config('app.name') }}! We are thrilled to have you on board. Below, you will find your login credentials to access our application:</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    <p>Please note that this password is temporary and it is crucial to change it once you log in for the first time. To enhance the security of your account, follow these steps:</p>
    <ol>
        <li>Go to <a href="https://bos.reylosglass.com/">BOS: Business Operations Suite</a></li>
        <li>Log in to your account</li>
        <li>Click on your profile in the top right corner</li>
        <li>Click on "Profile"</li>
        <li>Scroll down to "Update Password" section</li>
        <li>Enter your current password and your new password</li>
        <li>Click on "Save"</li>
    </ol>
    <p>If you encounter any issues during this process or have any questions, please feel free to reach out to our support team.</p>
    <p>Thank you for choosing {{ config('app.name') }}. We look forward to providing you with a seamless and secure experience.</p>
    <p>Best regards.</p>
</div>