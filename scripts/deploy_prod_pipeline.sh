#!/bin/bash
# Script: deploy_prod_pipeline.sh
# Script steps:
# 1. Verify logo.txt
# 2. Verify changelog.txt
# 3. Verify no unstaged/uncommitted changes (and that the stash is empty)
# 4. Branch change, pull, and merge
# 5. Commit and Push

# --- Step 0: Verify logo.txt ---
if [ ! -f "logo.txt" ]; then
    echo "The file logo.txt does not exist in the current directory."
    exit 1
fi
cat "logo.txt"

echo "Starting the production deployment process."

# --- Step 1: Verify changelog.txt ---
if [ ! -f "changelog.txt" ]; then
    echo "Error: changelog.txt does not exist."
    exit 1
fi

# Check the size of changelog.txt
filesize=$(stat -c%s "changelog.txt")
if [ "$filesize" -eq 0 ]; then
    echo "changelog.txt is empty."
    read -n 1 -p "Press E to exit or any other key to continue with an empty changelog: " choice
    echo
    if [[ "$choice" =~ ^[eE]$ ]]; then
        echo "Exiting process."
        exit 0
    fi
fi

# --- Step 2: Stash verification ---
if [ -n "$(git stash list)" ]; then
    echo "Error: There are changes in the stash. Please clean the stash before proceeding."
    exit 1
fi

# --- Additional Check: Verify no unstaged/uncommitted changes ---
if [ -n "$(git status --porcelain)" ]; then
    echo "Error: There are unstaged/uncommitted changes. Please clean the working directory before proceeding."
    exit 1
fi

# --- Step 3: Branch change, pull, and merge ---
git checkout deploy_prod
if [ $? -ne 0 ]; then
    echo "Error during checkout of deploy_prod."
    exit 1
fi

echo ">>>>>>>>>>>>>> Pull deploy_prod >>>>>>>>>>>>>>>"
git pull
if [ $? -ne 0 ]; then
    echo "Error during pull."
    exit 1
fi

echo ">>>>>>>>>>>>>> Merge main into deploy_prod >>>>>>>>>>>>>>>"
git merge main --no-ff -m "Deploy production"
if [ $? -ne 0 ]; then
    echo "Error during merge."
    exit 1
fi

# --- Step 4: Commit and Push ---
echo ">>>>>>>>>>>>>> Push changes >>>>>>>>>>>>>>>"
git push
if [ $? -ne 0 ]; then
    echo "Error during push."
    exit 1
fi

echo "Push completed. Production deployment pipeline started."
read -n 1 -s -r -p "Press any key to exit..."
