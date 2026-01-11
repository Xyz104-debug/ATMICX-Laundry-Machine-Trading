<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATMICX - Unified Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 450px;
            width: 90%;
        }

        .logo {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 25px;
        }

        .role-option {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-option.active {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }

        .role-option.secretary.active {
            background: #10b981;
            border-color: #10b981;
        }

        .role-option i {
            display: block;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .role-option .title {
            font-weight: 600;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #374151;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #4f46e5;
        }

        .login-btn {
            width: 100%;
            background: #4f46e5;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .login-btn:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }

        .login-btn.secretary {
            background: #10b981;
        }

        .login-btn.secretary:hover {
            background: #059669;
        }

        .access-both-btn {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5, #10b981);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .access-both-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: none;
        }

        .success-message {
            background: #d1fae5;
            color: #059669;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: none;
        }

        .divider {
            text-align: center;
            color: #6b7280;
            margin: 20px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            background: white;
            padding: 0 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">ATMICX</div>
        <div class="subtitle">Laundry Machine Trading System</div>

        <div class="role-selector">
            <div class="role-option manager active" onclick="selectRole('manager')">
                <i class="fas fa-user-tie"></i>
                <div class="title">Manager</div>
            </div>
            <div class="role-option secretary" onclick="selectRole('secretary')">
                <i class="fas fa-user-edit"></i>
                <div class="title">Secretary</div>
            </div>
        </div>

        <div class="error-message" id="error-message"></div>
        <div class="success-message" id="success-message"></div>

        <form id="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="manager">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required value="manager123">
            </div>

            <button type="submit" class="login-btn" id="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login as Manager
            </button>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <button type="button" class="access-both-btn" onclick="createUnifiedSession()">
            <i class="fas fa-users-cog"></i> Access Both Manager & Secretary
        </button>
    </div>

    <script>
        let selectedRole = 'manager';

        function selectRole(role) {
            selectedRole = role;
            
            // Update UI
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('active');
            });
            document.querySelector(`.role-option.${role}`).classList.add('active');
            
            // Update form
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('login-btn');
            
            if (role === 'manager') {
                usernameField.value = 'manager';
                passwordField.value = 'manager123';
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login as Manager';
                loginBtn.className = 'login-btn';
            } else {
                usernameField.value = 'secretary';
                passwordField.value = 'secretary123';
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login as Secretary';
                loginBtn.className = 'login-btn secretary';
            }
        }

        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error-message');
            const successDiv = document.getElementById('success-message');
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            try {
                const response = await fetch('role_login_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        role: selectedRole
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    successDiv.textContent = `Logged in as ${selectedRole}!`;
                    successDiv.style.display = 'block';
                    
                    setTimeout(() => {
                        if (selectedRole === 'manager') {
                            window.location.href = 'atmicxMANAGER.php';
                        } else {
                            window.location.href = 'armicxSECRETARY.php';
                        }
                    }, 1000);
                } else {
                    errorDiv.textContent = result.message;
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'Login error: ' + error.message;
                errorDiv.style.display = 'block';
            }
        });

        async function createUnifiedSession() {
            const errorDiv = document.getElementById('error-message');
            const successDiv = document.getElementById('success-message');
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            try {
                // Create both sessions
                const managerResponse = await fetch('role_login_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: 'manager',
                        password: 'manager123',
                        role: 'manager'
                    })
                });
                
                const secretaryResponse = await fetch('role_login_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: 'secretary',
                        password: 'secretary123',
                        role: 'secretary'
                    })
                });
                
                const managerResult = await managerResponse.json();
                const secretaryResult = await secretaryResponse.json();
                
                if (managerResult.success && secretaryResult.success) {
                    successDiv.textContent = 'Unified access created! You can now use both interfaces.';
                    successDiv.style.display = 'block';
                    
                    // Show access options
                    setTimeout(() => {
                        showAccessOptions();
                    }, 1000);
                } else {
                    errorDiv.textContent = 'Failed to create unified access';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'Error: ' + error.message;
                errorDiv.style.display = 'block';
            }
        }

        function showAccessOptions() {
            const container = document.querySelector('.login-container');
            container.innerHTML = `
                <div class="logo">ATMICX</div>
                <div class="subtitle">Access Both Interfaces</div>
                
                <div style="display: grid; gap: 15px;">
                    <a href="atmicxMANAGER.php" style="
                        display: flex;
                        align-items: center;
                        gap: 15px;
                        background: #4f46e5;
                        color: white;
                        padding: 20px;
                        border-radius: 10px;
                        text-decoration: none;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                        <i class="fas fa-chart-line" style="font-size: 24px;"></i>
                        <div>
                            <div style="font-weight: 600; font-size: 16px;">Manager Dashboard</div>
                            <div style="font-size: 14px; opacity: 0.9;">Approve quotes & verify payments</div>
                        </div>
                    </a>
                    
                    <a href="armicxSECRETARY.php" style="
                        display: flex;
                        align-items: center;
                        gap: 15px;
                        background: #10b981;
                        color: white;
                        padding: 20px;
                        border-radius: 10px;
                        text-decoration: none;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                        <i class="fas fa-clipboard-list" style="font-size: 24px;"></i>
                        <div>
                            <div style="font-weight: 600; font-size: 16px;">Secretary Interface</div>
                            <div style="font-size: 14px; opacity: 0.9;">Create quotes & submit to manager</div>
                        </div>
                    </a>
                </div>
            `;
        }
    </script>
</body>
</html>