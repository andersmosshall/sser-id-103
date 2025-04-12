#!/bin/bash

# --- Configuration ---
# List of target DIRECTORIES. These will be created if they don't exist.
# Permissions will not be applied recursively.
TARGET_DIRS=(
  ".encryption_key"
  "web"
  "web/sites"
  "web/sites/default"
)

TARGET_DIRS_ALWAYS_WRITABLE=(
  "web/sites/default/files"
  "web/sites/default/private-files"
)

# List of target FILES. These will be SKIPPED if they don't exist.
# Parent directories will be created if needed.
TARGET_FILES=(
  ".encryption_key/encrypt.key"
  "web/sites/default/settings.common-local.php"
  "web/sites/default/settings.common-secrets.php"
  "web/sites/default/settings.local.php"
  "web/sites/default/settings.php"
)

# --- Script Logic ---
# Ensure pipeline commands fail if any part fails
set -o pipefail

# Initialize variables
target_user=""
target_group=""
writable=false # Default: make non-writable (ug-w)
sudo_cmd=""

# --- Sudo Check ---
if command -v sudo > /dev/null 2>&1; then
  sudo_cmd="sudo "
  echo "INFO: 'sudo' command found. Will use it for privileged operations."
else
  echo "WARNING: 'sudo' command not found. Operations requiring root privileges might fail."
  # Optional: Exit if sudo is absolutely required?
  # echo "ERROR: This script requires 'sudo'. Please install it or run as root." >&2
  # exit 1
fi

# --- Argument Parsing ---
for arg in "$@"; do
  case "$arg" in
    -user=*)
      target_user="${arg#*=}" # Remove '-user=' prefix
      target_user="${target_user%\'}" # Remove potential surrounding single quotes
      target_user="${target_user#\'}"
      ;;
    -user-group=*)
      target_group="${arg#*=}" # Remove '-user-group=' prefix
      target_group="${target_group%\'}" # Remove potential surrounding single quotes
      target_group="${target_group#\'}"
      ;;
    --writable)
      writable=true
      ;;
    *)
      echo "Ignoring unrecognized argument: $arg"
      ;;
  esac
done

# --- Handle "current" keyword ---
CURRENT_USER=$(whoami) # Get current username
if [[ "$target_user" == "current" ]]; then
  target_user="$CURRENT_USER"
  echo "INFO: Using current user ($CURRENT_USER) for target user."
fi
if [[ "$target_group" == "current" ]]; then
  # Decide if "current" group means user's primary group or the user's name as group
  # Using the user's name is often simpler and matches the previous default
  target_group="$target_user" # Use the *determined* target user name
  echo "INFO: Using target user name ($target_group) for target group."
  # Alternatively, get primary group ID and then name:
  # current_gid=$(id -g "$CURRENT_USER")
  # target_group=$(getent group "$current_gid" | cut -d: -f1)
  # echo "INFO: Using current user's primary group ($target_group) for target group."
fi

# --- Get User/Group if not provided ---
# Prompt for username if not supplied via argument or "current"
if [[ -z "$target_user" ]]; then
  read -p "Enter username [$CURRENT_USER]: " -e -i "$CURRENT_USER" target_user
  if [[ -z "$target_user" ]]; then target_user="$CURRENT_USER"; fi
fi

# Determine default group (often same as username) *after* user is finalized
DEFAULT_GROUP="$target_user"
# Prompt for group name if not supplied via argument or "current"
if [[ -z "$target_group" ]]; then
  read -p "Enter group name [$DEFAULT_GROUP]: " -e -i "$DEFAULT_GROUP" target_group
   if [[ -z "$target_group" ]]; then target_group="$DEFAULT_GROUP"; fi
fi

echo "----------------------------------------"
echo "Target User:   $target_user"
echo "Target Group:  $target_group"
echo "Make Writable: $writable"
echo "Using Sudo:    $( [[ -n "$sudo_cmd" ]] && echo "Yes" || echo "No" )"
echo "----------------------------------------"

# --- Validate User and Group ---
echo "Validating user and group..."
if ! id -u "$target_user" > /dev/null 2>&1; then
    echo "ERROR: User '$target_user' does not exist on this system." >&2
    exit 1
fi
if ! getent group "$target_group" > /dev/null 2>&1; then
    echo "ERROR: Group '$target_group' does not exist on this system." >&2
    exit 1
fi
echo "User and group validation successful."
echo "----------------------------------------"

# --- Determine chmod Permissions ---
chmod_perms=""
if $writable; then
  chmod_perms="ug+w"
  echo "Permissions to set: User/Group WRITE enabled ($chmod_perms)"
else
  chmod_perms="ug-w"
  echo "Permissions to set: User/Group WRITE disabled ($chmod_perms)"
fi
echo "----------------------------------------"


# --- Confirmation ---
echo "Will attempt the following actions:"
echo "1. Ensure target directories exist (creating if necessary)."
echo "2. Set owner to '$target_user:$target_group' for targets."
echo "3. Set permissions '$chmod_perms' for targets."
echo
echo "Target Directories:"
printf "  %s\n" "${TARGET_DIRS[@]}"
echo "Target Directories (always writable):"
printf "  %s\n" "${TARGET_DIRS_ALWAYS_WRITABLE[@]}"
echo "Target Files (skipped if non-existent):"
printf "  %s\n" "${TARGET_FILES[@]}"
echo
echo "NOTE: This operation uses '${sudo_cmd:-<no sudo>}'."
echo "----------------------------------------"

# --- Apply Changes ---
echo "Applying changes..."
change_count=0
skip_count=0
error_count=0

# Process Directories
echo "--- Processing Directories ---"
for dir_item in "${TARGET_DIRS[@]}"; do
  echo "Processing Directory: $dir_item"
  parent_dir=$(dirname "$dir_item")

  # Ensure parent directory exists
  if [[ ! -d "$parent_dir" ]]; then
    echo "  Creating parent directory: $parent_dir"
    if ! ${sudo_cmd}mkdir -p "$parent_dir"; then
        echo "  ERROR: Failed to create parent directory $parent_dir" >&2
        ((error_count++))
        continue # Skip this item if parent creation failed
    fi
  fi

  # Ensure target directory exists
  if [[ ! -d "$dir_item" ]]; then
      echo "  Creating target directory: $dir_item"
      if ! ${sudo_cmd}mkdir -p "$dir_item"; then
        echo "  ERROR: Failed to create target directory $dir_item" >&2
        ((error_count++))
        continue # Skip this item if target creation failed
      fi
  fi

  # Apply chown (recursively)
  echo "  Setting owner (recursively) to ${target_user}:${target_group}..."
  if ! ${sudo_cmd}chown -R "${target_user}:${target_group}" "$dir_item"; then
      echo "  ERROR: Failed to chown $dir_item" >&2
      ((error_count++))
      # Decide whether to continue with chmod or skip
      # continue
  fi

  # Apply chmod
  echo "  Setting permissions to ${chmod_perms}..."
   if ! ${sudo_cmd}chmod "${chmod_perms}" "$dir_item"; then
      echo "  ERROR: Failed to chmod $dir_item" >&2
      ((error_count++))
   else
       # Only count as fully changed if both succeed (or adjust logic)
       ((change_count++))
   fi
done

# Process Directories
echo "--- Processing Directories Always writable (Skip if not exists) ---"
for dir_item in "${TARGET_DIRS_ALWAYS_WRITABLE[@]}"; do
  echo "Processing Directory: $dir_item"

  # Ensure target directory exists
  if [[ ! -d "$dir_item" ]]; then
      echo "  Skipped target directory as it does not exists: $dir_item"
      continue
  fi

  # Apply chown (recursively)
  echo "  Setting owner (recursively) to ${target_user}:${target_group}..."
  if ! ${sudo_cmd}chown -R "${target_user}:${target_group}" "$dir_item"; then
      echo "  ERROR: Failed to chown $dir_item" >&2
      ((error_count++))
      # Decide whether to continue with chmod or skip
      # continue
  fi

  # Apply chmod
  echo "  Setting permissions (recursively) to ug+w"
   if ! ${sudo_cmd}chmod -R ug+w "$dir_item"; then
      echo "  ERROR: Failed to chmod $dir_item" >&2
      ((error_count++))
   else
       # Only count as fully changed if both succeed (or adjust logic)
       ((change_count++))
   fi
done

# Process Files
echo "--- Processing Files ---"
for file_item in "${TARGET_FILES[@]}"; do
  echo "Processing File: $file_item"
  parent_dir=$(dirname "$file_item")

  # Ensure parent directory exists (needed before checking file existence)
  if [[ ! -d "$parent_dir" ]]; then
    echo "  Creating parent directory: $parent_dir"
     if ! ${sudo_cmd}mkdir -p "$parent_dir"; then
        echo "  ERROR: Failed to create parent directory $parent_dir" >&2
        ((error_count++))
        continue # Skip this item if parent creation failed
    fi
  fi

  # Check if the target file exists
  if [[ -f "$file_item" ]]; then
    # Apply chown (non-recursively)
    echo "  Setting owner to ${target_user}:${target_group}..."
    if ! ${sudo_cmd}chown "${target_user}:${target_group}" "$file_item"; then
      echo "  ERROR: Failed to chown $file_item" >&2
      ((error_count++))
      # continue
    fi

    # Apply chmod (non-recursively)
    echo "  Setting permissions to ${chmod_perms}..."
    if ! ${sudo_cmd}chmod "${chmod_perms}" "$file_item"; then
      echo "  ERROR: Failed to chmod $file_item" >&2
      ((error_count++))
    else
       ((change_count++))
    fi
  else
    echo "  Skipping non-existent file: $file_item"
    ((skip_count++))
  fi
done


echo "----------------------------------------"
echo "Operation Summary:"
echo "  Successfully processed items: $change_count"
echo "  Skipped non-existent files: $skip_count"
echo "  Errors encountered:         $error_count"
echo "----------------------------------------"

if [[ $error_count -gt 0 ]]; then
    echo "Script finished with errors." >&2
    exit 1 # Exit with error status if any operation failed
else
    echo "Script finished successfully."
    exit 0
fi
