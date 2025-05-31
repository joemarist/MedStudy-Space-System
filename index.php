<?php
// Ensure database is initialized
$database_error = null;
try {
    require_once 'config.php';

    // Additional check for database initialization
    $init_file = dirname(__FILE__) . '/database_initialized.flag';
    $log_file = dirname(__FILE__) . '/database_init.log';

    // Check if initialization failed
    if (!file_exists($init_file)) {
        // Read the last few lines of the log file to provide more context
        $log_contents = '';
        if (file_exists($log_file)) {
            $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $log_contents = implode("\n", array_slice($log_lines, -5)); // Last 5 lines
        }

        // Set database error message
        $database_error = "Database initialization failed. " . 
            (!empty($log_contents) ? "Log details: " . htmlspecialchars($log_contents) : "");
        
        // Log the initialization failure
        error_log('Database initialization failed. Check log file for details.');
    }
} catch (Exception $e) {
    // Log any unexpected errors during initialization
    error_log('Unexpected error during database initialization: ' . $e->getMessage());
    
    // Set database error message
    $database_error = "An unexpected system error occurred during database initialization.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedStudy | Log In</title>
    <link rel="stylesheet" href="User/css/logIn.css">
    <link rel="icon" href="User/images/logos/medstudyLogo.png">
    <style>
        .database-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .database-error-details {
            background-color: #f1f1f1;
            border-radius: 3px;
            padding: 10px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
            max-height: 150px;
            overflow-y: auto;
            text-align: left;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <?php if ($database_error): ?>
    <div class="database-error">
        <strong>System Error</strong>
        <p>Database initialization failed. Please contact system administrator.</p>
        <?php if (!empty($log_contents)): ?>
        <div class="database-error-details">
            <?php echo $database_error; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="welcomeImage">
            <img src="User/images/logos/medstudyresized.png" alt="">
            <br>
            <br>
            <span class="doctors">
                <img src="User/images/logos/doctors.png" alt="">
            </span>
        </div>
        <div class="logIn">
            <h1>Hello</h1>
            <span><h1>Welcome Back!</h1></span>
            <div class="logInForm">
                <h2>
                    <span>Login</span>
                    your account
                </h2>
                <form method="POST" action="verify.php">
                    <input type="email" name="email" placeholder="Email" required>
                    <br>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Password" oninput="toggleEyeVisibility()" required>
                        <img id="eyeIcon" src="User/images/icons/hidden.png" alt="Toggle Password" onclick="togglePassword()" style="display: none;">
                    </div>                   
                    <br>
                    <a href="#" onclick="openForgetPassOverlay()">forgot password?</a>
                    <br><br>
                    <button type="submit">Log In</button>
                    <br>
                    <p>OR</p>
                    <br>
                    <!-- Google Sign-In Button -->
                    <div id="g_id_onload"
                        data-client_id="80047855417-dfsmenc4jgtr2me0vm4a5tl76s91bf45.apps.googleusercontent.com"
                        data-context="signin"
                        data-ux_mode="redirect"
                        data-login_uri="http://localhost/MedStudy-Space-System/verify.php"
                        data-auto_prompt="false"
                        data-scope="profile email">
                    </div>
                    <div class="g_id_signin"
                        data-type="standard"
                        data-shape="rectangular"
                        data-theme="outline"
                        data-text="signin_with"
                        data-size="large"
                        data-logo_alignment="left"
                        style="display: flex; justify-content: center;">
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="forgetPassOverlay" id="forgetPassOverlay">
        <div class="forgetPassBox">
            <h2>Recover your password</h2>
            <div class="recoverPass" id="recoverPass">
                <p>
                    Please enter your email so that we can send your password.
                </p>
                <input type="email" id="recoveryEmail" placeholder="Email">
                <div class="recoverPassButtons">
                    <button onclick="closeForgetPassOverlay()">Cancel</button>
                    <button onclick="recoverPassword()">Send</button>
                </div>
            </div>
            <div class="sentEmail" id="sentEmail">
                <img src="User/images/icons/sent.png" alt="">
                <p>We have sent your password to your email.</p>
                <br>
                <button onclick="closeSentEmail()">Back</button>
            </div>
        </div>
    </div>

    <!--Loading-->
    <div class="popup" id="loadingPopup">
        <div class="popup-content">
            <p>Please Wait a Moment....</p>
            <img src="User/images/logos/medstudyLogo.png" alt="Loading" class="loading-image">
        </div>
    </div>
    <script>
        function showLoadingPopupAndRedirect() {
        const popup = document.getElementById("loadingPopup");
        if (popup) {
            popup.style.display = "flex";
            setTimeout(() => {
                window.location.href = "User/html/home.php";
            }, 2000);
        }
}

    
        window.onload = function () {
        const params = new URLSearchParams(window.location.search);

        if (params.get("status") === "loading") {
            showLoadingPopupAndRedirect();
        } else if (params.get("status") === "success") {
            showLoadingPopupAndRedirect();
        } else if (params.get("status") === "denied") {
            alert("❌ Only @usep.edu.ph accounts are allowed.");
        } else if (params.get("status") === "invalid") {
            alert("❌ Invalid token or client ID.");
        } else if (params.get("status") === "missing") {
            alert("❌ No token received.");
        }
    };

    </script>
        
    <!--Password Toggle Eye-->
    <script>
        function togglePassword() {
    const passwordField = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.src = "User/images/icons/eye.png";
    } else {
        passwordField.type = "password";
        eyeIcon.src = "User/images/icons/hidden.png";
    }
}

function toggleEyeVisibility() {
    const passwordField = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (passwordField.value.length > 0) {
        eyeIcon.style.display = "block";
    } else {
        eyeIcon.style.display = "none";
    }
}

    </script>

    <!--Overlay Script-->
    <script>
    function openForgetPassOverlay() {
        const overlay = document.getElementById("forgetPassOverlay");
        overlay.style.display = "flex";
        setTimeout(() => {
            overlay.classList.add("active");
        }, 10);
        document.body.style.overflow = "hidden";
    }
    function closeForgetPassOverlay() {
        const overlay = document.getElementById("forgetPassOverlay");
        overlay.classList.remove("active");

        setTimeout(() => {
            overlay.style.display = "none";
        }, 300);
        document.body.style.overflow = "auto";
    }

    function recoverPassword() {
        const emailInput = document.getElementById("recoveryEmail");
        const email = emailInput ? emailInput.value : null;
        const recoverPass = document.getElementById("recoverPass");
        const sentEmail = document.getElementById("sentEmail");

        // Validate input existence
        if (!emailInput) {
            console.error('Email input element not found');
            alert('An error occurred with the email input');
            return;
        }

        // Validate email
        if (!email || !email.trim()) {
            alert("Please enter an email address");
            emailInput.focus();
            return;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert("Please enter a valid email address");
            emailInput.focus();
            return;
        }

        // Prepare data
        const data = new URLSearchParams();
        data.append('email', email);

        // Show loading state
        const sendButton = document.querySelector('.recoverPassButtons button:last-child');
        if (sendButton) {
            sendButton.disabled = true;
            sendButton.textContent = 'Sending...';
        }

        // AJAX request
        fetch('recover_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: data
        })
        .then(response => {
            // Check response status
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Ensure response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }

            // Parse JSON
            return response.json();
        })
        .then(data => {
            // Restore button state
            if (sendButton) {
                sendButton.disabled = false;
                sendButton.textContent = 'Send';
            }

            // Check response
            if (data.success) {
                // Hide recover pass section
                if (recoverPass) {
                    recoverPass.style.display = "none";
                }
                // Show sent email section
                if (sentEmail) {
                    sentEmail.style.display = "block";
                    setTimeout(() => {
                        sentEmail.classList.add("active");
                    }, 10);
                }
                // Show success message
                alert(data.message || 'Password recovery request processed successfully');
            } else {
                // Show error message
                alert(data.message || 'An unknown error occurred');
            }
        })
        .catch(error => {
            // Restore button state
            if (sendButton) {
                sendButton.disabled = false;
                sendButton.textContent = 'Send';
            }

            // Log and show error
            console.error('Recovery Error:', error);
            alert('An error occurred while processing your request');
        });
    }

    /*Succesfully sent email*/
    function openSentEmail() {
        const recoverPass = document.getElementById("recoverPass");
        const sentEmail = document.getElementById("sentEmail");

        if (recoverPass) {
            recoverPass.style.display = "none";
        }
        if (sentEmail) {
            sentEmail.style.display = "block";
            setTimeout(() => {
                sentEmail.classList.add("active");
            }, 10);
        }

        document.body.style.overflow = "hidden";
    }
    function closeSentEmail() {
        closeForgetPassOverlay();

        const recoverPass = document.getElementById("recoverPass");
        const sentEmail = document.getElementById("sentEmail");

        if (recoverPass) {
            recoverPass.style.display = "block";
        }
        if (sentEmail) {
            sentEmail.style.display = "none";
            sentEmail.classList.remove("active");
        }
    }
    </script>

    <!--Function for hotkey switch to Admin-->
    <script>
        document.addEventListener("keydown", function(event) {
        if (event.altKey && event.key.toLowerCase() === "a") {
            event.preventDefault();
            window.location.href = "Admin/html/loginAdmin.php";
        }
        });
    </script>  

    <script src="https://accounts.google.com/gsi/client" async defer></script>

</body>
</html>