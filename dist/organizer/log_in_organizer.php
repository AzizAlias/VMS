<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Login</title>
    <link href="../../src/output.css" rel="stylesheet">
    <script src="../../node_modules/flowbite/dist/flowbite.min.js"></script>
    <script>
        // Check if the logout parameter is in the URL
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('logout') && urlParams.get('logout') === 'success') {
                alert("You have successfully logged out.");
            }

            // Check if there is an error message in the URL
            if (urlParams.has('error')) {
                const errorMessage = urlParams.get('error');
                // Display the error message using Flowbite's alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'fixed top-4 right-4 flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = `
                    <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <span class="sr-only">Info</span>
                    <div>${errorMessage}</div>
                `;
                document.body.appendChild(alertDiv);

                // Remove the alert after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }
        }
    </script>
</head>
<body class="bg-white">
<!-- Use inline styles to set the background to cobalt blue (#0047AB) -->
<section style="background-color: #0047AB; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
  <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
      <!-- Add your website logo here (outside the login form container) -->
      <div class="flex justify-center mb-8">
      <img src="../icon/uniserve.png" class="h-12" alt="UniServe Logo" />
      </div>

      <!-- Keep the login form white -->
      <div class="w-full bg-white rounded-lg shadow md:mt-0 sm:max-w-md xl:p-0">
          <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
              <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl text-center">
                  Sign in to your account
              </h1>
              <form class="space-y-4 md:space-y-6" action="login_organizer_process.php" method="POST">
                  <div>
                      <label for="organizer_email" class="block mb-2 text-sm font-medium text-gray-900">Your email</label>
                      <input type="email" name="organizer_email" id="organizer_email" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="email" required="">
                  </div>
                  <div>
                      <label for="organizer_password" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                      <input type="password" name="organizer_password" id="organizer_password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" required="">
                  </div>
                  <div class="flex items-center justify-between">
                      <div class="flex items-start">
                          <div class="flex items-center h-5">
                            <input id="remember" aria-describedby="remember" type="checkbox" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300">
                          </div>
                          <div class="ml-3 text-sm">
                            <label for="remember" class="text-gray-500">Remember me</label>
                          </div>
                      </div>
                      <a href="#" class="text-sm font-medium text-primary-600 hover:underline">Forgot password?</a>
                  </div>
                  <button type="submit" class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Sign in</button>
                  <p class="text-sm font-light text-gray-500 text-center">
                      Don’t have an account yet? <a href="../../dist/organizer/sign-up_organizer.php" class="font-medium text-primary-600 hover:underline">Sign up</a>
                  </p>
              </form>
          </div>
      </div>
  </div>
</section>
</body>
</html>