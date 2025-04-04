#!/bin/bash

# ==============================================================================
# Website Setup and Deployment Script
#
# This script automates various setup tasks for a Drupal site, including:
# - Initial environment checks (Git, settings files)
# - Variable extraction from settings
# - User confirmations for manual steps
# - Composer installation
# - Directory/file creation (private files, encryption key)
# - Configuration export/import and manipulation
# - Deployment steps (manual or via alias)
# - Final sanity checks and reminders
# ==============================================================================

# --- Configuration Variables ---

# Path to the main production settings file (relative to script execution dir)
SETTINGS_FILE=".settings/prod/settings.local.php"

# Source paths for settings files to be copied
PROD_SETTINGS_LOCAL_SRC=".settings/prod/settings.local.php"
PROD_SETTINGS_COMMON_LOCAL_SRC=".settings/prod/settings.common-local.php"

# Destination directory for site-specific files
DEFAULT_SITES_DIR="web/sites/default"

# Destination paths for core Drupal and custom settings files
DEFAULT_SETTINGS_PHP="$DEFAULT_SITES_DIR/settings.php"
DEFAULT_SETTINGS_LOCAL_DEST="$DEFAULT_SITES_DIR/settings.local.php"
DEFAULT_SETTINGS_COMMON_LOCAL_DEST="$DEFAULT_SITES_DIR/settings.common-local.php"
DEFAULT_SETTINGS_COMMON_SECRETS_DEST="$DEFAULT_SITES_DIR/settings.common-secrets.php"
DEFAULT_SETTINGS_LOCAL_SECRETS_DEST="$DEFAULT_SITES_DIR/settings.local-secrets.php"

# Drupal's configuration synchronization directory
CONFIG_SYNC_DIR="config/sync"
CONFIG_SITE_FILE="$CONFIG_SYNC_DIR/system.site.yml"
CONFIG_THEME_FILE="$CONFIG_SYNC_DIR/system.theme.global.yml"

# Drupal's private files directory (ensure consistency if used elsewhere)
PRIVATE_FILES_DIR="web/sites/default/private-files"

# Directory and file for the site's encryption key
KEY_DIR=".encryption_key"
KEY_FILE="$KEY_DIR/encrypt.key"

# ==============================================================================
# --- Helper Functions ---
# ==============================================================================

# Function: error_exit
# Description: Prints an error message to stderr and exits the script with status 1.
# Arguments:
#   $1: The error message string to print.
error_exit() {
    # Print error message prefixed with "ERROR: " to standard error
    echo "ERROR: $1" >&2
    # Exit script with a non-zero status code indicating failure
    exit 1
}

# Function: extract_setting
# Description: Extracts a string value assigned to a specific key within the
#              $settings array in a PHP settings file. Assumes the value is
#              enclosed in single quotes.
# Arguments:
#   $1: The setting key name (e.g., 'ssr_school_name').
#   $2: The path to the PHP settings file.
# Output:
#   Prints the extracted value to stdout, or an empty string if not found.
extract_setting() {
    local setting_name="$1"
    local file="$2"
    local line
    local value

    # Grep for the line matching the setting assignment pattern.
    # Handles optional whitespace around '='. Requires single quotes around value.
    # Example line: $settings['key'] = 'value';
    line=$(grep -E "^\s*\\\$settings\['$setting_name'\]\s*=\s*'.*'\s*;" "$file")

    # If no matching line is found, print empty string and return.
    if [ -z "$line" ]; then
        echo ""
        return
    fi

    # Use sed to extract the value between the single quotes.
    # \1 refers to the first captured group ([^']*)
    value=$(echo "$line" | sed -E "s/^\s*\\\$settings\['$setting_name'\]\s*=\s*'([^']*)'\s*;/\1/")
    echo "$value"
}

# Function: extract_setting_unquoted
# Description: Extracts a value (numeric or constant) assigned to a specific key
#              within the $settings array in a PHP settings file. Assumes the
#              value is NOT enclosed in quotes.
# Arguments:
#   $1: The setting key name (e.g., 'ssr_id').
#   $2: The path to the PHP settings file.
# Output:
#   Prints the extracted value to stdout, or an empty string if not found.
extract_setting_unquoted() {
    local setting_name="$1"
    local file="$2"
    local line
    local value

    # Grep for the line matching the setting assignment pattern.
    # Handles optional whitespace around '='. Assumes value is not single-quoted.
    # Example line: $settings['key'] = VALUE;
    line=$(grep -E "^\s*\\\$settings\['$setting_name'\]\s*=[^'].*;" "$file")

    # If no matching line is found, print empty string and return.
     if [ -z "$line" ]; then
        echo ""
        return
     fi

    # Use sed to extract the value between '=' and ';', trimming whitespace.
    # \1 refers to the first captured group ([^;]*)
    value=$(echo "$line" | sed -E "s/^\s*\\\$settings\['$setting_name'\]\s*=\s*([^;]*)\s*;/\1/")
    # Trim leading/trailing whitespace from the extracted value just in case.
    value=$(echo "$value" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')
    echo "$value"
}

# ==============================================================================
# --- Main Script Execution ---
# ==============================================================================

echo "Starting website setup script..."
echo "Current directory: $(pwd)"
echo "---"

# --- Step 1: Verify Git Repository ---
echo "[Step 1/34] Verifying Git repository..."
# Check if the .git directory exists in the current path
if [ ! -d ".git" ]; then
    error_exit "Current directory is not a Git repository ('.git' directory not found)."
fi

# Attempt to get the URL of the 'origin' remote. Suppress stderr from git command itself.
git_url=$(git remote get-url origin 2>/dev/null)
# Check if the git command failed OR if the URL variable is empty
if [ $? -ne 0 ] || [ -z "$git_url" ]; then
    error_exit "Could not determine Git repository URL. Make sure the 'origin' remote is configured correctly."
fi
echo "Git repository found: $git_url"
echo "---"

# --- Step 2: Verify Source Settings File Exists ---
echo "[Step 2/34] Verifying source settings file: $SETTINGS_FILE..."
# Check if the primary settings file exists and is a regular file
if [ ! -f "$SETTINGS_FILE" ]; then
    error_exit "Source settings file not found at '$SETTINGS_FILE'."
fi
echo "Source settings file found."
echo "---"

# --- Step 3: Extract Variables from Settings File ---
echo "[Step 3/34] Extracting required settings from $SETTINGS_FILE..."
# Use helper functions to extract specific settings
school_name=$(extract_setting 'ssr_school_name' "$SETTINGS_FILE")
bug_email=$(extract_setting 'ssr_bug_report_email' "$SETTINGS_FILE")
noreply_email=$(extract_setting 'ssr_no_reply_email' "$SETTINGS_FILE")
ssr_id=$(extract_setting_unquoted 'ssr_id' "$SETTINGS_FILE")

# Validate that all required variables were successfully extracted
error_flag=0
if [ -z "$school_name" ]; then
    echo "ERROR: Could not find or extract \$settings['ssr_school_name']" >&2
    error_flag=1
fi
if [ -z "$bug_email" ]; then
    echo "ERROR: Could not find or extract \$settings['ssr_bug_report_email']" >&2
    error_flag=1
fi
if [ -z "$noreply_email" ]; then
    echo "ERROR: Could not find or extract \$settings['ssr_no_reply_email']" >&2
    error_flag=1
fi
# Check ssr_id for empty, allowing '0' as a valid value
if [ -z "$ssr_id" ] && [ "$ssr_id" != "0" ]; then
    echo "ERROR: Could not find or extract \$settings['ssr_id']" >&2
    error_flag=1
fi

# If any extraction failed, exit the script
if [ $error_flag -ne 0 ]; then
    error_exit "One or more required settings could not be extracted from '$SETTINGS_FILE'."
fi

# Display extracted settings for user verification
echo "Successfully extracted settings:"
echo "  School Name: $school_name"
echo "  Bug Report Email: $bug_email"
echo "  No Reply Email: $noreply_email"
echo "  SSR ID: $ssr_id"
echo "---"

# --- Step 4: Initial Confirmation Prompt ---
echo "[Step 4/34] Requesting initial setup confirmation..."
# Display multiple confirmation points for the user to verify manually
echo "========================================================================"
echo "PLEASE CONFIRM THE FOLLOWING BEFORE PROCEEDING:"
echo "========================================================================"
echo "1. This script is running in the correct deployment directory:"
echo "   '$(pwd)'"
echo "2. This directory and Git repo ('$git_url')"
echo "   are correctly associated with the school: '$school_name'"
echo "3. You have ALREADY prepared a database with the necessary credentials"
echo "   and it is available on this server."
echo "4. IMPORTANT: It is highly recommended to open a *new* SSH terminal,"
echo "   navigate to this *same directory* ('$(pwd)'), and keep it ready."
echo "   You may be prompted to perform actions in parallel shortly."
echo "========================================================================"

# Loop until user provides 'y' or 'n'
confirm_setup=""
while [[ "$confirm_setup" != "y" && "$confirm_setup" != "n" ]]; do
    read -p "Proceed with setup based on these confirmations? (y/n): " confirm_setup
    # Convert input to lowercase for case-insensitive comparison
    confirm_setup=$(echo "$confirm_setup" | tr '[:upper:]' '[:lower:]')
done

# Abort if user did not confirm with 'y'
if [[ "$confirm_setup" != "y" ]]; then
    echo "Aborting setup based on user input."
    exit 1
fi
echo "Setup confirmation received."
echo "---"

# --- Step 5: Confirm Extracted SSR ID ---
echo "[Step 5/34] Confirming extracted SSR ID..."
# Loop until user provides 'y' or 'n' (or presses Enter for default 'y')
confirm_ssr_id=""
while [[ "$confirm_ssr_id" != "y" && "$confirm_ssr_id" != "n" ]]; do
    read -p "Is the extracted SSR ID '$ssr_id' correct? [Y/n]: " confirm_ssr_id_input
    # Default to 'y' if the user just presses Enter
    confirm_ssr_id=${confirm_ssr_id_input:-y}
    # Convert input to lowercase
    confirm_ssr_id=$(echo "$confirm_ssr_id" | tr '[:upper:]' '[:lower:]')
done

# Abort if user did not confirm with 'y'
if [[ "$confirm_ssr_id" != "y" ]]; then
    echo "Aborting setup: SSR ID confirmation denied by user."
    exit 1
fi
echo "SSR ID confirmed."
echo "---"

# --- Step 6: Check for Lando Environment ---
echo "[Step 6/34] Checking for Lando environment..."
# Initialize prefix as empty
lando_prefix=""
is_lando=""
# Loop until user provides 'y' or 'n' (or presses Enter for default 'n')
while [[ "$is_lando" != "y" && "$is_lando" != "n" ]]; do
    read -p "Is this script running inside a Lando environment? [y/N]: " is_lando_input
    # Default to 'n' if the user just presses Enter
    is_lando=${is_lando_input:-n}
    # Convert input to lowercase
    is_lando=$(echo "$is_lando" | tr '[:upper:]' '[:lower:]')
done

# Set the command prefix if running in Lando
if [[ "$is_lando" == "y" ]]; then
    # Note the trailing space in the prefix
    lando_prefix="lando "
    echo "Lando mode enabled. '${lando_prefix}' prefix will be used for composer/drush commands."
else
    echo "Running in standard environment (no Lando prefix)."
fi
echo "---"

# --- Step 7: Run Composer Install ---
echo "[Step 7/34] Running Composer install..."
# Execute composer install, potentially prefixed with 'lando '
${lando_prefix}composer install
# Check the exit status of the composer command
if [ $? -ne 0 ]; then
    error_exit "Composer install failed. Please check composer output for errors."
fi
echo "Composer install completed successfully."
echo "---"

# --- Step 8: Ensure Private Files Directory Exists ---
echo "[Step 8/34] Ensuring private files directory exists: $DEFAULT_SITES_DIR/private-files"
# Use 'mkdir -p' to create the directory and any necessary parent directories.
# It does not error if the directory already exists.
mkdir -p "$DEFAULT_SITES_DIR/private-files"
# Check the exit status of the mkdir command
if [ $? -ne 0 ]; then
    error_exit "Failed to create directory '$DEFAULT_SITES_DIR/private-files'."
fi
echo "Directory '$DEFAULT_SITES_DIR/private-files' ensured."
echo "---"

# --- Step 9: Generate/Verify Encryption Key ---
echo "[Step 9/34] Checking for encryption key: $KEY_FILE"
# Check if the encryption key file already exists
if [ -f "$KEY_FILE" ]; then
    echo "Encryption key already exists. Using existing key."
    # Sanity check: Ensure the existing key file is not empty
    if [ ! -s "$KEY_FILE" ]; then
        error_exit "Existing encryption key file '$KEY_FILE' is empty. Please investigate."
    fi
else
    # Key file does not exist, proceed with generation
    echo "Generating new encryption key..."

    # Ensure the directory for the key exists
    mkdir -p "$KEY_DIR"
    if [ $? -ne 0 ]; then
        error_exit "Failed to create directory '$KEY_DIR' for encryption key."
    fi

    # Generate key into a temporary file first for atomicity and error checking
    temp_key_file=$(mktemp "${KEY_DIR}/encrypt.key.XXXXXX")

    # Read 32 random bytes from /dev/urandom, base64 encode, and write to temp file.
    # Suppress dd's summary output (2>/dev/null).
    if ! dd if=/dev/urandom bs=32 count=1 2>/dev/null | base64 -i - > "$temp_key_file"; then
         # Clean up temp file if generation failed
         rm -f "$temp_key_file" >/dev/null 2>&1
         error_exit "Failed to generate encryption key using dd/base64."
    fi

    # Sanity check: Ensure the temporary key file has content
    if [ ! -s "$temp_key_file" ]; then
         # Clean up temp file
         rm -f "$temp_key_file" >/dev/null 2>&1
         error_exit "Generated encryption key file is empty. Aborting."
    fi

    # Move the successfully generated temp file to the final location
    if ! mv "$temp_key_file" "$KEY_FILE"; then
         # Clean up temp file just in case move failed somehow
         rm -f "$temp_key_file" >/dev/null 2>&1
         error_exit "Failed to move generated key to '$KEY_FILE'."
    fi

    # Permissions will be handled by Drupal/later steps as per user request.
    # chmod 600 "$KEY_FILE" # This line was removed based on user feedback

    echo "New encryption key generated and saved to '$KEY_FILE'."
fi
echo "Encryption key handling complete."
echo "---"

# --- Step 10: Prompt User to Backup Encryption Key ---
echo "[Step 10/34] Prompting for encryption key backup confirmation..."
# Display the key and instructions for backup
echo "Encryption Key Backup Required:"
echo "========================================================================"
echo "The encryption key content is:"
echo ""
# Display the content of the key file
cat "$KEY_FILE"
echo ""
echo "========================================================================"
echo "IMPORTANT: Please COPY the key displayed above NOW and store it"
echo "securely in Bitwarden, 1Password, or a similar password manager"
echo "as a backup. This key is CRUCIAL for the site's functionality"
echo "(e.g., Real AES encryption)."
echo "========================================================================"

# Loop until user confirms backup is done ('y' or 'n')
confirm_backup=""
while [[ "$confirm_backup" != "y" && "$confirm_backup" != "n" ]]; do
    read -p "Have you securely backed up the key and want to proceed? (y/n): " confirm_backup
    confirm_backup=$(echo "$confirm_backup" | tr '[:upper:]' '[:lower:]')
done

# Abort if user did not confirm backup
if [[ "$confirm_backup" != "y" ]]; then
    echo "Aborting setup. Please back up the key from '$KEY_FILE' before proceeding."
    exit 1
fi
echo "Key backup confirmed by user."
echo "---"

# --- Step 11: Manual Step - Configure Web Server Document Root ---
# This step only provides instructions; the script performs no actions.
echo "[Step 11/34] Instructing user to configure web server Document Root..."
echo "Manual Step 1: Configure Web Server Document Root"
echo "========================================================================"
echo "Action Required: You need to configure your web server (e.g., Nginx, Apache)"
echo "to serve this website."
echo ""
echo "Set the Document Root (or 'root' in Nginx) for the domain associated"
echo "with '$school_name' to point to the 'web' subdirectory of the current"
echo "repository:"
echo "  -> $(pwd)/web"
echo ""
echo "This script WILL NOT configure the web server for you."
echo "Ensure this is done before proceeding with the Drupal installation."
echo "========================================================================"
echo "(No action taken by script for this step)"
echo "---"

# --- Step 12: Manual Step - Perform Drupal Web Installation ---
# This step only provides instructions; the script performs no actions.
echo "[Step 12/34] Instructing user to perform Drupal web installation..."
echo "Manual Step 2: Perform Drupal Web Installation"
echo "========================================================================"
echo "Action Required: Once the document root is configured correctly, open your"
echo "web browser and navigate to the URL for '$school_name'."
echo ""
echo "Follow the Drupal installation steps presented in your browser."
echo "Use the following settings during the installation process:"
echo ""
echo "  -> Language: Select 'English'"
echo "  -> Installation profile: Select 'Minimal'"
# Use extracted variables in instructions
echo "  -> Database configuration -> Advanced Options -> Table name prefix: 'ss${ssr_id}_'"
echo "  -> Configure site -> Site name: '$school_name'"
echo "  -> Configure site -> Site email address: '$noreply_email'"
echo "  -> Configure site -> Site maintenance account -> Email address: (Suggested) '$bug_email'"
echo "  -> Configure site -> Site maintenance account -> Password: Use a STRONG, UNIQUE password"
echo "                                                        and save it securely (e.g., Bitwarden)."
echo ""
echo "Complete the entire web-based installation process."
echo "========================================================================"
echo "(No action taken by script for this step)"
echo "---"

# --- Step 13: Confirmation of Manual Docroot/Install Steps ---
echo "[Step 13/34] Confirming completion of manual Document Root and Drupal Install..."
# Prompt user to confirm both manual steps are complete
echo "Confirmation Needed:"
echo "Please confirm that you have:"
echo "  1. Correctly configured the web server Document Root to '$(pwd)/web'."
echo "  2. Successfully completed the Drupal web installation using the details above."
echo "---"

# Loop until user confirms completion ('y' or 'n')
confirm_manual_steps=""
while [[ "$confirm_manual_steps" != "y" && "$confirm_manual_steps" != "n" ]]; do
    read -p "Are both the document root setup AND Drupal installation complete? (y/n): " confirm_manual_steps
    confirm_manual_steps=$(echo "$confirm_manual_steps" | tr '[:upper:]' '[:lower:]')
done

# Abort if user did not confirm completion
if [[ "$confirm_manual_steps" != "y" ]]; then
    echo "Aborting script. Please complete the manual steps before running the script again."
    exit 1
fi
echo "Manual configuration steps confirmed by user."
echo "---"

# --- Step 14: Make Settings Directory/File Writable ---
echo "[Step 14/34] Temporarily making '$DEFAULT_SITES_DIR/' and '$DEFAULT_SETTINGS_PHP' writable..."
# Grant write permissions (+w for user, group, other as requested) temporarily.
# This is often needed for Drupal or scripts to modify these during setup/updates.
chmod +w "$DEFAULT_SITES_DIR" "$DEFAULT_SETTINGS_PHP"
# Check exit status of chmod
if [ $? -ne 0 ]; then
    error_exit "Failed to make '$DEFAULT_SITES_DIR' or '$DEFAULT_SETTINGS_PHP' writable. Check permissions."
fi
echo "Permissions updated temporarily."
echo "---"

# --- Step 15: Manual Step - Edit Drupal settings.php ---
# This step only provides instructions; the script performs no actions.
echo "[Step 15/34] Instructing user to edit settings.php..."
echo "Manual Step 3: Edit Drupal settings.php"
echo "========================================================================"
echo "Action Required: In your OTHER terminal window (in this same directory),"
echo "edit the file: $DEFAULT_SETTINGS_PHP"
echo ""
echo "Make the following changes:"
echo "1. Inside the \$databases['default']['default'] array, ensure these"
echo "   lines are present (usually add them at the end, before the closing '];'):"
echo "     'charset' => 'utf8mb4',"
echo "     'collation' => 'utf8mb4_swedish_ci',"
echo "2. COPY the entire \$databases['default']['default'] array (including the"
echo "   database credentials you entered during install) and SAVE it securely"
echo "   in Bitwarden or a similar password manager as a backup."
echo "3. FIND and DELETE the line that looks like:"
echo "     \$settings['config_sync_directory'] = '...';"
echo "   (This setting is managed in settings.local.php instead)."
echo "4. FIND and UNCOMMENT the following lines (usually near the end of the file)"
echo "   by removing the '#' or '//' at the beginning of each line:"
echo "     if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {"
echo "       include \$app_root . '/' . \$site_path . '/settings.local.php';"
echo "     }"
echo ""
echo "SAVE the changes to $DEFAULT_SETTINGS_PHP."
echo "========================================================================"
echo "(No action taken by script for this step)"
echo "---"

# --- Step 16: Confirmation for settings.php Edits ---
echo "[Step 16/34] Confirming completion of settings.php edits..."
# Loop until user confirms completion ('y' or 'n')
confirm_settings_edit=""
while [[ "$confirm_settings_edit" != "y" && "$confirm_settings_edit" != "n" ]]; do
    read -p "Have you completed ALL the edits to $DEFAULT_SETTINGS_PHP as described above? (y/n): " confirm_settings_edit
    confirm_settings_edit=$(echo "$confirm_settings_edit" | tr '[:upper:]' '[:lower:]')
done

# Abort if user did not confirm completion
if [[ "$confirm_settings_edit" != "y" ]]; then
    echo "Aborting script. Please complete the $DEFAULT_SETTINGS_PHP edits before running again."
    exit 1
fi
echo "$DEFAULT_SETTINGS_PHP edits confirmed by user."
echo "---"

# --- Step 17: Copy Production Settings Files ---
echo "[Step 17/34] Copying production settings files into $DEFAULT_SITES_DIR/ ..."
# Copy the local settings overlay files from the source location to the live site directory
cp "$PROD_SETTINGS_LOCAL_SRC" "$DEFAULT_SETTINGS_LOCAL_DEST"
if [ $? -ne 0 ]; then
    error_exit "Failed to copy '$PROD_SETTINGS_LOCAL_SRC' to '$DEFAULT_SETTINGS_LOCAL_DEST'."
fi

cp "$PROD_SETTINGS_COMMON_LOCAL_SRC" "$DEFAULT_SETTINGS_COMMON_LOCAL_DEST"
 if [ $? -ne 0 ]; then
    error_exit "Failed to copy '$PROD_SETTINGS_COMMON_LOCAL_SRC' to '$DEFAULT_SETTINGS_COMMON_LOCAL_DEST'."
fi
echo "Production settings files copied."
echo "---"

# --- Step 18: Ensure Secrets Files Exist (Create if Missing) ---
echo "[Step 18/34] Ensuring secrets files exist in $DEFAULT_SITES_DIR/ ..."

# Check/Create common secrets file
echo "Checking for: $DEFAULT_SETTINGS_COMMON_SECRETS_DEST"
if [ -f "$DEFAULT_SETTINGS_COMMON_SECRETS_DEST" ]; then
    echo "File already exists. Skipping creation."
else
    echo "Creating file..."
    # Using printf for reliable newline handling (<?php\n\n)
    printf "<?php\n\n" > "$DEFAULT_SETTINGS_COMMON_SECRETS_DEST"
    if [ $? -ne 0 ]; then
        error_exit "Failed to create '$DEFAULT_SETTINGS_COMMON_SECRETS_DEST'."
    fi
    echo "File created."
fi

echo # Blank line for separation

# Check/Create local secrets file
echo "Checking for: $DEFAULT_SETTINGS_LOCAL_SECRETS_DEST"
if [ -f "$DEFAULT_SETTINGS_LOCAL_SECRETS_DEST" ]; then
    echo "File already exists. Skipping creation."
else
    echo "Creating file..."
    printf "<?php\n\n" > "$DEFAULT_SETTINGS_LOCAL_SECRETS_DEST"
    if [ $? -ne 0 ]; then
        error_exit "Failed to create '$DEFAULT_SETTINGS_LOCAL_SECRETS_DEST'."
    fi
     echo "File created."
fi
echo "Secrets files ensured."
echo "---"

# --- Step 19: Manual Step - Populate Secrets Files ---
# This step only provides instructions; the script performs no actions.
echo "[Step 19/34] Instructing user to populate secrets files..."
echo "Manual Step 4: Populate Secrets Files"
echo "========================================================================"
echo "Action Required: In your OTHER terminal window, edit the following two files:"
echo "  -> $DEFAULT_SETTINGS_COMMON_SECRETS_DEST"
echo "  -> $DEFAULT_SETTINGS_LOCAL_SECRETS_DEST"
echo ""
echo "Populate these files with the necessary PHP settings (secrets, keys, database"
echo "credentials if applicable, API keys, etc.) as required by your application setup."
echo "Retrieve these values from Bitwarden or your project's documentation."
echo ""
echo "Ensure the content is valid PHP code (typically defining variables or constants)."
echo "SAVE the changes to both files."
echo "========================================================================"
echo "(No action taken by script for this step)"
echo "---"

# --- Step 20: Confirmation for Secret File Population ---
echo "[Step 20/34] Confirming population of secrets files..."
# Loop until user confirms completion ('y' or 'n')
confirm_secrets_populate=""
while [[ "$confirm_secrets_populate" != "y" && "$confirm_secrets_populate" != "n" ]]; do
    read -p "Have you populated the secrets files ($DEFAULT_SETTINGS_COMMON_SECRETS_DEST and $DEFAULT_SETTINGS_LOCAL_SECRETS_DEST)? (y/n): " confirm_secrets_populate
    confirm_secrets_populate=$(echo "$confirm_secrets_populate" | tr '[:upper:]' '[:lower:]')
done

# Abort if user did not confirm completion
if [[ "$confirm_secrets_populate" != "y" ]]; then
    echo "Aborting script. Please populate the secrets files before running again."
    exit 1
fi
echo "Secrets files population confirmed by user."
echo "---"

# --- Step 21: Drush Config Export ---
echo "[Step 21/34] Exporting initial configuration after setup (drush cex -y)..."
# Export the configuration state from the database to the sync directory
${lando_prefix}drush cex -y
if [ $? -ne 0 ]; then
    error_exit "Drush config export (drush cex -y) failed."
fi
echo "Drush config export complete."
echo "---"

# --- Step 22: Selective Git Add, Checkout, Clean ---
echo "[Step 22/34] Staging specific config files & cleaning working directory..."
# Stage only the core site settings and global theme settings after initial install/cex
echo "Staging $CONFIG_SITE_FILE..."
git add "$CONFIG_SITE_FILE"
if [ $? -ne 0 ]; then error_exit "Failed to git add $CONFIG_SITE_FILE."; fi

echo "Staging $CONFIG_THEME_FILE..."
git add "$CONFIG_THEME_FILE"
if [ $? -ne 0 ]; then error_exit "Failed to git add $CONFIG_THEME_FILE."; fi

# Explain the destructive nature of the next commands
echo ""
echo "NOTE: The next steps ('git checkout .' and 'git clean -fd') will:"
echo "      1. Discard all other UNSTAGED changes in the working directory."
echo "      2. Remove all UNTRACKED files and directories."
echo "      This resets most exported config changes back to the repository state,"
echo "      keeping only the STAGED changes to:"
echo "      '$CONFIG_SITE_FILE' and '$CONFIG_THEME_FILE'."
echo ""

# Discard all other unstaged changes
echo "Running 'git checkout .' ..."
git checkout .
if [ $? -ne 0 ]; then error_exit "Failed to git checkout ."; fi

# Remove untracked files/directories (use with caution!)
echo "Running 'git clean -fd' ..."
git clean -fd
if [ $? -ne 0 ]; then error_exit "Failed to git clean -fd."; fi
echo "Working directory cleaned."
echo "---"

# --- Step 23: Modify system.site.yml Language Codes ---
echo "[Step 23/34] Modifying language codes in '$CONFIG_SITE_FILE' to 'sv'..."
# Check if the file exists before attempting modification
if [ ! -f "$CONFIG_SITE_FILE" ]; then
    error_exit "Config file '$CONFIG_SITE_FILE' not found after git operations. Cannot modify."
fi

# Use sed with in-place editing (-i) and extended regex (-E).
# ^ anchors the match to the start of the line for safety.
# [[:space:]]* allows zero or more spaces/tabs after the colon.
# Replace 'en' with 'sv' only if it follows the specific key.
echo "Updating langcode..."
sed -i -E "s/^(langcode:[[:space:]]*)en/\1sv/" "$CONFIG_SITE_FILE"
if [ $? -ne 0 ]; then error_exit "Failed to update langcode in $CONFIG_SITE_FILE."; fi

echo "Updating default_langcode..."
sed -i -E "s/^(default_langcode:[[:space:]]*)en/\1sv/" "$CONFIG_SITE_FILE"
if [ $? -ne 0 ]; then error_exit "Failed to update default_langcode in $CONFIG_SITE_FILE."; fi
echo "Language codes updated to 'sv'."
echo "---"

# --- Step 24: Git Add Modified system.site.yml ---
echo "[Step 24/34] Staging updated '$CONFIG_SITE_FILE' with language changes..."
# Stage the file again after modification
git add "$CONFIG_SITE_FILE"
if [ $? -ne 0 ]; then error_exit "Failed to git add updated $CONFIG_SITE_FILE."; fi
echo "File staged."
echo "---"

# --- Step 25: Show Git Diff and Confirm ---
echo "[Step 25/34] Displaying staged changes for review..."
echo "Displaying staged changes (git diff --cached):"
echo "========================= DIFF START ========================="
git diff --cached
echo "========================== DIFF END =========================="
echo "Please review the staged changes shown above (should primarily be language updates in system.site.yml)."
echo ""

# Loop until user confirms the diff ('y' or 'n')
confirm_diff=""
while [[ "$confirm_diff" != "y" && "$confirm_diff" != "n" ]]; do
    read -p "Do the staged changes look correct? (y/n): " confirm_diff
    confirm_diff=$(echo "$confirm_diff" | tr '[:upper:]' '[:lower:]')
done

# Abort if user did not confirm diff, provide reset instructions
if [[ "$confirm_diff" != "y" ]]; then
    echo ""
    echo "To discard these specific staged changes before running the script again, you might use:"
    echo "  git reset HEAD -- $CONFIG_SITE_FILE $CONFIG_THEME_FILE"
    echo "  git checkout -- $CONFIG_SITE_FILE"
    echo ""
    error_exit "Aborting script based on user review of git diff."
fi
echo "Staged changes confirmed by user."
echo "---"

# --- Step 26: Git Commit and Push ---
echo "[Step 26/34] Committing and pushing staged configuration updates..."
# Commit the staged changes with a standard message
git commit -m "site config updates"
if [ $? -ne 0 ]; then error_exit "git commit failed."; fi
echo "Commit successful."

# Push the current branch (HEAD) to the 'origin' remote
echo "Pushing changes to origin..."
git push origin HEAD
if [ $? -ne 0 ]; then error_exit "git push failed. Check remote connection, branch status, and permissions."; fi
echo "Push successful."
echo "---"

# --- Step 27: Manual Step - Database Backup Prompt ---
# This step only provides instructions; the script performs no actions.
echo "[Step 27/34] Instructing user to perform database backup..."
echo "Manual Step 5: Database Backup"
echo "========================================================================"
echo "Action Required: BEFORE proceeding with database updates and config imports,"
echo "it is STRONGLY recommended to take a manual database dump (backup)"
echo "of the current site database."
echo ""
echo "Example (if using Lando): ${lando_prefix}db-export mybackup_$(date +%Y%m%d_%H%M%S).sql.gz"
echo "Example (standard): mysqldump -u[USER] -p[PASS] [DB_NAME] | gzip > mybackup_$(date +%Y%m%d_%H%M%S).sql.gz"
echo ""
echo "This script WILL NOT create the backup for you."
echo "========================================================================"
echo "(No action taken by script for this step)"
echo "---"

# --- Step 28: Confirmation for Database Backup ---
echo "[Step 28/34] Confirming database backup completion..."
# Loop until user confirms backup is done ('y' or 'n')
confirm_db_dump=""
while [[ "$confirm_db_dump" != "y" && "$confirm_db_dump" != "n" ]]; do
    read -p "Have you taken a database backup and want to proceed? (y/n): " confirm_db_dump
    confirm_db_dump=$(echo "$confirm_db_dump" | tr '[:upper:]' '[:lower:]')
done

# Abort if user did not confirm backup
if [[ "$confirm_db_dump" != "y" ]]; then
    error_exit "Aborting script. Please take a database backup before proceeding."
fi
echo "Database backup confirmed by user."
echo "---"

# --- Step 29: Run Drush Updatedb (First Time) ---
echo "[Step 29/34] Running initial database updates (drush updb -y)..."
# Apply any pending database schema updates
${lando_prefix}drush updb -y
if [ $? -ne 0 ]; then
    error_exit "First drush updb -y failed."
fi
echo "First database updates complete."
echo "---"

# --- Step 30: Run Drush Config Import (Twice) ---
echo "[Step 30/34] Importing configuration (drush cim -y) - First run..."
# Import configuration from the sync directory into the database
${lando_prefix}drush cim -y
if [ $? -ne 0 ]; then
    # Don't exit on first failure, warn instead as second run might fix dependency issues
    echo "WARNING: First drush cim -y reported errors. Proceeding with second run..."
else
    echo "First config import complete."
fi
echo "---"

echo "[Step 30/34] Importing configuration (drush cim -y) - Second run..."
# Run config import again, often necessary to resolve dependencies
${lando_prefix}drush cim -y
if [ $? -ne 0 ]; then
    error_exit "Second drush cim -y failed. Check Drush output for details."
fi
echo "Second config import complete."
echo "---"

# --- Step 31: Run Drush Updatedb (Second Time) ---
echo "[Step 31/34] Running database updates again after config import (drush updb -y)..."
# Run database updates again in case config imports introduced new updates
${lando_prefix}drush updb -y
if [ $? -ne 0 ]; then
    error_exit "Second drush updb -y failed."
fi
echo "Second database updates complete."
echo "---"

# --- Step 32: Configuration Sanity Check ---
echo "[Step 32/34] Running configuration sanity check..."
# Export config again to see if the previous steps resulted in further changes
echo "Running drush cex -y for check..."
${lando_prefix}drush cex -y
if [ $? -ne 0 ]; then
    echo "WARNING: drush cex -y during sanity check reported errors or unexpected changes. Proceeding with diff check."
fi

# Check for configuration changes specifically in the sync directory compared to the last commit (HEAD)
# `git diff --quiet` exits 0 if no changes, 1 if changes exist.
echo "Checking for config differences in '$CONFIG_SYNC_DIR'..."
if ! git diff --quiet HEAD -- "$CONFIG_SYNC_DIR"; then
    # Differences were found
    echo "WARNING: Configuration difference detected in '$CONFIG_SYNC_DIR' after setup/deploy steps!"
    diff_dir="deploy-diff"
    # Use date and time for unique diff file name
    diff_filename="config_diff_$(date +%Y%m%d_%H%M%S).txt"
    diff_file="$diff_dir/$diff_filename"

    # Create directory for diffs if it doesn't exist
    mkdir -p "$diff_dir"
    # Create .gitignore inside diff dir if it doesn't exist, to ignore diff files
    if [ ! -f "$diff_dir/.gitignore" ]; then
      printf "*\n" > "$diff_dir/.gitignore" # Ignore contents of this dir
    fi

    echo "Saving full diff of '$CONFIG_SYNC_DIR' to: $diff_file"
    # Save the actual diff output to the file
    git diff HEAD -- "$CONFIG_SYNC_DIR" > "$diff_file"

    echo "Files differing from committed state in '$CONFIG_SYNC_DIR':"
    # List the names of the differing files, indented for clarity
    git diff --name-only HEAD -- "$CONFIG_SYNC_DIR" | sed 's/^/  - /'

    echo "------------------------------------------------------------------------"
    echo "ACTION RECOMMENDED: Please review the diff file '$diff_file'"
    echo "and contact the SSR maintainer about this unexpected configuration drift."
    echo "------------------------------------------------------------------------"

    # Reset the config directory back to the committed state to avoid committing drift
    echo "Resetting '$CONFIG_SYNC_DIR' to repository state..."
    # Checkout specific directory from HEAD to discard working changes
    git checkout HEAD -- "$CONFIG_SYNC_DIR"
    if [ $? -ne 0 ]; then error_exit "Sanity Check Reset: Failed to git checkout $CONFIG_SYNC_DIR."; fi
    # Clean untracked files only within the specific directory
    git clean -fd -- "$CONFIG_SYNC_DIR"
    if [ $? -ne 0 ]; then error_exit "Sanity Check Reset: Failed to git clean -fd $CONFIG_SYNC_DIR."; fi
    echo "Configuration directory reset."
else
    # No differences were found
    echo "Config check: No unexpected changes detected in '$CONFIG_SYNC_DIR'."
fi
echo "Configuration sanity check complete."
echo "---"

# --- Step 33: Full Deployment Steps ---
echo "[Step 33/34] Performing final deployment steps..."

# Check if the custom deploy alias exists
echo "Checking for Drush alias '@ssr_deploy_local'..."
# Use grep -qFx for quiet (-q), fixed string (-F), exact line match (-x)
if ${lando_prefix}drush site:alias --format=list | grep -qFx '@ssr_deploy_local'; then
    # --- Run Deployment via Alias ---
    echo "Alias '@ssr_deploy_local' found. Running deployment via alias..."
    # Assume the alias encapsulates all necessary deployment commands including 'deploy'
    ${lando_prefix}drush @ssr_deploy_local deploy
    if [ $? -ne 0 ]; then
        error_exit "Deployment using alias @ssr_deploy_local failed."
    fi
    echo "Deployment via alias completed."

else
    # --- Run Standard Deployment Sequence ---
    echo "Alias '@ssr_deploy_local' not found. Running standard deployment sequence..."
    echo # Blank line

    echo "Deploy Step 1/13: Enabling maintenance mode..."
    ${lando_prefix}drush state:set system.maintenance_mode 1 --input-format=integer
    if [ $? -ne 0 ]; then error_exit "Deploy: Failed to enable maintenance mode."; fi
    echo # Blank line

    echo "Deploy Step 2/13: Pulling latest code..."
    git pull
    if [ $? -ne 0 ]; then error_exit "Deploy: git pull failed."; fi
    echo # Blank line

    echo "Deploy Step 3/13: Setting permissions for deploy..."
    # Make sites/default temporarily writable for potential file writes during deploy
    chmod +w "$DEFAULT_SITES_DIR/"
    if [ $? -ne 0 ]; then error_exit "Deploy: Failed to chmod +w $DEFAULT_SITES_DIR/."; fi
    # Make local settings read-only (as it should be included, not modified by deploy)
    chmod -w "$DEFAULT_SETTINGS_LOCAL_DEST"
    if [ $? -ne 0 ]; then error_exit "Deploy: Failed to chmod -w $DEFAULT_SETTINGS_LOCAL_DEST."; fi
    echo # Blank line

    echo "Deploy Step 4/13: Running composer install..."
    # Add typical production flags for composer
    ${lando_prefix}composer install --no-dev --optimize-autoloader
    if [ $? -ne 0 ]; then error_exit "Deploy: composer install failed."; fi
    echo # Blank line

    echo "Deploy Step 5/13: Clearing caches (post-composer)..."
    ${lando_prefix}drush cr
    if [ $? -ne 0 ]; then error_exit "Deploy: drush cr failed after composer install."; fi
    echo # Blank line

    echo "Deploy Step 6/13: Running drush deploy (1st time)..."
    # Run the main Drupal deploy command (applies updates, imports config, etc.)
    ${lando_prefix}drush deploy -y
    if [ $? -ne 0 ]; then error_exit "Deploy: First drush deploy failed."; fi
    echo # Blank line

    echo "Deploy Step 7/13: Running drush deploy (2nd time)..."
    # Run deploy again as requested (sometimes needed for complex dependencies)
    ${lando_prefix}drush deploy -y
    if [ $? -ne 0 ]; then error_exit "Deploy: Second drush deploy failed."; fi
    echo # Blank line

    echo "Deploy Step 8/13: Checking/Updating translations..."
    ${lando_prefix}drush locale-check
    # Don't exit if locale-check fails, just proceed to update
    ${lando_prefix}drush locale-update
    if [ $? -ne 0 ]; then echo "WARNING: drush locale-update reported issues."; fi # Warn, don't fail
    echo # Blank line

    echo "Deploy Step 9/13: Clearing caches (post-deploy)..."
    ${lando_prefix}drush cr
    if [ $? -ne 0 ]; then error_exit "Deploy: drush cr failed after locale updates."; fi
    echo # Blank line

    echo "Deploy Step 10/13: Running custom PHP evaluation..."
    # Execute custom deployment PHP function if it exists
    # Added echo within PHP for better feedback
    ${lando_prefix}drush php-eval 'if (function_exists("simple_school_reports_module_info_deploy")) { simple_school_reports_module_info_deploy(); echo "[PHP Eval] Executed simple_school_reports_module_info_deploy().\n"; } else { echo "[PHP Eval] Function simple_school_reports_module_info_deploy() not found.\n"; }'
    # Cannot reliably check exit status of conditional PHP code, proceed unless drush itself fails
    echo # Blank line

    echo "Deploy Step 11/13: Disabling maintenance mode..."
    ${lando_prefix}drush state:set system.maintenance_mode 0 --input-format=integer
    if [ $? -ne 0 ]; then error_exit "Deploy: Failed to disable maintenance mode."; fi
    echo # Blank line

    echo "Deploy Step 12/13: Clearing caches (final)..."
    ${lando_prefix}drush cr
    if [ $? -ne 0 ]; then error_exit "Deploy: Final drush cr failed."; fi
    echo # Blank line

    echo "Deploy Step 13/13: Restoring directory permissions..."
    # Make sites/default read-only again (specific owner/group perms should be handled separately)
    chmod -w "$DEFAULT_SITES_DIR/"
    if [ $? -ne 0 ]; then echo "WARNING: Failed to chmod -w $DEFAULT_SITES_DIR/. Check permissions."; fi # Warn only
    echo # Blank line

    echo "Standard deployment sequence completed."
fi
echo "Final deployment steps finished."
echo "---"

# --- Step 34: Post-Installation Reminders ---
# This section just prints information to the user; no actions performed.
echo "[Step 34/34] Displaying post-setup recommendations..."
echo ""
echo "========================================================================"
echo "                SITE SETUP AND DEPLOYMENT COMPLETE!                     "
echo "========================================================================"
echo ""
echo "Final Manual Steps & Checks Recommended:"
echo "----------------------------------------"
echo ""
echo "1. Database Backup:"
echo "   - Take a new database dump to capture the initial installed and deployed state."
echo "     (e.g., ${lando_prefix}db-export initial_deploy_backup_$(date +%Y%m%d_%H%M%S).sql.gz)"
echo ""
echo "2. Sanity Check Page:"
echo "   - Visit your site at [YOUR_SITE_URL]/sanity-check"
echo "   - Verify all checks pass and data looks correct."
echo ""
echo "3. Subject Taxonomy:"
echo "   - Verify subjects were automatically created (if applicable) at:"
echo "     [YOUR_SITE_URL]/admin/structure/taxonomy/manage/school_subject/overview"
echo ""
echo "4. Grade System Taxonomy:"
echo "   - Verify the grade system was created (if applicable) at:"
echo "     [YOUR_SITE_URL]/admin/structure/taxonomy/manage/af_grade_system/overview"
echo "   - Remember: You might need to manually reorder the '-' (Not Applicable/Blank)"
echo "     entry to be listed first if required by your setup."
echo ""
echo "5. Logos & File Templates:"
echo "   - Upload site logos and review any other file templates at:"
echo "     [YOUR_SITE_URL]/admin/file-templates-config"
echo ""
echo "6. Cron Job:"
echo "   - Set up a system cron job (outside of Drupal) to run frequently (e.g., every 10 minutes)."
echo "   - Configure it to request the Drupal cron URL. Find the specific URL with token on the"
echo "     'Cron settings' page in Drupal admin:"
echo "     [YOUR_SITE_URL]/admin/config/system/cron"
echo "   - (Refer to Drupal documentation for details on setting up system cron jobs)."
echo ""
echo "========================================================================"
echo ""

# --- Script End ---
exit 0
