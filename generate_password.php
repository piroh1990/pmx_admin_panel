<?php
/**
 * Password Hash Generator - Web Version
 * 
 * ⚠️ SECURITY WARNING: This file should be DELETED after generating your password hashes!
 * It should NOT be left on a production server.
 */

$hash = '';
$error = '';
$password = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    if (empty($password)) {
        $error = 'Password cannot be empty';
    } elseif (strlen($password) < 8) {
        $error = 'Password should be at least 8 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        // Generate the hash
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Hash Generator</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 600px;
    padding: 40px;
}

.warning {
    background: #fff3cd;
    border: 2px solid #ffc107;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-size: 14px;
}

.warning strong {
    display: block;
    margin-bottom: 5px;
    font-size: 16px;
}

h1 {
    color: #333;
    margin-bottom: 10px;
    font-size: 28px;
}

.subtitle {
    color: #666;
    margin-bottom: 30px;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #333;
    font-weight: 500;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s;
    font-family: monospace;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
}

.error-message {
    background: #fee;
    color: #c33;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    border: 1px solid #fcc;
}

.generate-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.generate-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}

.generate-btn:active {
    transform: translateY(0);
}

.result-box {
    background: #f8f9fa;
    border: 2px solid #28a745;
    border-radius: 8px;
    padding: 20px;
    margin-top: 25px;
}

.result-box h2 {
    color: #28a745;
    font-size: 18px;
    margin-bottom: 15px;
}

.hash-display {
    background: white;
    padding: 15px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    word-break: break-all;
    color: #333;
    border: 1px solid #ddd;
    margin-bottom: 15px;
    position: relative;
}

.copy-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.copy-btn:hover {
    background: #218838;
}

.code-block {
    background: #2d2d2d;
    color: #f8f8f2;
    padding: 15px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    margin-top: 15px;
    overflow-x: auto;
}

.code-block .comment {
    color: #6a9955;
}

.code-block .string {
    color: #ce9178;
}

.footer {
    text-align: center;
    margin-top: 25px;
    color: #999;
    font-size: 12px;
}
</style>
</head>
<body>

<div class="container">
    <div class="warning">
        <strong>⚠️ Security Warning</strong>
        Delete this file after generating your password hashes! It should NOT remain on your production server.
    </div>
    
    <h1>🔐 Password Hash Generator</h1>
    <p class="subtitle">Generate secure bcrypt hashes for your config.php</p>
    
    <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="password">Password</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required 
                minlength="8"
                placeholder="Enter password (min 8 characters)"
                autofocus
            >
        </div>
        
        <div class="form-group">
            <label for="confirm">Confirm Password</label>
            <input 
                type="password" 
                id="confirm" 
                name="confirm" 
                required 
                minlength="8"
                placeholder="Re-enter password"
            >
        </div>
        
        <button type="submit" class="generate-btn">Generate Hash</button>
    </form>
    
    <?php if ($hash): ?>
        <div class="result-box">
            <h2>✓ Hash Generated Successfully!</h2>
            
            <div class="hash-display" id="hashValue">
                <?= htmlspecialchars($hash) ?>
            </div>
            
            <button class="copy-btn" onclick="copyHash()">📋 Copy Hash</button>
            
            <div class="code-block">
<span class="comment">// Add this to your $USERS array in config.php:</span>
'username' => [
    'password_hash' => <span class="string">'<?= htmlspecialchars($hash) ?>'</span>,
    'name' => <span class="string">'Full Name'</span>,
],
            </div>
        </div>
    <?php endif; ?>
    
    <div class="footer">
        Remember to delete this file after use!
    </div>
</div>

<script>
function copyHash() {
    const hashText = document.getElementById('hashValue').textContent.trim();
    navigator.clipboard.writeText(hashText).then(() => {
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = '✓ Copied!';
        btn.style.background = '#218838';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '#28a745';
        }, 2000);
    }).catch(err => {
        alert('Failed to copy: ' + err);
    });
}

// Clear form on successful generation
<?php if ($hash): ?>
document.getElementById('password').value = '';
document.getElementById('confirm').value = '';
<?php endif; ?>
</script>

</body>
</html>
