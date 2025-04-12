#!/bin/bash

# Update site from base repository (sser-base) script

# --- Configuration ---
BASE_REMOTE_NAME="sser-base"
BASE_REMOTE_URL="git@github.com:andersmosshall/sser-base.git"
BASE_BRANCH="main"
COMMIT_MESSAGE="Auto merged sser-base"

# --- Script ---

# Exit immediately if a command exits with a non-zero status.
set -e
# Treat unset variables as an error when substituting.
# set -u # Can be problematic if variables might legitimately be unset in some git outputs
# Ensure pipeline commands fail if any part fails, not just the last one.
set -o pipefail

echo "--- Starting update from base repository ($BASE_REMOTE_NAME) ---"

# 1. Setup sser-base remote if it doesn't exist
echo "Checking for remote '$BASE_REMOTE_NAME'..."
if ! git remote -v | grep -q "^${BASE_REMOTE_NAME}\s"; then
  echo "Remote '$BASE_REMOTE_NAME' not found. Adding it..."
  git remote add "$BASE_REMOTE_NAME" "$BASE_REMOTE_URL" || { echo "ERROR: Failed to add remote '$BASE_REMOTE_NAME'." >&2; exit 1; }
  echo "Remote '$BASE_REMOTE_NAME' added."
else
  echo "Remote '$BASE_REMOTE_NAME' already exists."
  # Optional: You might want to ensure the URL is correct
  # git remote set-url "$BASE_REMOTE_NAME" "$BASE_REMOTE_URL"
  # echo "Ensured remote URL is up-to-date."
fi

# 2. Pull the latest changes for the current branch
# Ensure working directory is clean first? (Optional, add if needed)
# if ! git diff --quiet HEAD --; then
#   echo "ERROR: Working directory is not clean. Please commit or stash changes." >&2
#   exit 1
# fi
echo "Pulling latest changes for the current branch..."
git pull || { echo "ERROR: Failed to pull changes for the current branch." >&2; exit 1; }
echo "Current branch updated."

# 3. Fetch the latest main branch from sser-base
echo "Fetching latest changes from ${BASE_REMOTE_NAME}/${BASE_BRANCH}..."
git fetch "$BASE_REMOTE_NAME" "$BASE_BRANCH" || { echo "ERROR: Failed to fetch from ${BASE_REMOTE_NAME}/${BASE_BRANCH}." >&2; exit 1; }
echo "Fetch complete."

# 4. Attempt to merge sser-base/main into the current branch without committing
echo "Attempting to merge ${BASE_REMOTE_NAME}/${BASE_BRANCH}..."
# Use --no-edit to avoid opening an editor if the merge is trivial but not fast-forward
git merge "${BASE_REMOTE_NAME}/${BASE_BRANCH}" --allow-unrelated-histories --no-commit --no-ff --no-edit || {
  # Merge command failed before creating conflicts (e.g., unrelated histories error without the flag)
  echo "ERROR: Merge command failed unexpectedly before conflict stage." >&2
  exit 1
}
echo "Merge attempt finished, checking status..."

# 5. Check for merge conflicts (unmerged files)
# It's better to check for conflicts *first*, as merge might succeed (exit 0) but still have conflicts.
if [[ $(git ls-files -u | wc -l) -gt 0 ]]; then
  echo "-----------------------------------------------------" >&2
  echo "ERROR: Merge conflicts detected! Please resolve them." >&2
  echo "Conflicting files:" >&2
  git ls-files -u | cut -f 2 | sort -u >&2
  echo "-----------------------------------------------------" >&2
  echo "Run 'git status' for details." >&2
  echo "After resolving, manually run 'git add .' and 'git commit'." >&2
  echo "Or run 'git merge --abort' to cancel the merge." >&2
  exit 1
fi
echo "No merge conflicts detected."

# 6. Check if there is nothing to merge (staging area is empty)
# If the staging area is empty after a --no-commit merge, it means no changes were brought in.
if [[ $(git diff --staged --name-only | wc -l) -eq 0 ]]; then
  echo "Nothing to merge from ${BASE_REMOTE_NAME}/${BASE_BRANCH}. Already up to date."
  # Abort any potential lingering merge state (though usually not needed if staging is empty)
  git merge --abort > /dev/null 2>&1 || true
  exit 0
fi
echo "Changes found from the merge."

# 7. Ignore changes to .gitignore from the merge
echo "Checking for changes to .gitignore..."
# Check if .gitignore was actually staged by the merge
if git diff --staged --name-only | grep -q '^\.gitignore$'; then
    echo "Resetting potential changes to .gitignore..."
    # Checkout the version from HEAD (pre-merge state) to keep local modifications
    git checkout HEAD -- .gitignore || { echo "ERROR: Failed to reset .gitignore." >&2; git merge --abort > /dev/null 2>&1 || true; exit 1; }
    echo ".gitignore reset to pre-merge state."
else
    echo ".gitignore was not modified by the merge."
fi

# 8. Stage changes (if any remain after .gitignore reset) and commit
echo "Staging remaining changes..."
git add . # Stage everything (including the potentially reset .gitignore)

# Check if anything is left to commit after potentially resetting .gitignore
if [[ $(git diff --staged --name-only | wc -l) -eq 0 ]]; then
    echo "No effective changes left to commit after handling .gitignore."
    git merge --abort > /dev/null 2>&1 || true # Abort the merge process cleanly
    exit 0
fi

echo "Committing the merge..."
git commit -m "$COMMIT_MESSAGE" || { echo "ERROR: Commit failed." >&2; git merge --abort > /dev/null 2>&1 || true; exit 1; }
echo "Merge committed."

# 9. Push changes
echo "Pushing changes to origin..."
# Determine the current branch name automatically
current_branch=$(git rev-parse --abbrev-ref HEAD)
git push origin "$current_branch" || { echo "ERROR: Push failed." >&2; exit 1; }
echo "Push successful."

echo "--- Update from base repository completed successfully ---"
exit 0
