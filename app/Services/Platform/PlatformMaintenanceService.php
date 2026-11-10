<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class PlatformMaintenanceService
{
    /** @return array{environment: string, laravel_version: string, php_version: string, branch: string, commit: string} */
    public function status(): array
    {
        return [
            'environment' => app()->environment(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'branch' => $this->gitValue(['git', 'branch', '--show-current']),
            'commit' => $this->gitValue(['git', 'rev-parse', '--short', 'HEAD']),
        ];
    }

    /** @return array{operation: string, success: bool, output: string, ran_at: string} */
    public function execute(string $operation): array
    {
        return Cache::lock('platform-maintenance-command', 180)->block(1, function () use ($operation): array {
            $output = match ($operation) {
                'pull' => $this->pullLatestCode(),
                'optimize_clear' => $this->runArtisanCommand('optimize:clear'),
                'migrate' => $this->runArtisanCommand('migrate', ['--force' => true]),
                'clear_logs' => $this->clearLogs(),
                default => throw new RuntimeException('Unsupported maintenance operation.'),
            };

            return [
                'operation' => $operation,
                'success' => true,
                'output' => mb_substr($output, -20000),
                'ran_at' => now()->toIso8601String(),
            ];
        });
    }

    private function pullLatestCode(): string
    {
        $process = new Process(
            ['git', 'pull', '--ff-only', 'origin', 'main'],
            base_path(),
            timeout: 120,
        );
        $process->run();
        $output = trim($process->getOutput().PHP_EOL.$process->getErrorOutput());

        if (! $process->isSuccessful()) {
            throw new RuntimeException($output ?: 'Git pull failed without output.');
        }

        return $output ?: 'Repository is already up to date.';
    }

    /** @param array<string, bool|string|int|array<int, string>> $parameters */
    private function runArtisanCommand(string $command, array $parameters = []): string
    {
        $exitCode = Artisan::call($command, $parameters);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            throw new RuntimeException($output ?: "Artisan command {$command} failed with exit code {$exitCode}.");
        }

        return $output ?: "php artisan {$command} completed successfully.";
    }

    private function clearLogs(): string
    {
        $logFiles = collect(File::glob(storage_path('logs/*.log')))
            ->filter(fn (string $path): bool => File::isFile($path));

        if ($logFiles->isEmpty()) {
            return 'No Laravel log files were found.';
        }

        $logFiles->each(fn (string $path) => File::put($path, ''));

        return 'Cleared '.$logFiles->count().' Laravel log file(s):'.PHP_EOL
            .$logFiles->map(fn (string $path): string => '- '.basename($path))->implode(PHP_EOL);
    }

    /** @param array<int, string> $command */
    private function gitValue(array $command): string
    {
        $process = new Process($command, base_path(), timeout: 10);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : 'Unavailable';
    }
}
