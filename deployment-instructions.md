# Deployment Issue: SSH Permission Denied

## Problem
The deployment workflow successfully connected to the server via SFTP but authentication failed:
```
Permission denied, please try again.
```

## Solution
The `FTP_PASSWORD` GitHub secret needs to be updated with the correct SSH password for the `debian` user.

### Steps to Fix:

1. **Go to your GitHub repository settings:**
   https://github.com/Hilyamel/UPapp/settings/secrets/actions

2. **Update the `FTP_PASSWORD` secret:**
   - Click on `FTP_PASSWORD`
   - Click "Update secret"
   - Enter the correct SSH password for user `debian` on server `57.131.47.46`
   - Click "Update secret"

3. **Re-run the deployment:**
   - Go to Actions tab
   - Select the failed workflow
   - Click "Re-run jobs"

## Alternative: Use SSH Key Authentication (More Secure)

If you prefer to use SSH keys instead of password:

1. Generate an SSH key pair (if you don't have one)
2. Add the public key to the server: `~/.ssh/authorized_keys`
3. Add the private key as a GitHub secret named `SSH_PRIVATE_KEY`
4. Update the workflow to use `ssh_private_key` instead of `password`

Let me know which approach you prefer and I can help implement it!
