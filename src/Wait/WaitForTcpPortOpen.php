<?php

declare(strict_types=1);

namespace Testcontainers\Wait;

use JsonException;
use RuntimeException;
use Testcontainers\Exception\ContainerNotReadyException;
use Testcontainers\Traits\DockerContainerAwareTrait;

final class WaitForTcpPortOpen implements WaitInterface
{
    use DockerContainerAwareTrait;

    private int $port;
    private ?string $network = null;

    public function __construct(int $port, ?string $network = null)
    {
        $this->port = $port;
        $this->network = $network;
    }

    public static function make(int $port, ?string $network = null): self
    {
        return new self($port, $network);
    }

    /**
     * @throws JsonException
     */
    public function wait(string $id): void
    {
        if (@fsockopen(self::dockerContainerAddress($id, $this->network), $this->port) === false) {
            throw new ContainerNotReadyException($id, new RuntimeException('Unable to connect to container TCP port'));
        }
    }
}
