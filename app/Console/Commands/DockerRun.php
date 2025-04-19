<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DockerRun extends Command
{
    /**
     * The name and signature of the console command.
     * The arugument {env} must be 'test' or 'prod'
     *
     * @var string
     */
    protected $signature = 'docker:run {env=prod}';

    /**
     * The console command description.
     * 
     * @var string
     */
    protected $description = 'Run the docker scripts in the ./scripts/{env}/runner directory and .docker directory in local mode'; 

    /**
     * Execute the console command.
     * Execute the script in the ./scripts/{env}/runner directory if the OS is Windows, otherwise in the ./scripts/{env}/runner directory
     * If the {env} argument is not specified, the script in the .docker directory is executed with the start_docker.sh/cmd script in local mode    
     * @param string $env - The environment to deploy (test or prod)
     * @return int
     */
    public function handle()
    {
        $env = strtolower($this->argument('env'));
        $this->info("Starting docker: $env");

        if (strtoupper(
            substr(PHP_OS, 0, 3)) === 'WIN') {
            if ($env === 'test') {
                $script = base_path(".docker\start_docker.bat test");
            } elseif ($env === 'prod') {
                $script = base_path(".docker\tart_docker.bat prod");
            } else {
                $script = base_path(".docker\start_docker.bat");
            }
        } else {
            if ($env === 'test') {
                $script = base_path(".docker/start_docker.sh test");
            } else if ($env === 'prod') {
                $script = base_path(".docker/start_docker.sh prod");
            } 
        }

        if (!file_exists($script)) {
            $this->error("Script non trovato: $script");
            return 1;
        }

        $this->info("Esecuzione dello script locale: $script");

        $process = new Process([$script]);
        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error("Errore durante l'esecuzione dello script: " . $process->getErrorOutput());
            return 1;
        }

        $this->info("Deploy locale eseguito con successo!");
        return 0;
    }
}
