<?php

declare(strict_types=1);

namespace Testcontainers\Wait;

use Closure;
use Symfony\Component\Process\Process;
use Testcontainers\Exception\ContainerNotReadyException;

class WaitForExec implements WaitInterface
{
    /** @var array<array-key, string> */
    private array $command = [];
    private ?Closure $checkFunction = null;

    /**
     * @param array<string> $command
     */
    public function __construct(array $command, ?Closure $checkFunction = null)
    {
        $this->command = $command;
        $this->checkFunction = $checkFunction;
    }

    public function wait(string $id): void
    {
        $process = new Process(['docker', 'exec', $id, ...$this->command]);

        try {
            $process->mustRun();
        } catch (\Exception $e) {
            throw new ContainerNotReadyException($id, $e);
        }

        if ($this->checkFunction !== null) {
            $func = $this->checkFunction;
            $func($process);
        }
    }
}
