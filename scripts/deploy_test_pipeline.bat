@echo off
REM Script deploy_test_pipeline.cmd
REM Script steps:
REM 1. Verify logo.txt
REM 2. Verify changelog.txt
REM 3. Verify no unstaged/uncommitted changes
REM 4. Branch change, pull and merge
REM 5. Commit and Push

if not exist "logo.txt" (
    echo Il file logo.txt non esiste nella directory corrente.
    exit /b 1
)
type "logo.txt"

REM --- Step 1: Verify changelog.txt ---
if not exist changelog.txt (
    echo Errore: il file changelog.txt non esiste.
    exit /b 1
)

REM Check the changelog file size
for %%I in (changelog.txt) do set "size=%%~zI"
if "%size%"=="0" (
    echo Il file changelog.txt e vuoto.
    set /p choice="Premi E per uscire oppure qualsiasi altro tasto per continuare con il changelog vuoto: "
    if /I "%choice%"=="E" (
        echo Uscita dal processo.
        exit /b 0
    )
)

REM --- Step 2: Stash verify ---
git stash list > stash.txt
for %%I in (stash.txt) do set "stashSize=%%~zI"
if not "%stashSize%"=="0" (
    echo Errore: ci sono modifiche nello stash. Pulisci lo stash prima di procedere.
    del stash.txt
    exit /b 1
)
del stash.txt


REM --- Additional Check: Verify no unstaged/uncommitted changes ---
git status --porcelain > status.txt
for %%I in (status.txt) do set "statusSize=%%~zI"
if not "%statusSize%"=="0" (
    echo Errore: sono presenti modifiche non committate. Pulisci la working directory prima di procedere.
    del status.txt
    exit /b 1
)
del status.txt



REM --- Step 3: Branch change, pull e merge ---
git checkout deploy_test
if errorlevel 1 (
    echo Errore nel checkout di deploy_test.
    exit /b 1
)

echo >>>>>>>>>>>> pull deploy_test >>>>>>>>>>>>
git pull
if errorlevel 1 (
    echo Errore durante il pull.
    exit /b 1
)

echo >>>>>>>>>>>> merge main in deploy_test >>>>>>>>>>>> 
git merge main --no-ff -m "Deploy test"
if errorlevel 1 (
    echo Errore durante il merge.
    exit /b 1
)

REM --- Step 4: Commit e Push ---
echo >>>>>>>>>>>> Push modifiche >>>>>>>>>>>>
git push
if errorlevel 1 (
    echo Errore durante il push.
    exit /b 1
)

echo Push effettuato. Avviata pipeline deploy di test.
pause
