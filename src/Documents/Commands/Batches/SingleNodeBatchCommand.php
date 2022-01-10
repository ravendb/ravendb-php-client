<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ResultInterface;
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

//          @Override
//    public HttpRequestBase createRequest(ServerNode node, Reference<String> url) {
//        HttpPost request = new HttpPost();
//
//        request.setEntity(new ContentProviderHttpEntity(outputStream -> {
//            try (JsonGenerator generator = mapper.getFactory().createGenerator(outputStream)) {
//                if (_supportsAtomicWrites == null || node.isSupportsAtomicClusterWrites() != _supportsAtomicWrites) {
//                    _supportsAtomicWrites = node.isSupportsAtomicClusterWrites();
//                }
//
//                generator.writeStartObject();
//                generator.writeFieldName("Commands");
//                generator.writeStartArray();
//
//                if (_supportsAtomicWrites) {
//                    for (ICommandData command : _commands) {
//                        command.serialize(generator, _conventions);
//                    }
//                } else {
//                    for (ICommandData command : _commands) {
//                        ByteArrayOutputStream baos = new ByteArrayOutputStream();
//                        try (JsonGenerator itemGenerator = mapper.createGenerator(baos)) {
//                            command.serialize(itemGenerator, _conventions);
//                        }
//
//                        ObjectNode itemNode = (ObjectNode) mapper.readTree(baos.toByteArray());
//                        itemNode.remove("OriginalChangeVector");
//                        generator.writeObject(itemNode);
//                    }
//                }
//
//                generator.writeEndArray();
//
//                if (_mode == TransactionMode.CLUSTER_WIDE) {
//                    generator.writeStringField("TransactionMode", "ClusterWide");
//                }
//
//                generator.writeEndObject();
//            } catch (IOException e) {
//                throw new RuntimeException(e);
//            }
//        }, ContentType.APPLICATION_JSON));
//
//
//        if (_attachmentStreams != null && _attachmentStreams.size() > 0) {
//            MultipartEntityBuilder entityBuilder = MultipartEntityBuilder.create();
//
//            HttpEntity entity = request.getEntity();
//
//            try {
//                ByteArrayOutputStream baos = new ByteArrayOutputStream();
//                entity.writeTo(baos);
//
//                entityBuilder.addBinaryBody("main", new ByteArrayInputStream(baos.toByteArray()));
//            } catch (IOException e) {
//                throw new RavenException("Unable to serialize BatchCommand", e);
//            }
//
//            int nameCounter = 1;
//
//            for (InputStream stream : _attachmentStreams) {
//                InputStreamBody inputStreamBody = new InputStreamBody(stream, (String) null);
//                FormBodyPart part = FormBodyPartBuilder.create("attachment" + nameCounter++, inputStreamBody)
//                        .addField("Command-Type", "AttachmentStream")
//                        .build();
//                entityBuilder.addPart(part);
//            }
//            request.setEntity(entityBuilder.build());
//        }
//
//        StringBuilder sb = new StringBuilder(node.getUrl() + "/databases/" + node.getDatabase() + "/bulk_docs?");
//        appendOptions(sb);
//
//        url.value = sb.toString();
//        return request;
//    }

    protected function createUrl(ServerNode $serverNode): string
    {
        // TODO: Implement createUrl() method.
        return "";
    }

//          @Override
//    public void setResponse(String response, boolean fromCache) throws IOException {
//        if (response == null) {
//            throw new IllegalStateException("Got null response from the server after doing a batch, something is very wrong. Probably a garbled response.");
//        }
//
//        result = mapper.readValue(response, BatchCommandResult.class);
//    }
//
//    protected void appendOptions(StringBuilder sb) {
//        if (_options == null) {
//            return;
//        }
//
//        ReplicationBatchOptions replicationOptions = _options.getReplicationOptions();
//        if (replicationOptions != null) {
//            sb.append("&waitForReplicasTimeout=")
//                    .append(TimeUtils.durationToTimeSpan(replicationOptions.getWaitForReplicasTimeout()));
//
//            sb.append("&throwOnTimeoutInWaitForReplicas=")
//                    .append(replicationOptions.isThrowOnTimeoutInWaitForReplicas() ? "true" : "false");
//
//            sb.append("&numberOfReplicasToWaitFor=");
//            sb.append(replicationOptions.isMajority() ? "majority" : replicationOptions.getNumberOfReplicasToWaitFor());
//        }
//
//        IndexBatchOptions indexOptions = _options.getIndexOptions();
//        if (indexOptions != null) {
//            sb.append("&waitForIndexesTimeout=")
//                    .append(TimeUtils.durationToTimeSpan(indexOptions.getWaitForIndexesTimeout()));
//
//            if (indexOptions.isThrowOnTimeoutInWaitForIndexes()) {
//                sb.append("&waitForIndexThrow=true");
//            } else {
//                sb.append("&waitForIndexThrow=false");
//            }
//
//            if (indexOptions.getWaitForSpecificIndexes() != null) {
//                for (String specificIndex : indexOptions.getWaitForSpecificIndexes()) {
//                    sb.append("&waitForSpecificIndex=").append(urlEncode(specificIndex));
//                }
//            }
//        }
//    }

    public function getResult(): BatchCommandResult
    {
        /** @var BatchCommandResult $result */
        $result = parent::getResult();

        return $result;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function close(): void
    {
        // empty
    }

}
