<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Json\BatchCommandResult;
use RavenDB\primitives\CleanCloseable;

// @todo: implement this
class SingleNodeBatchCommand extends RavenCommand implements CleanCloseable
{
    private bool $supportsAtomicWrites = false;
    private array $attachmentStreams = [];
    private ?DocumentConventions $conventions;
    private array $commands = [];
    private ?BatchOptions $options;
    private TransactionMode $mode;

    /**
     * @throws IllegalArgumentException
     */
    public function __construct(
        ?DocumentConventions $conventions,
        ?array $commands,
        ?BatchOptions $options = null,
        ?TransactionMode $mode = null
    ) {
        parent::__construct(BatchCommandResult::class);
        $this->conventions = $conventions;
        $this->commands = $commands ?? [];
        $this->options = $options;
        $this->mode = $mode ?? TransactionMode::singleNode();

        if ($conventions == null) {
            throw new IllegalArgumentException("conventions cannot be null");
        }

        if ($commands == null) {
            throw new IllegalArgumentException("commands cannot be null");
        }

        /** @var CommandDataInterface $command */
        foreach ($commands as $command) {
            if ($command instanceof PutAttachmentCommandData) {

                /** @var PutAttachmentCommandData $putAttachmentCommandData */
                $putAttachmentCommandData = $command;

                if ($this->$this->attachmentStreams == null) {
                    $this->attachmentStreams = [];
                }

                $stream = $putAttachmentCommandData->getStream();
                if (in_array($stream, $this->attachmentStreams)) {
                    PutAttachmentCommandHelper::throwStreamWasAlreadyUsed();
                } else {
                    $this->attachmentStreams[] = $stream;
                }
            }
        }
    }



    protected function createUrl(ServerNode $serverNode): string
    {
        // TODO: Implement createUrl() method.
        return "";
    }

    public function close(): void
    {
        // TODO: Implement close() method.
    }

    public function isReadRequest(): boolean
    {
        return false;
    }
}
