# Password Generation Guide

**For Symfony Assessment Project**

This guide explains how to generate a new hashed password for the admin user in the Symfony application.

---

## Why Password Hashing?

Symfony stores passwords in a hashed (encrypted) format in `config/packages/security.yaml`. You cannot use plain text passwords directly. Instead, you must generate a bcrypt hash of your desired password and store that hash in the configuration file.

---

## Quick Reference

**Current Admin Credentials:**
- Username: `admin`
- Password: `admin123`
- Hash: `$2y$13$JcQf4AV6W9HRnJolKOgameI9hJPsFAbzQvfAU2EF41j4lxxL4b262`

---

## Step-by-Step Guide

### Step 1: Generate Password Hash

Run the Symfony console command to hash your desired password:

```bash
docker exec lvs_assessment_php php bin/console security:hash-password
```

**Interactive Prompts:**

1. The command will ask for the user class:
   ```
   For which user class would you like to hash a password?
     [0] Symfony\Component\Security\Core\User\InMemoryUser
     [1] Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface
   ```

   **Press Enter** to select the default `[0] Symfony\Component\Security\Core\User\InMemoryUser`

2. Enter your desired password when prompted:
   ```
   Type in your password to be hashed:
   ```

   Type your password (e.g., `myNewPassword123`) and press **Enter**

   *Note: Your input will be hidden for security*

3. The command will output the hash:
   ```
   --------------- -----------------------------------------------------------------
    Key             Value
   --------------- -----------------------------------------------------------------
    Hasher used     Symfony\Component\PasswordHasher\Hasher\MigratingPasswordHasher
    Password hash   $2y$13$ABC123XYZ...
   --------------- -----------------------------------------------------------------
   ```

4. **Copy the password hash** from the output (the long string starting with `$2y$13$`)

---

### Step 2: Update Security Configuration

1. Open the security configuration file:
   ```
   config/packages/security.yaml
   ```

2. Locate the `users` section under `providers`:
   ```yaml
   providers:
       in_memory:
           memory:
               users:
                   admin:
                       password: '$2y$13$OLD_HASH_HERE'
                       roles: ['ROLE_ADMIN']
   ```

3. Replace the old hash with your new hash:
   ```yaml
   providers:
       in_memory:
           memory:
               users:
                   admin:
                       password: '$2y$13$YOUR_NEW_HASH_HERE'
                       roles: ['ROLE_ADMIN']
   ```

4. **Important:** Keep the single quotes around the hash!

5. Save the file

---

### Step 3: Clear Symfony Cache

After updating the security configuration, clear the cache:

```bash
docker exec lvs_assessment_php php bin/console cache:clear
```

**Expected Output:**
```
[OK] Cache for the "dev" environment (debug=true) was successfully cleared.
```

---

### Step 4: Test the New Password

Test that your new password works by making an authenticated API request:

```bash
curl -u admin:YOUR_NEW_PASSWORD http://localhost:8084/api/v1/countries/USA
```

Replace `YOUR_NEW_PASSWORD` with the plain text password you used in Step 1 (not the hash).

**Expected Result:**
- ✅ If successful: Returns country data (status 200)
- ❌ If failed: Returns 401 Unauthorized (check your password or hash)

---

## Complete Example

### Example: Changing Password to "securePass2024"

**Step 1: Generate Hash**
```bash
$ docker exec lvs_assessment_php php bin/console security:hash-password securePass2024

# Output:
Password hash: $2y$13$k7hR8mP4nQ2xV5wL9tJ3eOqA6bC1dE8fG0hI2jK4lM6nO8pR0sT2u
```

**Step 2: Update config/packages/security.yaml**
```yaml
providers:
    in_memory:
        memory:
            users:
                admin:
                    password: '$2y$13$k7hR8mP4nQ2xV5wL9tJ3eOqA6bC1dE8fG0hI2jK4lM6nO8pR0sT2u'
                    roles: ['ROLE_ADMIN']
```

**Step 3: Clear Cache**
```bash
docker exec lvs_assessment_php php bin/console cache:clear
```

**Step 4: Test**
```bash
curl -u admin:securePass2024 http://localhost:8084/api/v1/countries/USA
```

---

## Alternative: Non-Interactive Method

If you want to avoid interactive prompts, you can pass the password as an argument:

```bash
docker exec lvs_assessment_php php bin/console security:hash-password "myPassword123"
```

This will prompt for the user class but not for the password.

---

## Adding Additional Users

To add more users, simply add more entries under the `users` section:

```yaml
providers:
    in_memory:
        memory:
            users:
                admin:
                    password: '$2y$13$HASH_FOR_ADMIN'
                    roles: ['ROLE_ADMIN']
                editor:
                    password: '$2y$13$HASH_FOR_EDITOR'
                    roles: ['ROLE_USER']
                viewer:
                    password: '$2y$13$HASH_FOR_VIEWER'
                    roles: ['ROLE_USER']
```

Each user needs:
- A unique username (e.g., `admin`, `editor`)
- A hashed password
- At least one role

---

## Troubleshooting

### Problem: 401 Unauthorized After Changing Password

**Possible Causes:**
1. Hash not properly copied (missing characters)
2. Forgot to clear the cache
3. YAML syntax error (missing quotes or wrong indentation)

**Solutions:**
1. Regenerate the hash and ensure you copy it completely
2. Clear cache: `docker exec lvs_assessment_php php bin/console cache:clear`
3. Validate YAML syntax - ensure proper indentation and quotes

---

### Problem: "The command is not defined" Error

**Cause:** You might be running the command outside the Docker container

**Solution:** Always prefix with `docker exec lvs_assessment_php`:
```bash
docker exec lvs_assessment_php php bin/console security:hash-password
```

---

### Problem: Hash Doesn't Work

**Check:**
1. Did you copy the entire hash including `$2y$13$`?
2. Did you wrap the hash in single quotes in YAML?
3. Did you clear the cache after updating?
4. Are you using the correct plain password when testing?

---

## Security Best Practices

1. **Never commit plain text passwords** to version control
2. **Use strong passwords** with:
   - At least 12 characters
   - Mix of uppercase and lowercase
   - Numbers and special characters
3. **Change default passwords** before deployment
4. **Don't share passwords** in documentation (use placeholders instead)
5. **Use environment variables** for production passwords (not in-memory provider)

---

## For Production Environments

**Note:** The in-memory user provider is suitable for development and testing only.

For production, use:
- **Database User Provider** (storing users in database)
- **LDAP Provider** (for enterprise authentication)
- **Custom User Provider** (for specific requirements)

See Symfony documentation: https://symfony.com/doc/current/security.html#loading-users

---

## Quick Command Reference

| Task | Command |
|------|---------|
| Generate password hash | `docker exec lvs_assessment_php php bin/console security:hash-password` |
| Clear cache | `docker exec lvs_assessment_php php bin/console cache:clear` |
| Test authentication | `curl -u admin:PASSWORD http://localhost:8084/api/v1/countries/USA` |

---

## Need Help?

- **Symfony Security Docs:** https://symfony.com/doc/current/security.html
- **Password Hashing Docs:** https://symfony.com/doc/current/security/passwords.html
- **Check logs:** `docker-compose logs -f`

---

**Last Updated:** December 19, 2025
