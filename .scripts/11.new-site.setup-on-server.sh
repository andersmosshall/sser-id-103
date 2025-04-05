#!/bin/bash

# ==============================================================================
# Website Setup and Deployment Script
#
# Purpose: Automates the initial setup and deployment of a specific Drupal
#          site structure on a server. Includes checks, user confirmations
#          for manual steps, configuration management, and deployment commands.
# Version: 5.0 (Restored full readability/verbosity)
# ==============================================================================

# --- Configuration Variables ---

# Path to the main production settings file used as a source/template
SETTINGS_FILE=".settings/prod/settings.local.php"

# Source paths for settings files to be copied into web/sites/default
PROD_SETTINGS_LOCAL_SRC=".settings/prod/settings.local.php"
PROD_SETTINGS_COMMON_LOCAL_SRC=".settings/prod/settings.common-local.php"

# Drupal's default site directory path
DEFAULT_SITES_DIR="web/sites/default"

# Destination paths for core Drupal and custom settings files within sites/default
DEFAULT_SETTINGS_PHP="$DEFAULT_SITES_DIR/settings.php"
DEFAULT_SETTINGS_LOCAL_DEST="$DEFAULT_SITES_DIR/settings.local.php"
DEFAULT_SETTINGS_COMMON_LOCAL_DEST="$DEFAULT_SITES_DIR/settings.common-local.php"
DEFAULT_SETTINGS_COMMON_SECRETS_DEST="$DEFAULT_SITES_DIR/settings.common-secrets.php"
DEFAULT_SETTINGS_LOCAL_SECRETS_DEST="$DEFAULT_SITES_DIR/settings.local-secrets.php"

# Drupal's configuration synchronization directory path
CONFIG_SYNC_DIR="config/sync"
CONFIG_SITE_FILE="$CONFIG_SYNC_DIR/system.site.yml"
CONFIG_THEME_FILE="$CONFIG_SYNC_DIR/system.theme.global.yml"

# Drupal's private files directory path
PRIVATE_FILES_DIR="web/sites/default/private-files"

# Directory and file for storing the site's encryption key
KEY_DIR=".encryption_key"
KEY_FILE="$KEY_DIR/encrypt.key"

# ==============================================================================
# --- Helper Functions ---
# ==============================================================================

# Function: error_exit
# Description: Prints an error message to stderr and exits the script with status 1.
# Arguments: $1: Error message string.
error_exit() {
    echo "" # Ensure message is on a new line
    echo "ERROR: $1" >&2
    echo "Script aborted." >&2
    exit 1
}

# Function: extract_setting
# Description: Extracts a string value (single-quoted) from $settings array in PHP file.
# Arguments: $1: Setting key name. $2: Path to settings file.
# Output: Extracted value or empty string.
extract_setting() {
    local setting_name="$1"; local file="$2"; local line; local value
    line=$(grep -E "^\s*\\\$settings\['$setting_name'\]\s*=\s*'.*'\s*;" "$file")
    if [ -z "$line" ]; then echo ""; return; fi
    value=$(echo "$line" | sed -E "s/^\s*\\\$settings\['$setting_name'\]\s*=\s*'([^']*)'\s*;/\1/")
    echo "$value"
}

# Function: extract_setting_unquoted
# Description: Extracts a non-quoted value (numeric/constant) from $settings array.
# Arguments: $1: Setting key name. $2: Path to settings file.
# Output: Extracted value or empty string.
extract_setting_unquoted() {
    local setting_name="$1"; local file="$2"; local line; local value
    line=$(grep -E "^\s*\\\$settings\['$setting_name'\]\s*=[^'].*;" "$file")
     if [ -z "$line" ]; then echo ""; return; fi
    value=$(echo "$line" | sed -E "s/^\s*\\\$settings\['$setting_name'\]\s*=\s*([^;]*)\s*;/\1/")
    value=$(echo "$value" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//') # Trim whitespace
    echo "$value"
}

# ==============================================================================
# --- Main Script Execution ---
# ==============================================================================

echo "Starting website setup script..."
echo "Current directory: $(pwd)"
echo "---"

# --- Step 1: Verify Git Repository ---
echo "[Step 1/35] Verifying Git repository..."
# Check if the .git directory exists
if [ ! -d ".git" ]; then
    error_exit "Current directory is not a Git repository ('.git' directory not found)."
fi

# Attempt to get the URL of the 'origin' remote
git_url=$(git remote get-url origin 2>/dev/null) # Suppress git errors
if [ $? -ne 0 ] || [ -z "$git_url" ]; then
    error_exit "Could not determine Git repository URL. Is 'origin' remote configured?"
fi
echo "Git repository found: $git_url"
echo "---"

# --- Step 2: Git Pull ---
echo "[Step 2/35] Pulling latest changes from Git repository..."
# Get the latest code from the default remote/branch
git pull
if [ $? -ne 0 ]; then
    error_exit "git pull failed. Check connection, permissions, and local changes."
fi
echo "Git pull successful."
echo "---"

# --- Step 3: Verify Source Settings File Exists ---
echo "[Step 3/35] Verifying source settings file: $SETTINGS_FILE..."
# Check if the main settings template file exists after pulling
if [ ! -f "$SETTINGS_FILE" ]; then
    error_exit "Source settings file not found at '$SETTINGS_FILE'. Did git pull remove it?"
fi
echo "Source settings file found."
echo "---"

# --- Step 4: Extract Variables from Settings File ---
echo "[Step 4/35] Extracting required settings from $SETTINGS_FILE..."
# Extract key configuration values needed later in the script
school_name=$(extract_setting 'ssr_school_name' "$SETTINGS_FILE")
bug_email=$(extract_setting 'ssr_bug_report_email' "$SETTINGS_FILE")
noreply_email=$(extract_setting 'ssr_no_reply_email' "$SETTINGS_FILE")
ssr_id=$(extract_setting_unquoted 'ssr_id' "$SETTINGS_FILE")

# Validate extraction results
error_flag=0
if [ -z "$school_name" ]; then echo "ERROR: Cannot find ssr_school_name" >&2; error_flag=1; fi
if [ -z "$bug_email" ]; then echo "ERROR: Cannot find ssr_bug_report_email" >&2; error_flag=1; fi
if [ -z "$noreply_email" ]; then echo "ERROR: Cannot find ssr_no_reply_email" >&2; error_flag=1; fi
if [ -z "$ssr_id" ] && [ "$ssr_id" != "0" ]; then echo "ERROR: Cannot find ssr_id" >&2; error_flag=1; fi # Allow '0'
if [ $error_flag -ne 0 ]; then
    error_exit "Failed extracting required settings from '$SETTINGS_FILE'."
fi

# Display extracted settings for confirmation
echo "Successfully extracted settings:"
echo "  School Name:      $school_name"
echo "  Bug Report Email: $bug_email"
echo "  No Reply Email:   $noreply_email"
echo "  SSR ID:           $ssr_id"
echo "---"

# --- Step 5: Initial Confirmation Prompt ---
echo "[Step 5/35] Requesting initial setup confirmation..."
# Confirm prerequisites and context with the user
echo "========================================================================"
echo "PLEASE CONFIRM THE FOLLOWING BEFORE PROCEEDING:"
echo "========================================================================"
echo "1. Correct Directory: Script is running in the intended deployment directory:"
echo "   '$(pwd)'"
echo "2. Correct Association: This directory/repo ('$git_url')"
echo "   is for the school: '$school_name'"
echo "3. Database Ready: A database with credentials has been prepared on this server."
echo "4. Parallel Terminal: You have another SSH terminal open to this directory"
echo "   ('$(pwd)') ready for potential parallel tasks."
echo "========================================================================"

# Loop for y/n confirmation
confirm_setup=""
while [[ "$confirm_setup" != "y" && "$confirm_setup" != "n" ]]; do
    read -p "Proceed with setup based on these confirmations? (y/n): " confirm_setup
    confirm_setup=$(echo "$confirm_setup" | tr '[:upper:]' '[:lower:]')
done
if [[ "$confirm_setup" != "y" ]]; then error_exit "Setup aborted by user."; fi
echo "Setup confirmation received."
echo "---"

# --- Step 6: Confirm Extracted SSR ID ---
echo "[Step 6/35] Confirming extracted SSR ID '$ssr_id'..."
# Specifically confirm the extracted school ID
confirm_ssr_id=""
while [[ "$confirm_ssr_id" != "y" && "$confirm_ssr_id" != "n" ]]; do
    read -p "Is the extracted SSR ID '$ssr_id' correct? [Y/n]: " confirm_ssr_id_input
    confirm_ssr_id=${confirm_ssr_id_input:-y} # Default to 'y'
    confirm_ssr_id=$(echo "$confirm_ssr_id" | tr '[:upper:]' '[:lower:]')
done
if [[ "$confirm_ssr_id" != "y" ]]; then error_exit "SSR ID confirmation denied by user."; fi
echo "SSR ID confirmed."
echo "---"

# --- Step 7: Check Lando Env & Determine Composer/Drush Commands ---
echo "[Step 7/35] Checking for Lando environment & confirming commands..."
# Ask if running within Lando
is_lando=""
while [[ "$is_lando" != "y" && "$is_lando" != "n" ]]; do
    read -p "Is this script running inside a Lando environment? [y/N]: " is_lando_input
    is_lando=${is_lando_input:-n} # Default 'n'
    is_lando=$(echo "$is_lando" | tr '[:upper:]' '[:lower:]')
done

# Initialize command variables
COMPOSER_CMD=""
DRUSH_CMD=""

if [[ "$is_lando" == "y" ]]; then
    # --- Lando Environment: Use standard Lando wrappers ---
    COMPOSER_CMD="lando composer"
    DRUSH_CMD="lando drush"
    echo "Lando mode enabled. Using standard Lando commands:"
    echo "  Composer: $COMPOSER_CMD"
    echo "  Drush:    $DRUSH_CMD"

else
    # --- Standard Environment: Confirm command paths with user ---
    echo "Running in standard environment."
    echo "Please confirm (or correct) the full commands to use for Composer and Drush."
    echo "Hint: Check aliases ('alias composer') or paths ('which composer', 'command -v php')"
    echo "      in your interactive terminal if unsure."
    echo ""

    # Define default commands using $HOME (safer than ~ in scripts)
    DEFAULT_COMPOSER_CMD="/usr/bin/php \"$HOME/composer.phar\""
    DEFAULT_DRUSH_CMD="/usr/bin/php \"$HOME/.config/composer/vendor/bin/drush\""

    # Confirm Composer command
    read -e -p "Enter Composer command: " -i "$DEFAULT_COMPOSER_CMD" COMPOSER_CMD_INPUT
    # Trim potential surrounding quotes
    COMPOSER_CMD=$(echo "$COMPOSER_CMD_INPUT" | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")
    if [ -z "$COMPOSER_CMD" ]; then error_exit "Composer command cannot be empty."; fi
    echo "Using Composer command: '$COMPOSER_CMD'"
    echo ""

    # Confirm Drush command
    read -e -p "Enter Drush command: " -i "$DEFAULT_DRUSH_CMD" DRUSH_CMD_INPUT
    # Trim potential surrounding quotes
    DRUSH_CMD=$(echo "$DRUSH_CMD_INPUT" | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")
     if [ -z "$DRUSH_CMD" ]; then error_exit "Drush command cannot be empty."; fi
    echo "Using Drush command: '$DRUSH_CMD'"

fi # End Lando check

echo "Command setup complete."
echo "---"

# ==============================================================================
# --- Use $COMPOSER_CMD and $DRUSH_CMD for subsequent calls ---
# ==============================================================================

# --- Step 8: Run Composer Install ---
echo "[Step 8/35] Running Composer install..."
# Use the determined $COMPOSER_CMD variable
$COMPOSER_CMD install --no-dev --optimize-autoloader # Use prod flags
if [ $? -ne 0 ]; then error_exit "Composer install failed using: '$COMPOSER_CMD'."; fi
echo "Composer install completed successfully."
echo "---"

# --- Step 9: Ensure Private Files Directory Exists ---
echo "[Step 9/35] Ensuring private files directory exists: $DEFAULT_SITES_DIR/private-files"
mkdir -p "$DEFAULT_SITES_DIR/private-files"
if [ $? -ne 0 ]; then error_exit "Failed creating directory '$DEFAULT_SITES_DIR/private-files'."; fi
echo "Directory ensured: '$DEFAULT_SITES_DIR/private-files'."
echo "---"

# --- Step 10: Generate/Verify Encryption Key ---
echo "[Step 10/35] Checking encryption key: $KEY_FILE..."
if [ -f "$KEY_FILE" ]; then
    echo "Encryption key already exists. Using existing key."
    if [ ! -s "$KEY_FILE" ]; then error_exit "Existing key file '$KEY_FILE' is empty."; fi
else
    echo "Generating new encryption key..."
    mkdir -p "$KEY_DIR"; if [ $? -ne 0 ]; then error_exit "Failed creating key dir '$KEY_DIR'."; fi
    temp_key_file=$(mktemp "${KEY_DIR}/encrypt.key.XXXXXX")
    if ! dd if=/dev/urandom bs=32 count=1 2>/dev/null | base64 -i - > "$temp_key_file"; then rm -f "$temp_key_file" >/dev/null 2>&1; error_exit "Failed key generation."; fi
    if [ ! -s "$temp_key_file" ]; then rm -f "$temp_key_file" >/dev/null 2>&1; error_exit "Generated key file empty."; fi
    if ! mv "$temp_key_file" "$KEY_FILE"; then rm -f "$temp_key_file" >/dev/null 2>&1; error_exit "Failed moving key to '$KEY_FILE'."; fi
    echo "New encryption key generated: '$KEY_FILE'."
fi
echo "Encryption key handling complete."
echo "---"

# --- Step 11: Prompt User to Backup Encryption Key ---
echo "[Step 11/35] Prompting for key backup confirmation..."
echo "================ Encryption Key Backup Required ================"
echo "Key Content:"
echo ""
cat "$KEY_FILE"
echo ""
echo "IMPORTANT: Copy the key above NOW and store it securely!"
echo "================================================================"
confirm_backup=""; while [[ "$confirm_backup" != "y" && "$confirm_backup" != "n" ]]; do read -p "Have you backed up the key? (y/n): " confirm_backup; confirm_backup=$(echo "$confirm_backup" | tr '[:upper:]' '[:lower:]'); done
if [[ "$confirm_backup" != "y" ]]; then error_exit "Aborted. Please back up the key."; fi
echo "Key backup confirmed."
echo "---"

# --- Step 12: Manual Step - Configure Web Server Document Root ---
echo "[Step 12/35] Instructing user on Document Root config..."
echo "================ Manual Step 1: Configure Web Server ================"
echo "Action Required: Configure your web server for '$school_name'."
echo "Set the Document Root to point to:"
echo "  -> $(pwd)/web"
echo "(This script will NOT perform this step.)"
echo "====================================================================="
echo "---"

# --- Step 13: Manual Step - Perform Drupal Web Installation ---
echo "[Step 13/35] Instructing user on Drupal web install..."
echo "================ Manual Step 2: Drupal Web Installation ================"
echo "Action Required: Visit the site URL for '$school_name' in your browser."
echo "Follow Drupal installation steps using:"
echo "  - Language: English, Profile: Minimal"
echo "  - DB Prefix: 'ss${ssr_id}_'"
echo "  - Site Name: '$school_name'"
echo "  - Site Email: '$noreply_email', User 1 Email: '$bug_email' (suggested)"
echo "  - User 1 Pass: STRONG & UNIQUE (save it!)"
echo "(This script will NOT perform this step.)"
echo "======================================================================"
echo "---"

# --- Step 14: Confirmation of Manual Docroot/Install Steps ---
echo "[Step 14/35] Confirming manual steps completion..."
echo "Confirmation Needed:"
echo "  1. Web server Document Root configured to '$(pwd)/web'?"
echo "  2. Drupal web installation successfully completed?"
echo "---"
confirm_manual=""; while [[ "$confirm_manual" != "y" && "$confirm_manual" != "n" ]]; do read -p "Both steps complete? (y/n): " confirm_manual; confirm_manual=$(echo "$confirm_manual" | tr '[:upper:]' '[:lower:]'); done
if [[ "$confirm_manual" != "y" ]]; then error_exit "Aborted. Please complete manual steps."; fi
echo "Manual steps confirmed."
echo "---"

# --- Step 15: Make Settings Directory/File Writable ---
echo "[Step 15/35] Temporarily making settings directory/file writable..."
chmod +w "$DEFAULT_SITES_DIR" "$DEFAULT_SETTINGS_PHP"
if [ $? -ne 0 ]; then error_exit "Failed making settings dir/file writable."; fi
echo "Permissions updated temporarily."
echo "---"

# --- Step 16: Manual Step - Edit Drupal settings.php ---
echo "[Step 16/35] Instructing user to edit settings.php..."
echo "================ Manual Step 3: Edit settings.php ================"
echo "Action Required: In your other terminal, edit: $DEFAULT_SETTINGS_PHP"
echo "Make these changes:"
echo " 1. Add: 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_swedish_ci' to DB array."
echo " 2. Backup \$databases['default']['default'] array securely."
echo " 3. DELETE the \$settings['config_sync_directory'] = ... line."
echo " 4. UNCOMMENT the 'settings.local.php' include block near the end."
echo "SAVE the file."
echo "(This script will NOT perform this step.)"
echo "=================================================================="
echo "---"

# --- Step 17: Confirmation for settings.php Edits ---
echo "[Step 17/35] Confirming settings.php edits..."
confirm_settings_edit=""; while [[ "$confirm_settings_edit" != "y" && "$confirm_settings_edit" != "n" ]]; do read -p "Completed edits to $DEFAULT_SETTINGS_PHP? (y/n): " confirm_settings_edit; confirm_settings_edit=$(echo "$confirm_settings_edit" | tr '[:upper:]' '[:lower:]'); done
if [[ "$confirm_settings_edit" != "y" ]]; then error_exit "Aborted. Please complete edits."; fi
echo "settings.php edits confirmed."
echo "---"

# --- Step 18: Copy Production Settings Files ---
echo "[Step 18/35] Copying production settings files..."
echo "Copying '$PROD_SETTINGS_LOCAL_SRC' to '$DEFAULT_SETTINGS_LOCAL_DEST'..."
cp "$PROD_SETTINGS_LOCAL_SRC" "$DEFAULT_SETTINGS_LOCAL_DEST"; if [ $? -ne 0 ]; then error_exit "Failed copy local settings."; fi
echo "Copying '$PROD_SETTINGS_COMMON_LOCAL_SRC' to '$DEFAULT_SETTINGS_COMMON_LOCAL_DEST'..."
cp "$PROD_SETTINGS_COMMON_LOCAL_SRC" "$DEFAULT_SETTINGS_COMMON_LOCAL_DEST"; if [ $? -ne 0 ]; then error_exit "Failed copy common local settings."; fi
echo "Settings files copied."
echo "---"

# --- Step 19: Ensure Secrets Files Exist ---
echo "[Step 19/35] Ensuring secrets files exist..."
echo "Checking/creating '$DEFAULT_SETTINGS_COMMON_SECRETS_DEST'..."
if [ -f "$DEFAULT_SETTINGS_COMMON_SECRETS_DEST" ]; then echo "Exists."; else printf "<?php\n\n" > "$DEFAULT_SETTINGS_COMMON_SECRETS_DEST"; if [ $? -ne 0 ]; then error_exit "Failed create common secrets."; fi; echo "Created."; fi
echo ""
echo "Checking/creating '$DEFAULT_SETTINGS_LOCAL_SECRETS_DEST'..."
if [ -f "$DEFAULT_SETTINGS_LOCAL_SECRETS_DEST" ]; then echo "Exists."; else printf "<?php\n\n" > "$DEFAULT_SETTINGS_LOCAL_SECRETS_DEST"; if [ $? -ne 0 ]; then error_exit "Failed create local secrets."; fi; echo "Created."; fi
echo "Secrets files ensured."
echo "---"

# --- Step 20: Manual Step - Populate Secrets Files ---
echo "[Step 20/35] Instructing user to populate secrets files..."
echo "================ Manual Step 4: Populate Secrets Files ================"
echo "Action Required: Edit these files in your other terminal:"
echo "  -> $DEFAULT_SETTINGS_COMMON_SECRETS_DEST"
echo "  -> $DEFAULT_SETTINGS_LOCAL_SECRETS_DEST"
echo "Add necessary PHP secrets (DB creds, API keys, etc.) from Bitwarden/docs."
echo "SAVE both files."
echo "(This script will NOT perform this step.)"
echo "====================================================================="
echo "---"

# --- Step 21: Confirmation for Secret File Population ---
echo "[Step 21/35] Confirming secrets population..."
confirm_secrets=""; while [[ "$confirm_secrets" != "y" && "$confirm_secrets" != "n" ]]; do read -p "Populated secrets files? (y/n): " confirm_secrets; confirm_secrets=$(echo "$confirm_secrets" | tr '[:upper:]' '[:lower:]'); done
if [[ "$confirm_secrets" != "y" ]]; then error_exit "Aborted. Please populate secrets."; fi
echo "Secrets population confirmed."
echo "---"

# --- Step 22: Drush Config Export ---
echo "[Step 22/35] Exporting configuration (drush cex)..."
$DRUSH_CMD cex -y
if [ $? -ne 0 ]; then error_exit "Drush cex failed using: '$DRUSH_CMD'"; fi
echo "Config export complete."
echo "---"

# --- Step 23: Selective Git Add, Checkout, Clean ---
echo "[Step 23/35] Staging core config & cleaning workdir..."
echo "Staging '$CONFIG_SITE_FILE'..."
git add "$CONFIG_SITE_FILE"; if [ $? -ne 0 ]; then error_exit "Failed git add $CONFIG_SITE_FILE."; fi
echo "Staging '$CONFIG_THEME_FILE'..."
git add "$CONFIG_THEME_FILE"; if [ $? -ne 0 ]; then error_exit "Failed git add $CONFIG_THEME_FILE."; fi
echo "Resetting other working directory changes ('checkout .')..."
git checkout .; if [ $? -ne 0 ]; then error_exit "Failed git checkout."; fi
echo "Removing untracked files ('clean -fd')..."
git clean -fd; if [ $? -ne 0 ]; then error_exit "Failed git clean."; fi
echo "Workdir cleaned."
echo "---"

# --- Step 24: Modify system.site.yml Language Codes ---
echo "[Step 24/35] Modifying lang codes in $CONFIG_SITE_FILE to sv..."
if [ ! -f "$CONFIG_SITE_FILE" ]; then error_exit "'$CONFIG_SITE_FILE' not found."; fi
sed -i -E "s/^(langcode:[[:space:]]*)en/\1sv/" "$CONFIG_SITE_FILE"; if [ $? -ne 0 ]; then error_exit "Failed update langcode."; fi
sed -i -E "s/^(default_langcode:[[:space:]]*)en/\1sv/" "$CONFIG_SITE_FILE"; if [ $? -ne 0 ]; then error_exit "Failed update default_langcode."; fi
echo "Lang codes updated."
echo "---"

# --- Step 25: Git Add Modified system.site.yml ---
echo "[Step 25/35] Staging updated $CONFIG_SITE_FILE..."
git add "$CONFIG_SITE_FILE"; if [ $? -ne 0 ]; then error_exit "Failed git add updated $CONFIG_SITE_FILE."; fi
echo "File staged."
echo "---"

# --- Step 26: Show Git Diff and Confirm ---
echo "[Step 26/35] Displaying staged changes for review..."
echo "========================= DIFF START ========================="
git diff --cached
echo "========================== DIFF END =========================="
echo "Please review staged changes (should primarily be langcode)."
echo ""
confirm_diff=""; while [[ "$confirm_diff" != "y" && "$confirm_diff" != "n" ]]; do read -p "Staged changes OK? (y/n): " confirm_diff; confirm_diff=$(echo "$confirm_diff" | tr '[:upper:]' '[:lower:]'); done
if [[ "$confirm_diff" != "y" ]]; then error_exit "Aborted based on diff review."; fi
echo "Diff confirmed."
echo "---"

# --- Step 27: Git Commit and Push ---
echo "[Step 27/35] Committing and pushing config changes..."
echo "Committing..."
git commit -m "site config updates"; if [ $? -ne 0 ]; then error_exit "Commit failed."; fi
echo "Pushing..."
git push origin HEAD; if [ $? -ne 0 ]; then error_exit "Push failed."; fi
echo "Commit and Push successful."
echo "---"

# --- Step 28: Manual Step - Database Backup Prompt ---
echo "[Step 28/35] Instructing user on database backup..."
echo "================ Manual Step 5: Database Backup ================"
echo "Action Required: Take a manual database dump NOW before proceeding!"; echo ""
echo "(e.g., '$DRUSH_CMD sql-dump --result-file=backup_$(date +%F_%T).sql.gz --gzip')"; echo "================================================================"
echo "(No action taken by script)"; echo "---"

# --- Step 29: Confirmation for Database Backup ---
echo "[Step 29/35] Confirming database backup..."
confirm_db_dump=""; while [[ "$confirm_db_dump" != "y" && "$confirm_db_dump" != "n" ]]; do read -p "Database backup taken? (y/n): " confirm_db_dump; confirm_db_dump=$(echo "$confirm_db_dump" | tr '[:upper:]' '[:lower:]'); done
if [[ "$confirm_db_dump" != "y" ]]; then error_exit "Aborted. Please take backup."; fi
echo "DB backup confirmed."
echo "---"

# --- Step 30: Run Drush Updatedb (First Time) ---
echo "[Step 30/35] Running database updates (1st)..."
$DRUSH_CMD updb -y
if [ $? -ne 0 ]; then error_exit "First drush updb failed using: '$DRUSH_CMD'"; fi
echo "DB updates complete."
echo "---"

# --- Step 31: Run Drush Config Import (Twice) ---
echo "[Step 31/35] Running config import (1st)..."
$DRUSH_CMD cim -y
if [ $? -ne 0 ]; then echo "WARNING: First drush cim reported errors. Continuing..."; else echo "First cim complete."; fi
echo "---"
echo "[Step 31/35] Running config import (2nd)..."
$DRUSH_CMD cim -y
if [ $? -ne 0 ]; then error_exit "Second drush cim failed using: '$DRUSH_CMD'"; fi
echo "Second cim complete."
echo "---"

# --- Step 32: Run Drush Updatedb (Second Time) ---
echo "[Step 32/35] Running database updates (2nd)..."
$DRUSH_CMD updb -y
if [ $? -ne 0 ]; then error_exit "Second drush updb failed using: '$DRUSH_CMD'"; fi
echo "DB updates complete."
echo "---"

# --- Step 33: Configuration Sanity Check ---
echo "[Step 33/35] Running configuration sanity check..."
echo "Running drush cex for check..."; $DRUSH_CMD cex -y; if [ $? -ne 0 ]; then echo "WARNING: drush cex during check reported errors."; fi
echo "Checking for config diffs in '$CONFIG_SYNC_DIR' vs HEAD..."
if ! git diff --quiet HEAD -- "$CONFIG_SYNC_DIR"; then
    echo "WARNING: Config difference detected post-deploy!"
    diff_dir="deploy-diff"; diff_filename="config_diff_$(date +%Y%m%d_%H%M%S).txt"; diff_file="$diff_dir/$diff_filename"
    mkdir -p "$diff_dir"; if [ ! -f "$diff_dir/.gitignore" ]; then printf "*\n" > "$diff_dir/.gitignore"; fi
    echo "Saving diff to: $diff_file"; git diff HEAD -- "$CONFIG_SYNC_DIR" > "$diff_file"
    echo "Differing files in '$CONFIG_SYNC_DIR':"; git diff --name-only HEAD -- "$CONFIG_SYNC_DIR" | sed 's/^/  - /'
    echo "ACTION: Review '$diff_file' & contact maintainer."; echo "---"
    echo "Resetting '$CONFIG_SYNC_DIR' to match HEAD..."; git checkout HEAD -- "$CONFIG_SYNC_DIR"; if [ $? -ne 0 ]; then error_exit "Failed checkout config."; fi
    git clean -fd -- "$CONFIG_SYNC_DIR"; if [ $? -ne 0 ]; then error_exit "Failed clean config."; fi; echo "Reset complete."
else
    echo "Config check: No unexpected changes found."
fi
echo "Sanity check complete."
echo "---"

# --- Step 34: Full Deployment Steps ---
echo "[Step 34/35] Performing final deployment steps (Standard Sequence)..."
# This block runs the standard deployment sequence.
# Comments are included for maintainability.

echo "Deploy Step 1/13: Enabling maintenance mode..."
$DRUSH_CMD state:set system.maintenance_mode 1 --input-format=integer; if [ $? -ne 0 ]; then error_exit "Deploy: Failed maint mode ON."; fi
echo ""

echo "Deploy Step 2/13: Pulling latest code..."
git pull; if [ $? -ne 0 ]; then error_exit "Deploy: Git pull failed."; fi
echo ""

echo "Deploy Step 3/13: Setting permissions & Copying Settings..."
# Make dir writable for deploy tasks
chmod +w "$DEFAULT_SITES_DIR/"; if [ $? -ne 0 ]; then error_exit "Deploy: Failed chmod +w sites."; fi
# Copy settings files (overwriting existing)
echo "  Copying settings..."
cp "$PROD_SETTINGS_LOCAL_SRC" "$DEFAULT_SETTINGS_LOCAL_DEST"; if [ $? -ne 0 ]; then error_exit "Deploy: Failed copy local settings."; fi
cp "$PROD_SETTINGS_COMMON_LOCAL_SRC" "$DEFAULT_SETTINGS_COMMON_LOCAL_DEST"; if [ $? -ne 0 ]; then error_exit "Deploy: Failed copy common local settings."; fi
# Make local settings read-only again
chmod -w "$DEFAULT_SETTINGS_LOCAL_DEST"; if [ $? -ne 0 ]; then error_exit "Deploy: Failed chmod -w local settings."; fi
echo ""

echo "Deploy Step 4/13: Running composer install..."
$COMPOSER_CMD install --no-dev --optimize-autoloader; if [ $? -ne 0 ]; then error_exit "Deploy: Composer install failed."; fi
echo ""

echo "Deploy Step 5/13: Clearing caches (post-composer)..."
$DRUSH_CMD cr; if [ $? -ne 0 ]; then error_exit "Deploy: drush cr failed."; fi
echo ""

echo "Deploy Step 6/13: Running drush deploy (1st time)..."
$DRUSH_CMD deploy -y; if [ $? -ne 0 ]; then error_exit "Deploy: First deploy failed."; fi
echo ""

echo "Deploy Step 7/13: Running drush deploy (2nd time)..."
$DRUSH_CMD deploy -y; if [ $? -ne 0 ]; then error_exit "Deploy: Second deploy failed."; fi
echo ""

echo "Deploy Step 8/13: Checking/Updating translations..."
$DRUSH_CMD locale-check # Check first
$DRUSH_CMD locale-update; if [ $? -ne 0 ]; then echo "WARNING: locale-update issues."; fi # Update, warn on fail
echo ""

echo "Deploy Step 9/13: Clearing caches (post-deploy)..."
$DRUSH_CMD cr; if [ $? -ne 0 ]; then error_exit "Deploy: drush cr failed."; fi
echo ""

echo "Deploy Step 10/13: Running custom PHP evaluation..."
$DRUSH_CMD php-eval 'if (function_exists("simple_school_reports_module_info_deploy")) { simple_school_reports_module_info_deploy(); echo "[PHP Eval] Executed.\n"; } else { echo "[PHP Eval] Func not found.\n"; }'
echo ""

echo "Deploy Step 11/13: Disabling maintenance mode..."
$DRUSH_CMD state:set system.maintenance_mode 0 --input-format=integer; if [ $? -ne 0 ]; then error_exit "Deploy: Failed maint mode OFF."; fi
echo ""

echo "Deploy Step 12/13: Clearing caches (final)..."
$DRUSH_CMD cr; if [ $? -ne 0 ]; then error_exit "Deploy: Final drush cr failed."; fi
echo ""

echo "Deploy Step 13/13: Restoring directory permissions..."
# Make sites/default read-only again (basic protection)
chmod -w "$DEFAULT_SITES_DIR/"; if [ $? -ne 0 ]; then echo "WARNING: Failed chmod -w sites."; fi
echo ""

echo "Standard deployment sequence completed."
echo "Final deployment steps finished."
echo "---"

# --- Step 35: Post-Installation Reminders ---
echo "[Step 35/35] Displaying final recommendations..."
# Display final checklist for the user after successful execution
echo "";
echo "========================================================================"
echo "                SITE SETUP AND DEPLOYMENT COMPLETE!                     ";
echo "========================================================================";
echo ""
echo "Final Manual Steps & Checks Recommended:";
echo "----------------------------------------";
echo ""
echo "1. Database Backup: Take initial state dump (e.g., using '$DRUSH_CMD sql-dump')."
echo "2. Sanity Check Page: Visit [YOUR_SITE_URL]/sanity-check and verify."
echo "3. Subject Taxonomy: Check [YOUR_SITE_URL]/admin/structure/taxonomy/manage/school_subject/overview"
echo "4. Grade System Taxonomy: Check [YOUR_SITE_URL]/admin/structure/taxonomy/manage/af_grade_system/overview (check '-' order)."
echo "5. Logos: Upload at [YOUR_SITE_URL]/admin/file-templates-config"
echo "6. Cron Job: Setup external cron (every ~10 min) for URL on [YOUR_SITE_URL]/admin/config/system/cron page.";
echo "";
echo "========================================================================";
echo ""

# --- Script End ---
echo "Script finished successfully."
exit 0
