<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedStudy | Log In | Admin</title>
    <link rel="stylesheet" href="../css/loginAdmin.css">
    <link rel="icon" href="../../User/images\logos\medstudyLogo.png">
</head>
<body>
    <div class="container">
        <div class="picholder">
            <img src="../images/logos/doc.png" alt="MedStudy Admin Login Illustration">
        </div>

        <div class="logIn">
            <div class="logInForm">
                <div class="title">
                    <h1>Hello</h1>
                    <h1>Welcome Back!</h1>
                </div>
                
                <form id="loginForm">
                    <h2>
                        <span>Login</span> to your account
                    </h2>
                    
                    <input id="admin" type="email" name="email" placeholder="Email" required>
                    
                    <div class="password-container">
                        <input type="password" id="password" placeholder="Password" required oninput="toggleEyeVisibility()">
                        <img id="eyeIcon" src="../../Admin/images/icons/hidden.png" alt="Toggle Password" onclick="togglePassword()" style="display: none;">
                    </div>    
                    
                    <div id="errorMessage" class="error-message" style="display: none;"></div>
                    
                    <button type="button" onclick="showLoading('dashboard.php')">Log In</button>
                </form>
            </div>
        </div>
    </div>
    
    <!--Loading-->
    <div class="popup" id="loadingPopup">
        <div class="popup-content">
            <p>Please Wait a Moment....</p>
            <img src="../../User/images/logos/medstudyLogo.png" alt="Loading" class="loading-image">
        </div>
    </div>

    <script>
        function showLoading(redirectUrl) {
            let nameField = document.getElementById("admin");
            let passField = document.getElementById("password");
            let name = nameField.value.trim();
            let pass = passField.value.trim();

            // Clear previous error states
            nameField.classList.remove("error");
            passField.classList.remove("error");
            document.getElementById("errorMessage").textContent = "";
            document.getElementById("errorMessage").style.display = "none";

            // Specific login credentials
            const validEmail = "somlibrary@usep.edu.ph";
            const validPassword = "somlibrary";

            // Check login credentials
            if (name === validEmail && pass === validPassword) {
                document.getElementById('loadingPopup').style.display = 'flex';
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 3000);
            } else {
                // Show error for invalid credentials
                if (name !== validEmail) {
                    nameField.classList.add("error");
                }
                if (pass !== validPassword) {
                    passField.classList.add("error");
                }
                
                // Display error message
                const errorMessage = document.getElementById("errorMessage");
                errorMessage.textContent = "Invalid email or password. Please try again.";
                errorMessage.style.display = "block";
            }
        }

        function togglePassword() {
            const passwordField = document.getElementById("password"); 
            const eyeIcon = document.getElementById("eyeIcon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.src = "../../Admin/images/icons/eye.png";
            } else {
                passwordField.type = "password";
                eyeIcon.src = "../../Admin/images/icons/hidden.png";
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

        document.addEventListener("keydown", function(event) {
            if (event.altKey && event.key.toLowerCase() === "a") {
                event.preventDefault();
                window.location.href = "/MedStudy-Space-System/index.php";
            }
        });
    </script>
</body>
</html>