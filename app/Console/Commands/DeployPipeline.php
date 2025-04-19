<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DeployPipeline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy {env=prod}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes remote deploy based on the environment (test or prod).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $env = strtolower($this->argument('env'));
        $this->info("❎ Start deploy process on environment: $env");

        $branch = $env === 'test' ? 'deploy_test' : 'deploy_prod';

        $logoPath = base_path('logo.txt');
        if (!file_exists($logoPath)) {
            $this->error("\n❌ Error: the logo.txt does not exist in the main directory.\n");
            return 1;
        }
        $this->line(file_get_contents($logoPath));

        $changelogPath = base_path('changelog.txt');
        if (!file_exists($changelogPath)) {
            $this->error("\n❌ Error: the changelog.txt does not exist.\n");
            return 1;
        }
        if (filesize($changelogPath) === 0) {
            $this->warn("\n❗ Il file changelog.txt è vuoto.");
            if (!$this->confirm("Do you want to continue with the empty changelog?", false)) {
                $this->info("\n❌ Exit from process.");
                return 0;
            }
        }

        if (!$this->checkGitClean()) {
            return 1;
        }

        if (!$this->runGitCommand("git checkout $branch", "Error checking out $branch")) {
            return 1;
        }

        if (!$this->runGitCommand("git pull", "Error during pull.")) {
            return 1;
        }

        if (!$this->runGitCommand("git merge -X theirs main --no-ff", "Error during merge.")) {
            return 1;
        }

        if (!$this->runGitCommand("git push", "Error during push.")) {
            return 1;
        }

        if (!$this->runGitCommand("git checkout main", "Changing branch to main error")) {
            return 1;
        }

        $this->info("\n❎ Error: push completed. Deploy pipeline for $env has started.");
        return 0;
    }

    /**
     * Check if the Git repository is clean (no uncommitted changes)
     */
    private function checkGitClean(): bool
    {
        $stashList = trim(shell_exec("git stash list"));
        if (!empty($stashList)) {
            $this->error("\n❌ Error: there are changes in the stash. Please clean the stash before proceeding.\n");
            return false;
        }

        $statusList = trim(shell_exec("git status --porcelain"));
        if (!empty($statusList)) {
            $this->error("\n❌ Error: there are uncommitted changes. Please clean the working directory before proceeding.\n");
            return false;
        }

        return true;
    }

    /**
     * Execute a Git command and print the output
     * @param string $command The Git command to execute
     * @param string $errorMessage The error message to print in case of failure
     * @return bool True if the command was successful, false otherwise
     */
    private function runGitCommand(string $command, string $errorMessage): bool
    {
        $this->info("\n⚙️  Executing: $command");
        $process = Process::fromShellCommandline($command, base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($errorMessage);
            $this->error($process->getErrorOutput());
            return false;
        }

        $this->info($process->getOutput());
        return true;
    }
}
