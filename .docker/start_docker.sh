@echo off

REM Show the content of logo.txt
if not exist "logo.txt" (
    echo The file logo.txt does not exist in the current directory.
    exit /b 1
)
type "logo.txt"

docker info >nul 2>&1
if errorlevel 1 (
    echo Error: Docker is not running.
    pause
    exit /b
)

echo Starting Docker containers ...
docker-compose up --build -d
if errorlevel 1 (
    echo Error during the startup of Docker containers.
    pause
    exit /b
)

REM Show the status of active containers
echo Status of active containers:
docker ps

:menu
echo.
echo "Press S to stop the development server and enter the PHP container."
echo "Press N to exit without stopping the containers."
choice /c SN /n /m "S/N? "
if %errorlevel%==2 (
    echo Exit from the script without stopping the containers.
    goto end
)

if %errorlevel%==1 (
    for /f "tokens=*" %%i in ('docker ps --filter "name=php" --format "{{.Names}}"') do set PHP_CONTAINER=%%i

    if "%PHP_CONTAINER%"=="" (
        echo Error: the PHP container is not running.
        goto menu
    )

    echo Stopping the development server in the PHP container (%PHP_CONTAINER%)...
    docker exec -it %PHP_CONTAINER% pkill -f "artisan serve" >nul 2>&1
    if errorlevel 1 (
        echo Error: unable to stop the development server.
    ) else (
        echo Development server stopped successfully.
    )

    echo Opening a shell in the PHP container...
    docker exec -it %PHP_CONTAINER% bash
    goto menu
)

:end
echo Script terminated.
pause