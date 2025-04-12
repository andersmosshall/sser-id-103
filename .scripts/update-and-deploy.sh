#!/bin/bash

# --- Default Configuration ---
DEFAULT_PROFILE="ssr"
PROFILE="$DEFAULT_PROFILE"
DRUSH_CMD=""

# --- Argument Parsing ---
# Simple loop to find the -profile flag
while [[ $# -gt 0 ]]; do
  key="$1"
  case $key in
    -profile)
      # Check if a value is provided and it's not another flag
      if [[ -n "$2" && "$2" != -* ]]; then
        PROFILE="$2"
        shift # past argument (-profile)
        shift # past value (e.g., lando)
      else
        echo "Error: Argument for -profile is missing or invalid." >&2
        echo "Usage: $0 [-profile lando|ssr|sser]" >&2
        exit 1
      fi
      ;;
    *)
      # Handle unknown options if necessary, or ignore
      echo "Ignoring unknown option: $1"
      shift # past argument
      ;;
  esac
done

echo "--- Using profile: $PROFILE ---"

# --- Determine Drush command based on profile ---
case "$PROFILE" in
  lando)
    # Assuming 'lando drush' is available in the PATH when profile is lando
    if ! command -v lando &> /dev/null; then
        echo "Error: 'lando' command not found. Is Lando running and configured?" >&2
        exit 1
    fi
    DRUSH_CMD="lando drush"
    ;;
  ssr|sser)
    # Check if vendor/bin/drush exists and is executable for ssr/sser profiles
    if [ ! -x "./vendor/bin/drush" ]; then
        echo "Error: ./vendor/bin/drush not found or not executable for profile '$PROFILE'." >&2
        echo "Make sure you are in the project root and have run 'composer install'." >&2
        exit 1
    fi
    DRUSH_CMD="./vendor/bin/drush"
    ;;
  *)
    echo "Error: Invalid profile '$PROFILE'. Allowed profiles are 'lando', 'ssr', 'sser'." >&2
    exit 1
    ;;
esac

echo "--- Determined Drush command: $DRUSH_CMD ---"

# --- Execution Steps ---
echo "[1/5] Enabling maintenance mode..."
$DRUSH_CMD state:set system.maintenance_mode 1 --input-format=integer
# If this fails, set -e will exit. Maintenance mode might not be enabled. Manual check required.

echo "[2/5] Running update-owner.sh..."
if [ -f ".scripts/update-owner.sh" ]; then
    bash .scripts/update-owner.sh -user="current" -user-group="current"
    # If update-owner.sh fails, set -e will cause the script to exit here.
    # Maintenance mode will remain ENABLED. Manual intervention required.
else
    echo "Error: .scripts/update-owner.sh not found." >&2
    echo "Maintenance mode remains ENABLED. Manual intervention required." >&2
    exit 1 # Exit, leaving mode enabled
fi

echo "[3/5] Running update-from-base.sh..."
if [ -f ".scripts/update-from-base.sh" ]; then
    bash .scripts/update-from-base.sh
    # If update-from-base.sh fails, set -e will cause the script to exit here.
    # Maintenance mode will remain ENABLED. Manual intervention required.
else
    echo "Error: .scripts/update-from-base.sh not found." >&2
    echo "Maintenance mode remains ENABLED. Manual intervention required." >&2
    exit 1 # Exit, leaving mode enabled
fi

echo "[4/5] Running deploy-local.sh with profile $PROFILE..."
if [ -f ".scripts/deploy-local.sh" ]; then
    bash .scripts/deploy-local.sh -profile="$PROFILE"
    # If deploy-local.sh fails, set -e will cause the script to exit here.
    # Maintenance mode will remain ENABLED. Manual intervention required.
else
    echo "Error: .scripts/deploy-local.sh not found." >&2
    echo "Maintenance mode remains ENABLED. Manual intervention required." >&2
    exit 1 # Exit, leaving mode enabled
fi

echo "--- Update and Deploy finished successfully! ---"

exit 0
