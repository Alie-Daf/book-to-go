@echo off
setlocal
title Publish Book To Go to GitHub

where git >nul 2>nul
if errorlevel 1 (
  echo Git was not found. Install Git for Windows from https://git-scm.com/download/win
  pause
  exit /b 1
)

echo.
echo BOOK TO GO - GITHUB PUBLISHER
echo -----------------------------
echo First, create an EMPTY repository on https://github.com/new
echo Do not add a README, .gitignore, or license on GitHub.
echo.
set /p REPO_URL=Paste the GitHub repository HTTPS URL: 
if "%REPO_URL%"=="" (
  echo No repository URL entered.
  pause
  exit /b 1
)

if not exist ".git" git init
if errorlevel 1 goto failed

git add .
if errorlevel 1 goto failed

git diff --cached --quiet
if not errorlevel 1 (
  echo No new changes to commit. Continuing to push existing commits...
) else (
  git commit -m "Initial commit - Book To Go"
  if errorlevel 1 goto failed
)

git branch -M main
if errorlevel 1 goto failed

git remote get-url origin >nul 2>nul
if errorlevel 1 (
  git remote add origin "%REPO_URL%"
) else (
  git remote set-url origin "%REPO_URL%"
)
if errorlevel 1 goto failed

git push -u origin main
if errorlevel 1 goto failed

echo.
echo SUCCESS: Your project has been pushed to GitHub.
pause
exit /b 0

:failed
echo.
echo Publishing stopped because a Git command failed.
echo Check the message above, then try again.
pause
exit /b 1
