<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Submitted - BioElectrode AI</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f5f5f5; color: #333; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
        .icon { font-size: 50px; color: #4CAF50; margin-bottom: 20px; }
        h1 { margin-bottom: 15px; color: #333; }
        button { background: #1565C0; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>Request Submitted</h1>
        <p>Your account deletion request for <strong><?php echo htmlspecialchars($_POST['email'] ?? 'your account'); ?></strong> has been successfully submitted for processing.</p>
        <p>A confirm email will be sent within 48 hours once deletion is complete.</p>
        <button onclick="window.location.href='index.php'">Return to Website</button>
    </div>
</body>
</html>
