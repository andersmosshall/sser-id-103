#!/bin/bash

# --- Bash Version Check ---
if (( BASH_VERSINFO[0] < 4 )); then
    echo "Error: Bash version 4+ is required for associative arrays (module weights)." >&2
    exit 1
fi

# --- Configuration ---
# Base directory where ssr-sites will be created
SITES_BASE_DIR="ssr-sites"
CUSTOM_MODULES_DIR="web/modules/custom" # Relative path to custom modules
BASE_CORE_EXTENSION_FILE="config/sync/core.extension.yml" # Path to base module list

# Template paths relative to the script's execution directory
SETTINGS_TEMPLATE_SOURCE=".scripts/templates/settings.local.template"
SYSTEM_SITE_TEMPLATE_SOURCE=".scripts/templates/system.site.template"
CONFIG_SPLIT_TEMPLATE_SOURCE=".scripts/templates/config_split.config_split.local.template"
FETCH_HELPER_TEMPLATE_SOURCE=".scripts/templates/bash_section_fetch.template"
UPDATE_HELPER_TEMPLATE_SOURCE=".scripts/templates/bash_section_update.template"
# Default values
DEFAULT_NO_REPLY_EMAIL="no-reply@simpleschoolreports.se"
DEFAULT_TOOLBAR_COLOR="#0f0f0f"
DEFAULT_MODULE_WEIGHT=10

# Modules to always exclude from selection (in addition to _support and base)
declare -a ALWAYS_EXCLUDE_MODULES=(
    "some_other_module_to_exclude"
    # Add more modules here later if needed
)

# Specific weight overrides for modules (Key = module_name, Value = weight)
# Needs Bash 4+
declare -A MODULE_WEIGHT_OVERRIDES=(
    ["simple_school_reports_geg_grade_registration"]=20
    ["simple_school_reports_grade_stats"]=30
    # Add more overrides here later if needed
)

# --- Helper Functions ---
# Function to print error messages and exit
error_exit() {
  echo "Error: $1" >&2 # Print error message to stderr
  exit 1
}

# Function to escape characters problematic for sed replacement (like / & \)
escape_sed_replacement() {
  # Escape forward slash, ampersand, and backslash for sed 's/.../.../'
  echo "$1" | sed -e 's/[\/&\\]/\\&/g'
}

# --- Main Script ---

echo "--- SSR Site Setup Script ---"
echo "This script will guide you through setting up a new SSR site."
echo "Please ensure the following prerequisites are met before proceeding:"
echo "  - You are running this from the root of an SSR base repository."
echo "  - You have an empty Git repository prepared for the new site."
echo "  - You have a server prepared, pointing the expected URL to the web root, with SSL configured."
echo "  - You have a database created on the server and the access credentials ready."
echo

# 1. Confirm Prerequisites
read -p "Do you confirm all the above prerequisites are met? (y/n): " confirm_prereqs
confirm_prereqs_lower=$(echo "$confirm_prereqs" | tr '[:upper:]' '[:lower:]')

if [[ "$confirm_prereqs_lower" != "y" ]]; then
  error_exit "Aborting. Please ensure all prerequisites are met before running the script."
fi
echo # Add a newline for readability

# --- Site Configuration ---
echo "--- Site Configuration ---"

# 2. Get SSR_ID (Expanded)
while true; do
  read -p "Enter SSR_ID (must be a number between 1 and 99): " SSR_ID
  if [[ "$SSR_ID" =~ ^[0-9]+$ && "$SSR_ID" -gt 0 && "$SSR_ID" -lt 100 ]]; then
    break # Exit loop if valid
  else
    echo "Invalid input. Please enter a number between 1 and 99." >&2
  fi
done
echo # Add a newline

# 3. Get Git Clone URL (Expanded)
DEFAULT_GIT_URL="git@github.com:andersmosshall/ssr-id-${SSR_ID}.git"
read -p "Enter Git clone URL [${DEFAULT_GIT_URL}]: " GIT_CLONE_URL
# Use Bash parameter expansion for default if input is empty
GIT_CLONE_URL=${GIT_CLONE_URL:-$DEFAULT_GIT_URL}
echo "Using Git URL: ${GIT_CLONE_URL}"
echo # Add a newline

# 4. Get School Details (Expanded)
echo "--- School Details ---"
while true; do
    read -p "Enter School Name (required): " SCHOOL_NAME
    if [[ -n "$SCHOOL_NAME" ]]; then
        break
    else
        echo "School Name cannot be empty." >&2
    fi
done

DEFAULT_SCHOOL_NAME_SHORT=$(echo "$SCHOOL_NAME" | cut -c1-3)
read -p "Enter School Short Name (max 3-4 chars) [${DEFAULT_SCHOOL_NAME_SHORT}]: " SCHOOL_NAME_SHORT
SCHOOL_NAME_SHORT=${SCHOOL_NAME_SHORT:-$DEFAULT_SCHOOL_NAME_SHORT}
if [[ ${#SCHOOL_NAME_SHORT} -gt 4 ]]; then
    echo "Warning: School Short Name '${SCHOOL_NAME_SHORT}' is longer than 4 characters. Using first 4: '${SCHOOL_NAME_SHORT:0:4}'" >&2
    SCHOOL_NAME_SHORT=${SCHOOL_NAME_SHORT:0:4}
fi

while true; do
    read -p "Enter School Organiser (Huvudman) (required): " SCHOOL_ORGANISER
    if [[ -n "$SCHOOL_ORGANISER" ]]; then
        break
    else
        echo "School Organiser cannot be empty." >&2
    fi
done

while true; do
    read -p "Enter School Unit Code (Skolenhetskod) (required): " SCHOOL_UNIT_CODE
    if [[ -n "$SCHOOL_UNIT_CODE" ]]; then
        break
    else
        echo "School Unit Code cannot be empty." >&2
    fi
done

while true; do
    read -p "Enter School Municipality (Kommun) (required): " SCHOOL_MUNICIPALITY
    if [[ -n "$SCHOOL_MUNICIPALITY" ]]; then
        break
    else
        echo "School Municipality cannot be empty." >&2
    fi
done

while true; do
    read -p "Enter School Municipality Code (Kommunkod) (required): " SCHOOL_MUNICIPALITY_CODE
    if [[ -n "$SCHOOL_MUNICIPALITY_CODE" ]]; then
        break
    else
        echo "School Municipality Code cannot be empty." >&2
    fi
done
echo # Add a newline

# 5. Get URL and SSR Settings (Expanded)
echo "--- URL and SSR Settings ---"
while true; do
  read -p "Enter URL Name (lowercase a-z, 0-9, - only): " URL_NAME
  if [[ -n "$URL_NAME" && "$URL_NAME" =~ ^[a-z0-9-]+$ ]]; then
    break
  else
    echo "Invalid input. URL Name must contain only lowercase letters (a-z), numbers (0-9), or hyphens (-), and cannot be empty." >&2
  fi
done

DEFAULT_FULL_URL="${URL_NAME}.simpleschoolreports.se"
read -p "Enter Full URL [${DEFAULT_FULL_URL}]: " FULL_URL
FULL_URL=${FULL_URL:-$DEFAULT_FULL_URL} # Use default if empty
echo "Using Full URL: ${FULL_URL}"
echo # Add a newline

while true; do
  read -p "Enter starting grade (0-9): " SSR_GRADE_FROM
  if [[ "$SSR_GRADE_FROM" =~ ^[0-9]$ ]]; then # Check for single digit 0-9
     break
  else
     echo "Invalid input. Please enter a single digit between 0 and 9." >&2
  fi
done

while true; do
  read -p "Enter ending grade (0-9, must be >= ${SSR_GRADE_FROM}): " SSR_GRADE_TO
  if [[ "$SSR_GRADE_TO" =~ ^[0-9]$ && "$SSR_GRADE_TO" -ge "$SSR_GRADE_FROM" ]]; then
     break
  else
     echo "Invalid input. Please enter a single digit between 0 and 9, not less than the starting grade (${SSR_GRADE_FROM})." >&2
  fi
done
echo # Add a newline

# 6. Email and Customization Settings (Expanded)
echo "--- Email and Customization ---"
while true; do
    read -p "Enter Bug Report Email address (required): " SSR_BUG_REPORT_EMAIL
    if [[ -n "$SSR_BUG_REPORT_EMAIL" && "$SSR_BUG_REPORT_EMAIL" == *"@"* && "$SSR_BUG_REPORT_EMAIL" == *"."* ]]; then
        break
    else
        echo "Please enter a valid-looking email address." >&2
    fi
done

read -p "Enter No-Reply Email address [${DEFAULT_NO_REPLY_EMAIL}]: " SSR_NO_REPLY_EMAIL
SSR_NO_REPLY_EMAIL=${SSR_NO_REPLY_EMAIL:-$DEFAULT_NO_REPLY_EMAIL} # Use default if empty
echo "Using No-Reply Email: ${SSR_NO_REPLY_EMAIL}"

while true; do
    read -p "Enter Toolbar Color (hex format, e.g., #aabbcc) [${DEFAULT_TOOLBAR_COLOR}]: " SSR_TOOLBAR_COLOR_INPUT
    SSR_TOOLBAR_COLOR=${SSR_TOOLBAR_COLOR_INPUT:-$DEFAULT_TOOLBAR_COLOR} # Use default if empty
    if [[ "$SSR_TOOLBAR_COLOR" =~ ^#[0-9a-fA-F]{6}$ ]]; then
        break
    else
        echo "Invalid format. Please enter a 6-digit hex color code starting with # (e.g., #1a2b3c)." >&2
        if [[ -n "$SSR_TOOLBAR_COLOR_INPUT" ]]; then SSR_TOOLBAR_COLOR=""; fi
    fi
done
echo "Using Toolbar Color: ${SSR_TOOLBAR_COLOR}"
echo # Add a newline


# --- Module Selection ---
echo "--- Optional Module Selection ---"
declare -a available_modules=()
declare -a selected_module_indices=()
declare -a base_modules=()
SELECTED_MODULES=() # Final list of selected module names

# Read base modules from core.extension.yml if it exists
if [[ -f "$BASE_CORE_EXTENSION_FILE" ]]; then
    echo "Reading base modules from '$BASE_CORE_EXTENSION_FILE'..."
    # Awk script: set flag in_module_block when 'module:' is found, unset on non-indented line.
    # If in block and line is indented '  name:', print name.
    mapfile -t base_modules < <(awk '/^module:/{in_module_block=1; next} /^[^[:space:]]/{in_module_block=0} in_module_block && /^[[:space:]]{2}[a-zA-Z0-9_]+:/{ sub(/^[[:space:]]+/, ""); sub(/:.*/, ""); print }' "$BASE_CORE_EXTENSION_FILE")
    if [[ ${#base_modules[@]} -gt 0 ]]; then
        echo "  Found base modules to exclude: ${base_modules[*]}"
    else
        echo "  No base modules found under 'module:' key or file issue."
    fi
else
    echo "Warning: Base core extension file not found at '$BASE_CORE_EXTENSION_FILE'. Cannot exclude base modules." >&2
fi

if [[ ! -d "$CUSTOM_MODULES_DIR" ]]; then
    echo "Warning: Custom modules directory not found at '$CUSTOM_MODULES_DIR'. Skipping module selection." >&2
else
    echo "Searching for custom modules in '$CUSTOM_MODULES_DIR' (up to 4 levels deep)..."
    mapfile -t module_files < <(find "$CUSTOM_MODULES_DIR" -maxdepth 4 -name '*.info.yml' -type f -printf "%f\n" | sort)

    if [[ ${#module_files[@]} -eq 0 ]]; then
        echo "No custom modules found within 4 levels."
    else
        echo "Filtering potential modules:"
        available_index=0
        # Temp array to hold modules before listing them for selection
        declare -a candidate_modules=()
        for module_file in "${module_files[@]}"; do
            module_name="${module_file%.info.yml}"

            # Check exclusion: _support suffix
            if [[ "$module_name" == *_support ]]; then
                echo "  - Excluding '$module_name' (_support suffix)"
                continue
            fi

            # Check exclusion: Always exclude list
            excluded_by_list=false
            for exclude in "${ALWAYS_EXCLUDE_MODULES[@]}"; do
                if [[ "$module_name" == "$exclude" ]]; then
                    echo "  - Excluding '$module_name' (in exclude list)"
                    excluded_by_list=true
                    break
                fi
            done
            if $excluded_by_list; then
                continue
            fi

            # Check exclusion: Base modules from core.extension.yml
            is_base_module=false
            for base_mod in "${base_modules[@]}"; do
                if [[ "$module_name" == "$base_mod" ]]; then
                    is_base_module=true
                    break
                fi
            done
            if $is_base_module; then
                 echo "  - Excluding '$module_name' (already in $BASE_CORE_EXTENSION_FILE)"
                continue
            fi

            # If not excluded, add to candidates
             candidate_modules+=("$module_name")
        done

        # Now list the actual available modules for selection
        if [[ ${#candidate_modules[@]} -gt 0 ]]; then
            echo "Available optional modules:"
            available_index=0
            # Use candidate_modules for display and indexing
            available_modules=("${candidate_modules[@]}") # Copy to the array used later
            for module_name in "${available_modules[@]}"; do
                 printf "  %d) %s\n" $((available_index + 1)) "$module_name"
                 available_index=$((available_index + 1))
             done

            # Selection prompt loop
            while true; do
                read -p "Enter numbers of modules to include (space-separated, or 'none'): " selection_input
                if [[ "$selection_input" == "none" || -z "$selection_input" ]]; then
                    echo "No optional modules selected."
                    break
                fi

                # Validate input are numbers within range
                valid_selection=true
                read -ra selected_indices <<< "$selection_input" # Read input into an array
                temp_selected_modules=() # Temporary array for validated selections in this attempt
                for index_str in "${selected_indices[@]}"; do
                    if ! [[ "$index_str" =~ ^[1-9][0-9]*$ ]]; then
                        echo "Invalid input: '$index_str' is not a positive number." >&2
                        valid_selection=false
                        break
                    fi
                    local_index=$((index_str - 1)) # Convert to 0-based index
                    if ! [[ "$local_index" -ge 0 && "$local_index" -lt ${#available_modules[@]} ]]; then
                        echo "Invalid input: Number '$index_str' is out of range (1-${#available_modules[@]})." >&2
                        valid_selection=false
                        break
                    fi
                    # Add valid module name to temp list
                    temp_selected_modules+=("${available_modules[$local_index]}")
                done

                if $valid_selection; then
                    echo "Selected module numbers: ${selected_indices[*]}"
                    # Assign validated selections and remove duplicates
                    SELECTED_MODULES=($(printf "%s\n" "${temp_selected_modules[@]}" | sort -u))
                    echo "Selected modules: ${SELECTED_MODULES[*]}"
                    break # Exit selection loop
                fi
                # If not valid, loop continues asking for input
            done
        else
            echo "No eligible optional modules available for selection after filtering."
        fi
    fi
fi
echo # Newline after module selection

# Escape variables that might contain problematic characters BEFORE file operations
echo "Preparing replacement values..."
ESCAPED_SCHOOL_NAME=$(escape_sed_replacement "$SCHOOL_NAME")
ESCAPED_SCHOOL_ORGANISER=$(escape_sed_replacement "$SCHOOL_ORGANISER")
ESCAPED_SCHOOL_MUNICIPALITY=$(escape_sed_replacement "$SCHOOL_MUNICIPALITY")
ESCAPED_SSR_NO_REPLY_EMAIL=$(escape_sed_replacement "$SSR_NO_REPLY_EMAIL")
ESCAPED_GIT_CLONE_URL=$(escape_sed_replacement "$GIT_CLONE_URL")
ESCAPED_FULL_URL=$(escape_sed_replacement "$FULL_URL")

# --- Replacement Function ---
apply_all_replacements() {
  local target_file="$1"
  local filename
  filename=$(basename "$target_file") # Get filename for error messages

  echo "Applying common replacements to ${filename}..."

  sed -i "s/\\[SSR_ID\\]/${SSR_ID}/g" "$target_file" \
    || error_exit "Failed replacing [SSR_ID] in ${filename}"
  sed -i "s/\\[SCHOOL_NAME\\]/${ESCAPED_SCHOOL_NAME}/g" "$target_file" \
    || error_exit "Failed replacing [SCHOOL_NAME] in ${filename}"
  sed -i "s/\\[SCHOOL_NAME_SHORT\\]/${SCHOOL_NAME_SHORT}/g" "$target_file" \
    || error_exit "Failed replacing [SCHOOL_NAME_SHORT] in ${filename}"
  sed -i "s/\\[SCHOOL_ORGANISER\\]/${ESCAPED_SCHOOL_ORGANISER}/g" "$target_file" \
    || error_exit "Failed replacing [SCHOOL_ORGANISER] in ${filename}"
  sed -i "s/\\[SCHOOL_UNIT_CODE\\]/${SCHOOL_UNIT_CODE}/g" "$target_file" \
    || error_exit "Failed replacing [SCHOOL_UNIT_CODE] in ${filename}"
  sed -i "s/\\[SCHOOL_MUNICIPALITY\\]/${ESCAPED_SCHOOL_MUNICIPALITY}/g" "$target_file" \
    || error_exit "Failed replacing [SCHOOL_MUNICIPALITY] in ${filename}"
  sed -i "s/\\[SCHOOL_MUNICIPALITY_CODE\\]/${SCHOOL_MUNICIPALITY_CODE}/g" "$target_file" \
    || error_exit "Failed replacing [SCHOOL_MUNICIPALITY_CODE] in ${filename}"
  sed -i "s/\\[SSR_GRADE_FROM\\]/${SSR_GRADE_FROM}/g" "$target_file" \
    || error_exit "Failed replacing [SSR_GRADE_FROM] in ${filename}"
  sed -i "s/\\[SSR_GRADE_TO\\]/${SSR_GRADE_TO}/g" "$target_file" \
    || error_exit "Failed replacing [SSR_GRADE_TO] in ${filename}"
  sed -i "s/\\[SSR_BUG_REPORT_EMAIL\\]/${SSR_BUG_REPORT_EMAIL}/g" "$target_file" \
    || error_exit "Failed replacing [SSR_BUG_REPORT_EMAIL] in ${filename}"
  sed -i "s/\\[SSR_NO_REPLY_EMAIL\\]/${ESCAPED_SSR_NO_REPLY_EMAIL}/g" "$target_file" \
    || error_exit "Failed replacing [SSR_NO_REPLY_EMAIL] in ${filename}"
  sed -i "s/\\[SSR_TOOLBAR_COLOR\\]/${SSR_TOOLBAR_COLOR}/g" "$target_file" \
    || error_exit "Failed replacing [SSR_TOOLBAR_COLOR] in ${filename}"
  sed -i "s/\\[FULL_URL\\]/${ESCAPED_FULL_URL}/g" "$target_file" \
    || error_exit "Failed replacing [FULL_URL] in ${filename}"
  sed -i "s/\\[URL_NAME\\]/${URL_NAME}/g" "$target_file" \
    || error_exit "Failed replacing [URL_NAME] in ${filename}"
  sed -i "s/\\[GIT_CLONE_URL\\]/${ESCAPED_GIT_CLONE_URL}/g" "$target_file" \
    || error_exit "Failed replacing [GIT_CLONE_URL] in ${filename}"

  echo "Finished applying common replacements to ${filename}."
}

# --- Filesystem Operations ---
echo "--- Setting up Filesystem ---"

# Define target directory paths
TARGET_SITE_DIR="${SITES_BASE_DIR}/${URL_NAME}"
TARGET_SETTINGS_DIR="${TARGET_SITE_DIR}/.settings/prod"
TARGET_CONFIG_SYNC_DIR="${TARGET_SITE_DIR}/config/sync"
TARGET_BASH_HELPERS_DIR="${TARGET_SITE_DIR}/.bash_helpers"

# Define target file paths
TARGET_SETTINGS_FILE="${TARGET_SETTINGS_DIR}/settings.local.php"
TARGET_SYSTEM_SITE_FILE="${TARGET_CONFIG_SYNC_DIR}/system.site.yml"
TARGET_CONFIG_SPLIT_FILE="${TARGET_CONFIG_SYNC_DIR}/config_split.config_split.local.yml"
TARGET_FETCH_HELPER_FILE="${TARGET_BASH_HELPERS_DIR}/bash_section_fetch.txt"
TARGET_UPDATE_HELPER_FILE="${TARGET_BASH_HELPERS_DIR}/bash_section_update.txt"

# Define marker file name and path
MARKER_FILENAME="THIS_IS_${URL_NAME^^}" # Convert URL_NAME to uppercase
TARGET_MARKER_FILE="${TARGET_SITE_DIR}/${MARKER_FILENAME}"


echo "Preparing to create site structure in: ${TARGET_SITE_DIR}"

# 7. Create Target Directories (Expanded)
if [[ -e "$TARGET_SITE_DIR" ]]; then
  error_exit "Target directory or file '${TARGET_SITE_DIR}' already exists."
fi
mkdir -p "$TARGET_SETTINGS_DIR" \
  || error_exit "Failed to create directory '${TARGET_SETTINGS_DIR}'."
mkdir -p "$TARGET_CONFIG_SYNC_DIR" \
  || error_exit "Failed to create directory '${TARGET_CONFIG_SYNC_DIR}'."
mkdir -p "$TARGET_BASH_HELPERS_DIR" \
  || error_exit "Failed to create directory '${TARGET_BASH_HELPERS_DIR}'."
echo "Created base directories:"
ls -d "$TARGET_SETTINGS_DIR" "$TARGET_CONFIG_SYNC_DIR" "$TARGET_BASH_HELPERS_DIR"


# 8. Process settings.local.php (Expanded)
echo "Processing ${TARGET_SETTINGS_FILE}..."
if [[ ! -f "$SETTINGS_TEMPLATE_SOURCE" ]]; then
  error_exit "Template file '${SETTINGS_TEMPLATE_SOURCE}' not found."
fi
cp "$SETTINGS_TEMPLATE_SOURCE" "$TARGET_SETTINGS_FILE" \
  || error_exit "Failed to copy template to '${TARGET_SETTINGS_FILE}'."
apply_all_replacements "$TARGET_SETTINGS_FILE"


# 9. Process system.site.yml (Expanded)
echo "Processing ${TARGET_SYSTEM_SITE_FILE}..."
if [[ ! -f "$SYSTEM_SITE_TEMPLATE_SOURCE" ]]; then
  error_exit "Template file '${SYSTEM_SITE_TEMPLATE_SOURCE}' not found."
fi
cp "$SYSTEM_SITE_TEMPLATE_SOURCE" "$TARGET_SYSTEM_SITE_FILE" \
  || error_exit "Failed to copy template to '${TARGET_SYSTEM_SITE_FILE}'."
apply_all_replacements "$TARGET_SYSTEM_SITE_FILE"


# 10. Generate config_split.config_split.local.yml (Expanded)
echo "Processing ${TARGET_CONFIG_SPLIT_FILE}..."
if [[ ! -f "$CONFIG_SPLIT_TEMPLATE_SOURCE" ]]; then
  error_exit "Template file '${CONFIG_SPLIT_TEMPLATE_SOURCE}' not found."
fi

# Prepare module list replacement string
MODULES_REPLACEMENT_STRING=""
if [ ${#SELECTED_MODULES[@]} -eq 0 ]; then
    MODULES_REPLACEMENT_STRING="module: {  }"
    echo "  No modules selected, using empty module list."
else
    MODULES_REPLACEMENT_STRING="module:"
    echo "  Generating module list for config split:"
    for module in "${SELECTED_MODULES[@]}"; do
        # Get weight: Check override, else use default
        weight=${MODULE_WEIGHT_OVERRIDES[$module]:-$DEFAULT_MODULE_WEIGHT}
        printf -v module_line '\n  %s: %s' "$module" "$weight" # Store formatted line in variable
        MODULES_REPLACEMENT_STRING+="$module_line" # Append to main string
        echo "    - $module: $weight"
    done
fi

# Read template content, replace placeholder using Bash substitution, write to target
TEMPLATE_CONTENT=$(<"$CONFIG_SPLIT_TEMPLATE_SOURCE") \
  || error_exit "Failed to read template '${CONFIG_SPLIT_TEMPLATE_SOURCE}'."
CONFIG_SPLIT_CONTENT="${TEMPLATE_CONTENT//\[MODULES\]/$MODULES_REPLACEMENT_STRING}"
# Use printf instead of echo -n to handle potential backslashes in replacement better
printf "%s" "$CONFIG_SPLIT_CONTENT" > "$TARGET_CONFIG_SPLIT_FILE" \
  || error_exit "Failed to write processed content to '${TARGET_CONFIG_SPLIT_FILE}'."
# Also apply common replacements (in case template uses any others)
apply_all_replacements "$TARGET_CONFIG_SPLIT_FILE"


# 11. Process bash_section_fetch.txt (Renumbered & Expanded)
echo "Processing ${TARGET_FETCH_HELPER_FILE}..."
if [[ ! -f "$FETCH_HELPER_TEMPLATE_SOURCE" ]]; then
  error_exit "Template file '${FETCH_HELPER_TEMPLATE_SOURCE}' not found."
fi
cp "$FETCH_HELPER_TEMPLATE_SOURCE" "$TARGET_FETCH_HELPER_FILE" \
  || error_exit "Failed to copy template to '${TARGET_FETCH_HELPER_FILE}'."
apply_all_replacements "$TARGET_FETCH_HELPER_FILE"
echo "INFO: Content of ${TARGET_FETCH_HELPER_FILE} should be added to your ssr_fetch_all() bash function."


# 12. Process bash_section_update.txt (Renumbered & Expanded)
echo "Processing ${TARGET_UPDATE_HELPER_FILE}..."
if [[ ! -f "$UPDATE_HELPER_TEMPLATE_SOURCE" ]]; then
  error_exit "Template file '${UPDATE_HELPER_TEMPLATE_SOURCE}' not found."
fi
cp "$UPDATE_HELPER_TEMPLATE_SOURCE" "$TARGET_UPDATE_HELPER_FILE" \
  || error_exit "Failed to copy template to '${TARGET_UPDATE_HELPER_FILE}'."
apply_all_replacements "$TARGET_UPDATE_HELPER_FILE"
echo "INFO: Content of ${TARGET_UPDATE_HELPER_FILE} should be added to your ssr_update_all() bash function."


# 13. Create Marker File (Renumbered & Expanded)
echo "Creating marker file ${TARGET_MARKER_FILE}..."
touch "${TARGET_MARKER_FILE}" \
  || error_exit "Failed to create marker file '${TARGET_MARKER_FILE}'."
echo "Finished creating marker file."


# 14. Create Symlinks in .settings/prod (New Section & Expanded)
echo "Creating symlinks in ${TARGET_SETTINGS_DIR}..."

# Symlink for system.site.yml
SYMLINK_TARGET_SYSTEM_SITE="../../config/sync/system.site.yml"
SYMLINK_PATH_SYSTEM_SITE="${TARGET_SETTINGS_DIR}/system.site.yml"
if [[ -e "$SYMLINK_PATH_SYSTEM_SITE" ]]; then
  echo "Warning: Symlink already exists at ${SYMLINK_PATH_SYSTEM_SITE}. Skipping." >&2
else
  ln -s "$SYMLINK_TARGET_SYSTEM_SITE" "$SYMLINK_PATH_SYSTEM_SITE" \
    || error_exit "Failed to create symlink for system.site.yml."
  echo "  Created symlink: ${SYMLINK_PATH_SYSTEM_SITE} -> ${SYMLINK_TARGET_SYSTEM_SITE}"
fi

# Symlink for config_split.config_split.local.yml
SYMLINK_TARGET_CONFIG_SPLIT="../../config/sync/config_split.config_split.local.yml"
SYMLINK_PATH_CONFIG_SPLIT="${TARGET_SETTINGS_DIR}/config_split.config_split.local.yml"
if [[ -e "$SYMLINK_PATH_CONFIG_SPLIT" ]]; then
  echo "Warning: Symlink already exists at ${SYMLINK_PATH_CONFIG_SPLIT}. Skipping." >&2
else
  ln -s "$SYMLINK_TARGET_CONFIG_SPLIT" "$SYMLINK_PATH_CONFIG_SPLIT" \
    || error_exit "Failed to create symlink for config_split.local.yml."
  echo "  Created symlink: ${SYMLINK_PATH_CONFIG_SPLIT} -> ${SYMLINK_TARGET_CONFIG_SPLIT}"
fi
echo "Finished creating symlinks."


# --- Final Summary ---
echo
echo "--- Setup Summary ---"
echo "Site Directory:         ${TARGET_SITE_DIR}"
echo "Marker File:            ${TARGET_MARKER_FILE}"
echo "Settings File:          ${TARGET_SETTINGS_FILE}"
echo "System Site Config:     ${TARGET_SYSTEM_SITE_FILE}"
echo "Config Split Local:     ${TARGET_CONFIG_SPLIT_FILE}"
echo "Bash Fetch Helper:      ${TARGET_FETCH_HELPER_FILE}"
echo "Bash Update Helper:     ${TARGET_UPDATE_HELPER_FILE}"
echo "Selected Modules:       ${SELECTED_MODULES[*]:-(None)}"
echo "SSR ID:                 ${SSR_ID}"
echo "Git URL:                ${GIT_CLONE_URL}"
echo "URL Name:               ${URL_NAME}"
echo "Full URL:               ${FULL_URL}"
echo "School Name:            ${SCHOOL_NAME}"
echo "School Short Name:      ${SCHOOL_NAME_SHORT}"
echo "School Organiser:       ${SCHOOL_ORGANISER}"
echo "School Unit Code:       ${SCHOOL_UNIT_CODE}"
echo "School Municipality:    ${SCHOOL_MUNICIPALITY}"
echo "Municipality Code:      ${SCHOOL_MUNICIPALITY_CODE}"
echo "Grade Range:            ${SSR_GRADE_FROM}-${SSR_GRADE_TO}"
echo "Bug Report Email:       ${SSR_BUG_REPORT_EMAIL}"
echo "No-Reply Email:         ${SSR_NO_REPLY_EMAIL}"
echo "Toolbar Color:          ${SSR_TOOLBAR_COLOR}"
echo
echo "Script completed successfully!"
echo "Next steps typically involve:"
echo "  - Reviewing generated files for correctness (esp. settings, config files)."
echo "  - Integrating helper sections from ${TARGET_BASH_HELPERS_DIR} into your bash functions."
echo "  - Setting up database credentials in ${TARGET_SETTINGS_FILE} (if not templated)"
echo "  - Committing the initial site structure to its Git repository: ${GIT_CLONE_URL}"
echo "  - Deploying the code to the server"
echo "  - Running initial setup/database migrations (consider selected modules: ${SELECTED_MODULES[*]:-(None)})"

exit 0 # Success
