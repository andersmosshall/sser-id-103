#!/bin/bash

# Deployment script with profile-specific configurations

# Ensure pipeline commands fail if any part fails, not just the last one.
set -o pipefail

# --- Default Configuration ---
PROFILE=""
# Assuming this script is in '.scripts/' relative to the project root
PROJECT_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )/.."
UPDATE_OWNER_SCRIPT="$PROJECT_ROOT/.scripts/update-owner.sh"
TARGET_SETTINGS_DIR="$PROJECT_ROOT/web/sites/default"

# --- Helper Functions ---
print_usage() {
  echo "Usage: $0 -profile=<profile_name>"
  echo "  <profile_name>: lando | ssr | sser"
  echo "Example: bash .scripts/deploy.sh -profile=ssr"
}

print_step() {
  echo ""
  echo "-----------------------------------------------------"
  echo ">> STEP: $1"
  echo "-----------------------------------------------------"
}

print_error() {
  echo ""
  echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!" >&2
  echo ">> ERROR: $1" >&2
  echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!" >&2
}

# --- Argument Parsing ---
for arg in "$@"; do
  case "$arg" in
    -profile=*)
      PROFILE="${arg#*=}" # Remove '-profile=' prefix
      PROFILE="${PROFILE%\'}" # Remove potential surrounding single quotes
      PROFILE="${PROFILE#\'}"
      ;;
    *)
      echo "Ignoring unrecognized argument: $arg"
      ;;
  esac
done

# --- Validate Profile Argument ---
if [[ -z "$PROFILE" ]]; then
  print_error "Profile parameter '-profile=<name>' is mandatory."
  print_usage
  exit 1
fi

# --- Check Prerequisites ---
if [[ ! -f "$UPDATE_OWNER_SCRIPT" ]]; then
    print_error "Required script not found: $UPDATE_OWNER_SCRIPT"
    exit 1
fi
if [[ ! -x "$UPDATE_OWNER_SCRIPT" ]]; then
    print_error "Required script is not executable: $UPDATE_OWNER_SCRIPT (run: chmod +x $UPDATE_OWNER_SCRIPT)"
    exit 1
fi


# --- Profile-Specific Configuration ---
# Use arrays for commands to handle spaces robustly
DRUSH_CMD=()
COMPOSER_CMD=()
SETTINGS_SOURCE_DIR=""
OWNER_USER="current"    # Default user for update-owner.sh
OWNER_GROUP="current"   # Default group for update-owner.sh
COPY_EXTRA_LANDO_FILES=false

print_step "Configuring for profile: $PROFILE"

case "$PROFILE" in
  lando)
    DRUSH_CMD=("lando" "drush")
    COMPOSER_CMD=("lando" "composer")
    SETTINGS_SOURCE_DIR="$PROJECT_ROOT/.settings/local" # Lando uses 'local' settings
    # Owner/Group remains 'current' for Lando
    COPY_EXTRA_LANDO_FILES=true
    ;;
  ssr)
    DRUSH_CMD=("$PROJECT_ROOT/vendor/bin/drush")
    COMPOSER_CMD=("$HOME/composer.phar") # Path to composer.phar might need adjustment
    SETTINGS_SOURCE_DIR="$PROJECT_ROOT/.settings/prod"
    # Owner/Group remains 'current' for SSR
    ;;
  sser)
    DRUSH_CMD=("$PROJECT_ROOT/vendor/bin/drush")
    COMPOSER_CMD=("composer")
    SETTINGS_SOURCE_DIR="$PROJECT_ROOT/.settings/prod"
    OWNER_USER="www-data"
    OWNER_GROUP="current"
    ;;
  *)
    print_error "Unknown profile: '$PROFILE'"
    print_usage
    exit 1
    ;;
esac

echo "Using Drush command: ${DRUSH_CMD[*]}"
echo "Using Composer command: ${COMPOSER_CMD[*]}"
echo "Using Settings source: $SETTINGS_SOURCE_DIR"
echo "Using Owner settings: User=$OWNER_USER, Group=$OWNER_GROUP"
echo "Copy extra Lando files: $COPY_EXTRA_LANDO_FILES"


# --- Main Deployment Workflow ---

# Change to project root to ensure relative paths work correctly
cd "$PROJECT_ROOT" || exit 1
echo "Changed directory to project root: $(pwd)"

print_step "Update owner/permissions (writable)"
# Note: Corrected user/user-group typo from original request
bash "$UPDATE_OWNER_SCRIPT" -user="current" -user-group="current" --writable

print_step "Enable Maintenance Mode"
"${DRUSH_CMD[@]}" state:set system.maintenance_mode 1 --input-format=integer

print_step "Pull latest changes from Git"
git pull

print_step "Copy configuration files for profile '$PROFILE'"
cp "$SETTINGS_SOURCE_DIR/settings.local.php" "$TARGET_SETTINGS_DIR/settings.local.php"
echo "Copied settings.local.php"
cp "$SETTINGS_SOURCE_DIR/settings.common-local.php" "$TARGET_SETTINGS_DIR/settings.common-local.php"
echo "Copied settings.common-local.php"

# Copy extra files specifically for the Lando profile
if [[ "$COPY_EXTRA_LANDO_FILES" = true ]]; then
  print_step "Copy extra Lando configuration files"
  # Ensure target config sync directory exists
  mkdir -p "$PROJECT_ROOT/config/sync"
  cp ".settings/local/config_split.config_split.local.yml" "config/sync/config_split.config_split.local.yml"
  echo "Copied config_split.config_split.local.yml"
  cp ".settings/local/system.site.yml" "config/sync/system.site.yml"
  echo "Copied system.site.yml"
  cp ".settings/local/system.theme.global.yml" "config/sync/system.theme.global.yml"
  echo "Copied system.theme.global.yml"
fi

print_step "Install Composer dependencies"
# Consider adding flags like --no-dev --optimize-autoloader for production profiles
"${COMPOSER_CMD[@]}" install

print_step "Clear Drupal cache (pre-deploy)"
"${DRUSH_CMD[@]}" cr

print_step "Run Drupal database updates and configuration import (drush deploy)"
"${DRUSH_CMD[@]}" deploy -y

print_step "Run Drupal database updates and configuration import AGAIN (drush deploy)"
# Yes, running twice as requested
"${DRUSH_CMD[@]}" deploy -y

print_step "Check Drupal locales"
"${DRUSH_CMD[@]}" locale-check

print_step "Update Drupal locales"
"${DRUSH_CMD[@]}" locale-update

print_step "Clear Drupal cache (post-deploy)"
"${DRUSH_CMD[@]}" cr

print_step "Run custom deploy hook (if exists)"
# Note the careful quoting for the PHP code within the eval string
"${DRUSH_CMD[@]}" php-eval 'if (function_exists("simple_school_reports_module_info_deploy")) { simple_school_reports_module_info_deploy(); }'

print_step "Disable Maintenance Mode"
"${DRUSH_CMD[@]}" state:set system.maintenance_mode 0 --input-format=integer

print_step "Clear Drupal cache (final)"
"${DRUSH_CMD[@]}" cr

print_step "Update owner/permissions (non-writable)"
# Note: Corrected user/user-group typo from original request
bash "$UPDATE_OWNER_SCRIPT" -user="$OWNER_USER" -user-group="$OWNER_GROUP"

print_step "Deployment process completed for profile '$PROFILE'!"
echo "-----------------------------------------------------"
echo ""

exit 0
