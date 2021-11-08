<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\DocumentStoreBase;
use RavenDB\Exceptions\IllegalArgumentException;

class SessionInfo
{
    private InMemoryDocumentSessionOperations $session;

    private bool $canUseLoadBalanceBehavior;
    private int $lastClusterTransactionIndex;
    private bool $noCaching;
    private int $loadBalancerContextSeed;


    /**
     * @throws IllegalArgumentException
     */
    public function __construct(
        ?InMemoryDocumentSessionOperations $session,
        SessionOptions $options,
        ?DocumentStoreBase $documentStore
    ) {
        if ($documentStore === null) {
            throw new IllegalArgumentException("DocumentStore cannot be null");
        }
        if ($session == null) {
            throw new IllegalArgumentException("Session cannot be null");
        }

        $this->session = $session;
        $this->loadBalancerContextSeed = $session->getRequestExecutor()->getConventions()->getLoadBalancerContextSeed();
        $this->canUseLoadBalanceBehavior = $session->getConventions()->getLoadBalanceBehavior()->isUseSessionContext()
            && $session->getConventions()->getLoadBalancerPerSessionContextSelector() != null;

        $this->setLastClusterTransactionIndex($documentStore->getLastTransactionIndex($session->getDatabaseName()));
        $this->noCaching = $options->isNoCaching();
    }


    public function isCanUseLoadBalanceBehavior(): bool
    {
        return $this->canUseLoadBalanceBehavior;
    }

    public function getLastClusterTransactionIndex(): int
    {
        return $this->lastClusterTransactionIndex;
    }

    public function setLastClusterTransactionIndex(int $lastClusterTransactionIndex): void
    {
        $this->lastClusterTransactionIndex = $lastClusterTransactionIndex;
    }

    public function isNoCaching(): bool
    {
        return $this->noCaching;
    }

    public function setNoCaching(bool $noCaching): void
    {
        $this->noCaching = $noCaching;
    }

//    public void setContext(String sessionKey) {
//        if (StringUtils.isBlank(sessionKey)) {
//            throw new IllegalArgumentException("Session key cannot be null or whitespace.");
//        }
//
//      setContextInternal(sessionKey);
//
//      _canUseLoadBalanceBehavior =
//          _canUseLoadBalanceBehavior ||
//          _session.getConventions().getLoadBalanceBehavior() == LoadBalanceBehavior.USE_SESSION_CONTEXT;
//  }
//
//private void setContextInternal(String sessionKey) {
//    if (_sessionIdUsed) {
//        throw new IllegalStateException(
//              "Unable to set the session context after it has already been used. " .
//              "The session context can only be modified before it is utilized."
//        );
//    }
//
//    if (sessionKey == null) {
//        Integer v = _clientSessionIdCounter.get();
//            _sessionId = ++v;
//            _clientSessionIdCounter.set(v);
//        } else {
//
//        byte[] sessionKeyBytes = sessionKey.getBytes();
//            byte[] bytesToHash = ByteBuffer
//            .allocate(sessionKeyBytes.length + 4)
//            .put(sessionKeyBytes)
//            .putInt(_loadBalancerContextSeed)
//            .array();
//            byte[] md5Bytes = DigestUtils.md5(bytesToHash);
//            _sessionId = ByteBuffer.wrap(md5Bytes)
//                .getInt();
//        }
//}
//
//    public ServerNode getCurrentSessionNode(RequestExecutor requestExecutor) {
//    CurrentIndexAndNode result;
//
//        if (requestExecutor.getConventions().getLoadBalanceBehavior() == LoadBalanceBehavior.USE_SESSION_CONTEXT) {
//            if (_canUseLoadBalanceBehavior) {
//                result = requestExecutor.getNodeBySessionId(getSessionId());
//                return result.currentNode;
//            }
//        }
//
//        switch (requestExecutor.getConventions().getReadBalanceBehavior()) {
//            case NONE:
//                result = requestExecutor.getPreferredNode();
//                break;
//            case ROUND_ROBIN:
//                result = requestExecutor.getNodeBySessionId(getSessionId());
//                break;
//            case FASTEST_NODE:
//                result = requestExecutor.getFastestNode();
//                break;
//            default:
//                throw new IllegalArgumentException(
//                    requestExecutor.getConventions().getReadBalanceBehavior().toString()
//                );
//        }
//
//        return result.currentNode;
//    }

//    public Integer getSessionId() {
//        if (_sessionId == null) {
//            String context = null;
//            Function<String, String> selector =
//                    _session.getConventions().getLoadBalancerPerSessionContextSelector();
//            if (selector != null) {
//                context = selector.apply(_session.getDatabaseName());
//            }
//        setContextInternal(context);
//        }
//        _sessionIdUsed = true;
//        return _sessionId;
//    }
}
