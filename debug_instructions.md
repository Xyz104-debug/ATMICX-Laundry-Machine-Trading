# How to use the Login Debugging Script

I've created a script to help you debug the "invalid credentials" error. This script will check your database and tell you exactly where the problem is.

## 1. Edit the `debug_login.php` file

Open the `debug_login.php` file in your code editor. You will see a section at the top called "Configuration".

```php
// --- Configuration ---
// Enter the email and password you are trying to log in with.
$test_email = "manager@example.com";
$test_password = "password";
// ---------------------
```

Change the `$test_email` and `$test_password` variables to the exact email and password you are using to log in. For example, if you are trying to log in with the email `myemail@example.com` and the password `mypassword`, you would change the code to this:

```php
// --- Configuration ---
// Enter the email and password you are trying to log in with.
$test_email = "myemail@example.com";
$test_password = "mypassword";
// ---------------------
```

## 2. Run the script

Open your web browser and go to this URL:

`http://localhost/ATMICX-Laundry-Machine-Trading/debug_login.php`

## 3. Interpret the output

The script will give you a report. Here's what the different messages mean:

*   **"Database connection successful."** (Green) - This is good. It means the script was able to connect to your database.
*   **"Database connection failed: ..."** (Red) - This is bad. It means the script could not connect to your database. The error message will tell you what went wrong.
*   **"User found with email: ..."** (Green) - This is good. It means the script found a user with the email you provided.
*   **"User not found with email: ..."** (Red) - This is bad. It means there is no user in your database with the email you provided. You need to register an account with this email address.
*   **"Password verification successful!"** (Green) - This is good. It means the password you provided is correct.
*   **"Password verification failed!"** (Red) - This is bad. It means the password you provided is incorrect.
*   **"Note: If the password hash looks like a plain password..."** - This is a very important note. If you see a plain password in the "Stored Password Hash" field, it means your passwords are not being hashed correctly.

By following these steps, you should be able to figure out why you are getting the "invalid credentials" error.

Once you have finished debugging, please delete the `debug_login.php` and `debug_instructions.md` files.
