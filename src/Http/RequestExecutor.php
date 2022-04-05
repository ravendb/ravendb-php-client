<?php

namespace RavenDB\Http;

use DateTime;
use Ds\Map as DSMap;
use Exception;
use RavenDB\Auth\AuthOptions;
use RavenDB\Constants\HttpStatusCode;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\SessionInfo;
use RavenDB\Documents\Session\TopologyUpdatedEventArgs;
use RavenDB\Exceptions\AllTopologyNodesDownException;
use RavenDB\Exceptions\Database\DatabaseDoesNotExistException;
use RavenDB\Exceptions\ExceptionDispatcher;
use RavenDB\Exceptions\ExceptionSchema;
use RavenDB\Exceptions\ExecutionException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\Security\AuthorizationException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\Adapter\HttpClient;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\EventHelper;
use RavenDB\Primitives\ExceptionsUtils;
use RavenDB\Type\Duration;
use RavenDB\Type\UrlArray;
use RavenDB\Utils\AtomicInteger;
use RavenDB\Utils\UrlUtils;

// !status: IN PROGRESS
class RequestExecutor implements CleanCloseable
{
//    private static UUID GLOBAL_APPLICATION_IDENTIFIER = UUID.randomUUID();
//
//    private static final int INITIAL_TOPOLOGY_ETAG = -2;
//
//    public static Consumer<HttpClientBuilder> configureHttpClient = null;
//
//    private static final GetStatisticsOperation backwardCompatibilityFailureCheckOperation = new GetStatisticsOperation("failure=check");
//
//    private static final DatabaseHealthCheckOperation failureCheckOperation = new DatabaseHealthCheckOperation();
//    private static Set<String> _useOldFailureCheckOperation = ConcurrentHashMap.newKeySet();
//
//    /**
//     * Extension point to plug - in request post processing like adding proxy etc.
//     */
//    public static Consumer<HttpRequestBase> requestPostProcessor = null;
//
//    public static final String CLIENT_VERSION = "5.2.0";
//
//    private static final ConcurrentMap<String, CloseableHttpClient> globalHttpClientWithCompression = new ConcurrentHashMap<>();
//    private static final ConcurrentMap<String, CloseableHttpClient> globalHttpClientWithoutCompression = new ConcurrentHashMap<>();
//
//    private final Semaphore _updateDatabaseTopologySemaphore = new Semaphore(1);
//
//    private final Semaphore _updateClientConfigurationSemaphore = new Semaphore(1);
//
//    private final ConcurrentMap<ServerNode, NodeStatus> _failedNodesTimers = new ConcurrentHashMap<>();

    private string $databaseName;

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    private ?AuthOptions $authOptions = null;

    public function getAuthOptions(): ?AuthOptions
    {
        return $this->authOptions;
    }

    public function setAuthOptions(AuthOptions $authOptions): void
    {
        $this->authOptions = $authOptions;
    }

//
//    private static final Log logger = LogFactory.getLog(RequestExecutor.class);
    private DateTime $lastReturnedResponse;
//
//    protected final ExecutorService _executorService;
//
//    private final HttpCache cache;
//
//    private ServerNode _topologyTakenFromNode;
//
//    public HttpCache getCache() {
//        return cache;
//    }
//
//    public final ThreadLocal<AggressiveCacheOptions> aggressiveCaching = new ThreadLocal<>();
//
    public function getTopology(): ?Topology
    {
        return $this->nodeSelector != null ? $this->nodeSelector->getTopology() : null;
    }

    private ?HttpClientInterface $httpClient = null;

    private function getHttpClient(): HttpClientInterface
    {
        if ($this->httpClient == null) {
            $this->httpClient = $this->createHttpClient();
        }

        return $this->httpClient;
    }


    public function getTopologyNodes(): ?ServerNodeArray
    {
        return null;

        // @todo: Check with Marcin what this code does and implement it

//        return Optional.ofNullable(getTopology())
//                .map(Topology::getNodes)
//                .map(Collections::unmodifiableList)
//                .orElse(null);
    }

//    private volatile Timer _updateTopologyTimer;

    private ?NodeSelector $nodeSelector = null;

    public function getNodeSelector(): ?NodeSelector
    {
        return $this->nodeSelector;
    }

    public function setNodeSelector(?NodeSelector $nodeSelector): void
    {
        $this->nodeSelector = $nodeSelector;
    }
    private Duration $defaultTimeout;

    public AtomicInteger $numberOfServerRequests;

    /**
     * @throws DatabaseDoesNotExistException
     */
    public function getUrl(): ?string
    {
        if ($this->nodeSelector == null) {
            return null;
        }

        $preferredNode = $this->nodeSelector->getPreferredNode();

        return $preferredNode != null ? $preferredNode->currentNode->getUrl() : null;
    }

    protected int $topologyEtag = -2;

    public function getTopologyEtag(): int
    {
        return $this->topologyEtag;
    }

    protected int $clientConfigurationEtag = 0;

    public function getClientConfigurationEtag(): int
    {
        return $this->clientConfigurationEtag;
    }

    private DocumentConventions $conventions;

    protected bool $disableTopologyUpdates = false;

    protected bool $disableClientConfigurationUpdates = false;

    protected string $lastServerVersion = '';

    public function getLastServerVersion(): string
    {
        return $this->lastServerVersion;
    }

    public function getDefaultTimeout(): Duration
    {
        return $this->defaultTimeout;
    }

    public function setDefaultTimeout(Duration $timeout): void {
        $this->defaultTimeout = $timeout;
    }

    private ?Duration $secondBroadcastAttemptTimeout = null;

    public function getSecondBroadcastAttemptTimeout(): ?Duration {
        return $this->secondBroadcastAttemptTimeout;
    }

    public function setSecondBroadcastAttemptTimeout(?Duration $secondBroadcastAttemptTimeout): void
    {
        $this->secondBroadcastAttemptTimeout = $secondBroadcastAttemptTimeout;
    }

    private ?Duration $firstBroadcastAttemptTimeout = null;

    public function getFirstBroadcastAttemptTimeout(): ?Duration
    {
        return $this->firstBroadcastAttemptTimeout;
    }

    public function setFirstBroadcastAttemptTimeout(Duration $firstBroadcastAttemptTimeout): void
    {
        $this->firstBroadcastAttemptTimeout = $firstBroadcastAttemptTimeout;
    }

//    private final List<EventHandler<FailedRequestEventArgs>> _onFailedRequest = new ArrayList<>();
//
//    public void addOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler) {
//        this._onFailedRequest.add(handler);
//    }
//
//    public void removeOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler) {
//        this._onFailedRequest.remove(handler);
//    }
//
//    private final List<EventHandler<BeforeRequestEventArgs>> _onBeforeRequest = new ArrayList<>();
//
//    public void addOnBeforeRequestListener(EventHandler<BeforeRequestEventArgs> handler) {
//        this._onBeforeRequest.add(handler);
//    }
//
//    public void removeOnBeforeRequestListener(EventHandler<BeforeRequestEventArgs> handler) {
//        this._onBeforeRequest.remove(handler);
//    }
//
//    private final List<EventHandler<SucceedRequestEventArgs>> _onSucceedRequest = new ArrayList<>();
//
//    public void addOnSucceedRequestListener(EventHandler<SucceedRequestEventArgs> handler) {
//        this._onSucceedRequest.add(handler);
//    }
//
//    public void removeOnSucceedRequestListener(EventHandler<SucceedRequestEventArgs> handler) {
//        this._onSucceedRequest.remove(handler);
//    }
//
    private  ClosureArray $onTopologyUpdated;
//
//    public void addOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler) {
//        _onTopologyUpdated.add(handler);
//    }
//
//    public void removeOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler) {
//        _onTopologyUpdated.remove(handler);
//    }
//
//    private void onFailedRequestInvoke(String url, Exception e) {
//        EventHelper.invoke(_onFailedRequest, this, new FailedRequestEventArgs(_databaseName, url, e));
//    }
//
    private function createHttpClient(): HttpClientInterface
    {
        return new HttpClient();
//        ConcurrentMap<String, CloseableHttpClient> httpClientCache = getHttpClientCache();
//
//        String name = getHttpClientName();
//
//        return httpClientCache.computeIfAbsent(name, n -> createClient());
    }

//    private String getHttpClientName() {
//        if (certificate != null) {
//            return CertificateUtils.extractThumbprintFromCertificate(certificate);
//        }
//        return "";
//    }
//
//    private ConcurrentMap<String, CloseableHttpClient> getHttpClientCache() {
//        return conventions.isUseCompression() ? globalHttpClientWithCompression : globalHttpClientWithoutCompression;
//    }

    public function getConventions(): DocumentConventions
    {
        return $this->conventions;
    }

//    public KeyStore getCertificate() {
//        return certificate;
//    }
//
//    public char[] getKeyPassword() {
//        return keyPassword;
//    }
//
//    public KeyStore getTrustStore() {
//        return trustStore;
//    }

    protected function __construct(
        ?string $databaseName,
        ?AuthOptions $authOptions,
//        KeyStore certificate,
//        char[] keyPassword,
//        KeyStore trustStore,
        DocumentConventions $conventions
//        ExecutorService executorService,
//        String[] initialUrls

    ) {

        $this->onTopologyUpdated = new ClosureArray();

        // --

        $this->numberOfServerRequests = new AtomicInteger(0);

//        cache = new HttpCache(conventions.getMaxHttpCacheSize());
//        _executorService = executorService;
        $this->databaseName = $databaseName ?? '';
        $this->authOptions = $authOptions;

        $this->lastReturnedResponse = new DateTime();
        $this->conventions = $conventions;
//        this.conventions = conventions.clone();
        $this->defaultTimeout = $conventions->getRequestTimeout();
        $this->secondBroadcastAttemptTimeout = $conventions->getSecondBroadcastAttemptTimeout();
        $this->firstBroadcastAttemptTimeout = $conventions->getFirstBroadcastAttemptTimeout();
    }

    public static function create(
        UrlArray $initialUrls,
        ?string $databaseName,
        ?AuthOptions $authOptions,
//        ExecutorService executorService,
        DocumentConventions $conventions
    ): RequestExecutor {
        $executor = new RequestExecutor(
              $databaseName,
              $authOptions,
              $conventions
//              $executorService,
//              $initialUrls
        );
//        $executor->firstTopologyUpdate = executor.firstTopologyUpdate(initialUrls, GLOBAL_APPLICATION_IDENTIFIER);
//        return $executor;

        // @todo: check why I added this lines and probably it wont be needed so we can delete it
        // or we need this for SingleNode!!!!
        $serverNode = new ServerNode();
        $serverNode->setDatabase($databaseName);
        $serverNode->setUrl($initialUrls[0]);

        $topology = new Topology();
        $topology->setEtag(-1);
        $topology->getNodes()->append($serverNode);

//        $executor = new RequestExecutor($databaseName, $conventions);
        $executor->setNodeSelector(new NodeSelector($topology));

        return $executor;
    }

//
//    public static RequestExecutor createForSingleNodeWithConfigurationUpdates(String url, String databaseName, KeyStore certificate, char[] keyPassword, KeyStore trustStore, ExecutorService executorService, DocumentConventions conventions) {
//        RequestExecutor executor = createForSingleNodeWithoutConfigurationUpdates(url, databaseName, certificate, keyPassword, trustStore, executorService, conventions);
//        executor._disableClientConfigurationUpdates = false;
//        return executor;
//    }
//
//    public static RequestExecutor createForSingleNodeWithoutConfigurationUpdates(String url, String databaseName, KeyStore certificate, char[] keyPassword, KeyStore trustStore, ExecutorService executorService, DocumentConventions conventions) {
//        final String[] initialUrls = validateUrls(new String[]{url}, certificate);
//
//        RequestExecutor executor = new RequestExecutor(databaseName, certificate, keyPassword, trustStore, conventions, executorService, initialUrls);
//
//        Topology topology = new Topology();
//        topology.setEtag(-1L);
//
//        ServerNode serverNode = new ServerNode();
//        serverNode.setDatabase(databaseName);
//        serverNode.setUrl(initialUrls[0]);
//        topology.setNodes(Collections.singletonList(serverNode));
//
//        executor._nodeSelector = new NodeSelector(topology, executorService);
//        executor.topologyEtag = INITIAL_TOPOLOGY_ETAG;
//        executor._disableTopologyUpdates = true;
//        executor._disableClientConfigurationUpdates = true;
//
//        return executor;
//    }
//
//    protected CompletableFuture<Void> updateClientConfigurationAsync(ServerNode serverNode) {
//        if (_disposed) {
//            return CompletableFuture.completedFuture(null);
//        }
//
//        return CompletableFuture.runAsync(() -> {
//            try {
//                _updateClientConfigurationSemaphore.acquire();
//            } catch (InterruptedException e) {
//                throw new RuntimeException(e);
//            }
//
//            boolean oldDisableClientConfigurationUpdates = _disableClientConfigurationUpdates;
//            _disableClientConfigurationUpdates = true;
//
//            try {
//                if (_disposed) {
//                    return;
//                }
//
//                GetClientConfigurationOperation.GetClientConfigurationCommand command = new GetClientConfigurationOperation.GetClientConfigurationCommand();
//                execute(serverNode, null, command, false, null);
//
//                GetClientConfigurationOperation.Result result = command.getResult();
//                if (result == null) {
//                    return;
//                }
//
//                conventions.updateFrom(result.getConfiguration());
//                clientConfigurationEtag = result.getEtag();
//            } finally {
//                _disableClientConfigurationUpdates = oldDisableClientConfigurationUpdates;
//                _updateClientConfigurationSemaphore.release();
//            }
//        }, _executorService);
//    }
//
//    public CompletableFuture<Boolean> updateTopologyAsync(UpdateTopologyParameters parameters) {
//        if (parameters == null) {
//            throw new IllegalArgumentException("Parameters cannot be null");
//        }
//
//        if (_disableTopologyUpdates) {
//            return CompletableFuture.completedFuture(false);
//        }
//
//        if (_disposed) {
//            return CompletableFuture.completedFuture(false);
//        }
//
//        return CompletableFuture.supplyAsync(() -> {
//
//            //prevent double topology updates if execution takes too much time
//            // --> in cases with transient issues
//            try {
//                boolean lockTaken = _updateDatabaseTopologySemaphore.tryAcquire(parameters.getTimeoutInMs(), TimeUnit.MILLISECONDS);
//                if (!lockTaken) {
//                    return false;
//                }
//            } catch (InterruptedException e) {
//                throw new RuntimeException(e);
//            }
//
//            try {
//
//                if (_disposed) {
//                    return false;
//                }
//
//                GetDatabaseTopologyCommand command = new GetDatabaseTopologyCommand(parameters.getDebugTag(),
//                        getConventions().isSendApplicationIdentifier() ? parameters.getApplicationIdentifier() : null);
//                execute(parameters.getNode(), null, command, false, null);
//                Topology topology = command.getResult();
//
//                if (_nodeSelector == null) {
//                    _nodeSelector = new NodeSelector(topology, _executorService);
//
//                    if (conventions.getReadBalanceBehavior() == ReadBalanceBehavior.FASTEST_NODE) {
//                        _nodeSelector.scheduleSpeedTest();
//                    }
//                } else if (_nodeSelector.onUpdateTopology(topology, parameters.isForceUpdate())) {
//                    disposeAllFailedNodesTimers();
//                    if (conventions.getReadBalanceBehavior() == ReadBalanceBehavior.FASTEST_NODE) {
//                        _nodeSelector.scheduleSpeedTest();
//                    }
//                }
//
//                topologyEtag = _nodeSelector.getTopology().getEtag();
//
//                onTopologyUpdatedInvoke(topology);
//            } catch (Exception e) {
//                if (!_disposed) {
//                    throw e;
//                }
//            } finally {
//                _updateDatabaseTopologySemaphore.release();
//            }
//
//            return true;
//        }, _executorService);
//
//    }
//
//    protected void disposeAllFailedNodesTimers() {
//        _failedNodesTimers.forEach((node, status) -> status.close());
//        _failedNodesTimers.clear();
//    }

    public function execute(
        RavenCommand $command,
        ?SessionInfo $sessionInfo = null,
        ?ExecuteOptions $options = null
    ): void {
        if ($options) {
            $this->executeOnSpecificNode($command, $sessionInfo, $options);
        }

        $currentIndexAndNode = $this->chooseNodeForRequest($command, $sessionInfo);

        $executeOptions = new ExecuteOptions();
        $executeOptions->setNodeIndex($currentIndexAndNode->currentIndex);
        $executeOptions->setChosenNode($currentIndexAndNode->currentNode);
        $executeOptions->setShouldRetry(true);

        $this->executeOnSpecificNode($command, $sessionInfo, $executeOptions);
    }

    public function chooseNodeForRequest(?RavenCommand $cmd, ?SessionInfo $sessionInfo): CurrentIndexAndNode
    {
        $preferredNode = $this->nodeSelector->getPreferredNode();
        return new CurrentIndexAndNode($preferredNode->currentIndex, $preferredNode->currentNode);

//        if (!_disableTopologyUpdates) {
//            // when we disable topology updates we cannot rely on the node tag,
//            // because the initial topology will not have them
//
//            if (StringUtils.isNotBlank(cmd.getSelectedNodeTag())) {
//                return _nodeSelector.getRequestedNode(cmd.getSelectedNodeTag());
//            }
//        }
//
//        if (conventions.getLoadBalanceBehavior() == LoadBalanceBehavior.USE_SESSION_CONTEXT) {
//            if (sessionInfo != null && sessionInfo.canUseLoadBalanceBehavior()) {
//                return _nodeSelector.getNodeBySessionId(sessionInfo.getSessionId());
//            }
//        }
//
//        if (!cmd.isReadRequest()) {
//            return _nodeSelector.getPreferredNode();
//        }
//
//        switch (conventions.getReadBalanceBehavior()) {
//            case NONE:
//                return _nodeSelector.getPreferredNode();
//            case ROUND_ROBIN:
//                return _nodeSelector.getNodeBySessionId(sessionInfo != null ? sessionInfo.getSessionId() : 0);
//            case FASTEST_NODE:
//                return _nodeSelector.getFastestNode();
//            default:
//                throw new IllegalArgumentException();
//        }
    }

    private function executeOnSpecificNode(
        RavenCommand $command,
        ?SessionInfo $sessionInfo,
        ExecuteOptions $options
    ): void {

        $request = $this->createRequest($options->getChosenNode(), $command);

        if ($request == null) {
            return;
        }

        if ($this->authOptions != null) {
            if ($this->authOptions->getType()->isPem()) {
                $requestOptions = $request->getOptions();
                if (!array_key_exists('local_cert', $requestOptions)) {
                    $requestOptions['local_cert'] = $this->authOptions->getCertificatePath();
                }

                if (!array_key_exists('passphrase', $requestOptions)) {
                    $requestOptions['passphrase'] = $this->authOptions->getPassword();
                }

                $caPath = $this->authOptions->getCaPath();
                if (!array_key_exists('capath', $requestOptions) && !empty($caPath)) {
                    $requestOptions['capath'] = $caPath;
                }

                $caFile = $this->authOptions->getCaFile();
                if (!array_key_exists('cafile', $requestOptions) && !empty($caFile)) {
                    $requestOptions['cafile'] = $caFile;
                }

                $request->setOptions($requestOptions);
            }
        }

        $response = $this->sendRequestToServer(
            $options->getChosenNode(),
            $options->getNodeIndex(),
            $command,
            true,
            $sessionInfo,
            $request
        );

        if ($response == null) {
            return ;
        }



//        CompletableFuture<Void> refreshTask = refreshIfNeeded(chosenNode, response);
//
        $command->setStatusCode($response->getStatusCode());

        $responseDispose = ResponseDisposeHandling::automatic();

        try {
            if ($response->getStatusCode() == HttpStatusCode::NOT_MODIFIED) {
//                    EventHelper.invoke(_onSucceedRequest, this, new SucceedRequestEventArgs(_databaseName, urlRef.value, response, request, attemptNum));
//
//                    cachedItem.notModified();
//
//                    try {
//                        if (command.getResponseType() == RavenCommandResponseType.OBJECT) {
//                            command.setResponse(cachedValue.value, true);
//                        }
//                    } catch (IOException e) {
//                        throw ExceptionsUtils.unwrapException(e);
//                    }
//
//                    return;
                }

                if ($response->getStatusCode() >= 400) {
                    if (!$this->handleUnsuccessfulResponse(
                        $options->getChosenNode(),
                        $options->getNodeIndex(),
                        $command,
                        $request,
                        $response
//                        urlRef.value,
//                        sessionInfo,
//                        shouldRetry
                    )) {
                        $dbMissingHeader = $response->getFirstHeader("Database-Missing");
                        if ($dbMissingHeader != null) {
                            throw new DatabaseDoesNotExistException($dbMissingHeader);
                        }

                        $this->throwFailedToContactAllNodes($command, $request);
                    }
                    return; // we either handled this already in the unsuccessful response or we are throwing
                }

//                EventHelper.invoke(_onSucceedRequest, this, new SucceedRequestEventArgs(_databaseName, urlRef.value, response, request, attemptNum));
//
//                $responseDispose = $command->processResponse($cache, $response, $urlRef);
                $responseDispose = $command->processResponse(null, $response, $request->getUrl());
                $this->lastReturnedResponse = new DateTime();
        } finally {
                if ($responseDispose->isAutomatic()) {
                    // @todo: check what to do with this - initial idea is to do nothing 'cause this is Java
//                    IOUtils::closeQuietly($response, null);
                }
//
//                try {
//                    refreshTask.get();
//                } catch (Exception e) {
//                    //noinspection ThrowFromFinallyBlock
//                    throw ExceptionsUtils.unwrapException(e);
//                }
        }
    }

    private function createRequest(ServerNode $serverNode, RavenCommand $command): ?HttpRequestInterface
    {
        $request = $command->createRequest($serverNode);

        if ($request == null) {
            return null;
        }

        $url = $request->getUrl();

//        @todo: Implement following code
//        if ($this->requestPostProcessor != null) {
//            $this->requestPostProcessor->accept($request);
//        }

        if ($command instanceof RaftCommandInterface) {
            /** @var RaftCommandInterface $raftCommand */
            $raftCommand = $command;

            $url = UrlUtils::appendQuery($url, 'raft-request-id', $raftCommand->getRaftUniqueRequestId());
        }

//        if ($this->shouldBroadcast($command)) {
//            $command->setTimeout($command->getTimeout() ?? $this->firstBroadcastAttemptTimeout);
//        }

        $request->setUrl($url);

        return $request;
    }
//    public <TResult> void execute(RavenCommand<TResult> command) {
//        execute(command, null);
//    }
//
//    public <TResult> void execute(RavenCommand<TResult> command, SessionInfo sessionInfo) {
//        CompletableFuture<Void> topologyUpdate = _firstTopologyUpdate;
//        if (topologyUpdate != null &&
//                (topologyUpdate.isDone() && !topologyUpdate.isCompletedExceptionally() && !topologyUpdate.isCancelled())
//                || _disableTopologyUpdates) {
//            CurrentIndexAndNode currentIndexAndNode = chooseNodeForRequest(command, sessionInfo);
//            execute(currentIndexAndNode.currentNode, currentIndexAndNode.currentIndex, command, true, sessionInfo);
//        } else {
//            unlikelyExecute(command, topologyUpdate, sessionInfo);
//        }
//    }
//
//    public <TResult> CurrentIndexAndNode chooseNodeForRequest(RavenCommand<TResult> cmd, SessionInfo sessionInfo) {
//        if (!_disableTopologyUpdates) {
//            // when we disable topology updates we cannot rely on the node tag,
//            // because the initial topology will not have them
//
//            if (StringUtils.isNotBlank(cmd.getSelectedNodeTag())) {
//                return _nodeSelector.getRequestedNode(cmd.getSelectedNodeTag());
//            }
//        }
//
//        if (conventions.getLoadBalanceBehavior() == LoadBalanceBehavior.USE_SESSION_CONTEXT) {
//            if (sessionInfo != null && sessionInfo.canUseLoadBalanceBehavior()) {
//                return _nodeSelector.getNodeBySessionId(sessionInfo.getSessionId());
//            }
//        }
//
//        if (!cmd.isReadRequest()) {
//            return _nodeSelector.getPreferredNode();
//        }
//
//        switch (conventions.getReadBalanceBehavior()) {
//            case NONE:
//                return _nodeSelector.getPreferredNode();
//            case ROUND_ROBIN:
//                return _nodeSelector.getNodeBySessionId(sessionInfo != null ? sessionInfo.getSessionId() : 0);
//            case FASTEST_NODE:
//                return _nodeSelector.getFastestNode();
//            default:
//                throw new IllegalArgumentException();
//        }
//    }
//
//    private <TResult> void unlikelyExecute(RavenCommand<TResult> command, CompletableFuture<Void> topologyUpdate, SessionInfo sessionInfo) {
//        waitForTopologyUpdate(topologyUpdate);
//
//        CurrentIndexAndNode currentIndexAndNode = chooseNodeForRequest(command, sessionInfo);
//        execute(currentIndexAndNode.currentNode, currentIndexAndNode.currentIndex, command, true, sessionInfo);
//    }
//
//    private void waitForTopologyUpdate(CompletableFuture<Void> topologyUpdate) {
//        try {
//            if (topologyUpdate == null || topologyUpdate.isCompletedExceptionally()) {
//                synchronized (this) {
//                    if (_firstTopologyUpdate == null || topologyUpdate == _firstTopologyUpdate) {
//                        if (_lastKnownUrls == null) {
//                            // shouldn't happen
//                            throw new IllegalStateException("No known topology and no previously known one, cannot proceed, likely a bug");
//                        }
//                        _firstTopologyUpdate = firstTopologyUpdate(_lastKnownUrls, null);
//                    }
//
//                    topologyUpdate = _firstTopologyUpdate;
//                }
//            }
//
//            topologyUpdate.get();
//        } catch (InterruptedException | ExecutionException e) {
//            synchronized (this) {
//                if (_firstTopologyUpdate == topologyUpdate) {
//                    _firstTopologyUpdate = null; // next request will raise it
//                }
//            }
//
//            throw ExceptionsUtils.unwrapException(e);
//        }
//    }
//
//    private void updateTopologyCallback() {
//        Date time = new Date();
//        if (time.getTime() - _lastReturnedResponse.getTime() <= Duration.ofMinutes(5).toMillis()) {
//            return;
//        }
//
//        ServerNode serverNode;
//
//        try {
//            NodeSelector selector = _nodeSelector;
//            if (selector == null) {
//                return;
//            }
//            CurrentIndexAndNode preferredNode = selector.getPreferredNode();
//            serverNode = preferredNode.currentNode;
//        } catch (Exception e) {
//            if (logger.isInfoEnabled()) {
//                logger.info("Couldn't get preferred node Topology from _updateTopologyTimer", e);
//            }
//            return;
//        }
//
//        UpdateTopologyParameters updateParameters = new UpdateTopologyParameters(serverNode);
//        updateParameters.setTimeoutInMs(0);
//        updateParameters.setDebugTag("timer-callback");
//
//        updateTopologyAsync(updateParameters)
//                .exceptionally(ex -> {
//                    if (logger.isInfoEnabled()) {
//                        logger.info("Couldn't update topology from _updateTopologyTimer", ex);
//                    }
//                    return null;
//                });
//    }
//
//    protected CompletableFuture<Void> firstTopologyUpdate(String[] inputUrls) {
//        return firstTopologyUpdate(inputUrls, null);
//    }
//
//    @SuppressWarnings({"ConstantConditions"})
//    protected CompletableFuture<Void> firstTopologyUpdate(String[] inputUrls, UUID applicationIdentifier) {
//        final String[] initialUrls = validateUrls(inputUrls, certificate);
//
//        ArrayList<Tuple<String, Exception>> list = new ArrayList<>();
//
//        return CompletableFuture.runAsync(() -> {
//
//            for (String url : initialUrls) {
//                try {
//                    ServerNode serverNode = new ServerNode();
//                    serverNode.setUrl(url);
//                    serverNode.setDatabase(_databaseName);
//
//                    UpdateTopologyParameters updateParameters = new UpdateTopologyParameters(serverNode);
//                    updateParameters.setTimeoutInMs(Integer.MAX_VALUE);
//                    updateParameters.setDebugTag("first-topology-update");
//                    updateParameters.setApplicationIdentifier(applicationIdentifier);
//
//                    updateTopologyAsync(updateParameters).get();
//
//                    initializeUpdateTopologyTimer();
//
//                    _topologyTakenFromNode = serverNode;
//                    return;
//                } catch (Exception e) {
//
//                    if (e instanceof ExecutionException && e.getCause() instanceof AuthorizationException) {
//                        // auth exceptions will always happen, on all nodes
//                        // so errors immediately
//                        _lastKnownUrls = initialUrls;
//                        throw (AuthorizationException) e.getCause();
//                    }
//
//                    if (e instanceof ExecutionException && e.getCause() instanceof DatabaseDoesNotExistException) {
//                        // Will happen on all node in the cluster,
//                        // so errors immediately
//                        _lastKnownUrls = initialUrls;
//                        throw (DatabaseDoesNotExistException) e.getCause();
//                    }
//
//                    list.add(Tuple.create(url, e));
//                }
//            }
//
//            Topology topology = new Topology();
//            topology.setEtag(topologyEtag);
//
//            List<ServerNode> topologyNodes = getTopologyNodes();
//            if (topologyNodes == null) {
//                topologyNodes = Arrays.stream(initialUrls)
//                        .map(url -> {
//                            ServerNode serverNode = new ServerNode();
//                            serverNode.setUrl(url);
//                            serverNode.setDatabase(_databaseName);
//                            serverNode.setClusterTag("!");
//                            return serverNode;
//                        }).collect(Collectors.toList());
//            }
//
//            topology.setNodes(topologyNodes);
//
//            _nodeSelector = new NodeSelector(topology, _executorService);
//
//            if (initialUrls != null && initialUrls.length > 0) {
//                initializeUpdateTopologyTimer();
//                return;
//            }
//
//            _lastKnownUrls = initialUrls;
//            String details = list.stream().map(x -> x.first + " -> " + Optional.ofNullable(x.second).map(Throwable::getMessage).orElse("")).collect(Collectors.joining(", "));
//            throwExceptions(details);
//        }, _executorService);
//    }
//
//    protected void throwExceptions(String details) {
//        throw new IllegalStateException("Failed to retrieve database topology from all known nodes" + System.lineSeparator() + details);
//    }
//
//    public static String[] validateUrls(String[] initialUrls, KeyStore certificate) {
//        String[] cleanUrls = new String[initialUrls.length];
//        boolean requireHttps = certificate != null;
//        for (int index = 0; index < initialUrls.length; index++) {
//            String url = initialUrls[index];
//            try {
//                new URL(url);
//            } catch (MalformedURLException e) {
//                throw new IllegalArgumentException("'" + url + "' is not a valid url");
//            }
//
//            cleanUrls[index] = StringUtils.stripEnd(url, "/");
//            requireHttps |= url.startsWith("https://");
//        }
//
//        if (!requireHttps) {
//            return cleanUrls;
//        }
//
//        for (String url : initialUrls) {
//            if (!url.startsWith("http://")) {
//                continue;
//            }
//
//            if (certificate != null) {
//                throw new IllegalStateException("The url " + url + " is using HTTP, but a certificate is specified, which require us to use HTTPS");
//            }
//            throw new IllegalStateException("The url " + url + " is using HTTP, but other urls are using HTTPS, and mixing of HTTP and HTTPS is not allowed.");
//        }
//
//        return cleanUrls;
//    }
//
//    private void initializeUpdateTopologyTimer() {
//        if (_updateTopologyTimer != null) {
//            return;
//        }
//
//        synchronized (this) {
//            if (_updateTopologyTimer != null) {
//                return;
//            }
//
//            _updateTopologyTimer = new Timer(this::updateTopologyCallback, Duration.ofMinutes(1), Duration.ofMinutes(1), _executorService);
//        }
//    }
//
//    public <TResult> void execute(ServerNode chosenNode, Integer nodeIndex, RavenCommand<TResult> command, boolean shouldRetry, SessionInfo sessionInfo) {
//        execute(chosenNode, nodeIndex, command, shouldRetry, sessionInfo, null);
//    }
//
//    @SuppressWarnings({"ConstantConditions"})
//    public <TResult> void execute(ServerNode chosenNode, Integer nodeIndex, RavenCommand<TResult> command, boolean shouldRetry, SessionInfo sessionInfo, Reference<HttpRequestBase> requestRef) {
//        if (command.failoverTopologyEtag == INITIAL_TOPOLOGY_ETAG) {
//            command.failoverTopologyEtag = INITIAL_TOPOLOGY_ETAG;
//            if (_nodeSelector != null && _nodeSelector.getTopology() != null) {
//                Topology topology = _nodeSelector.getTopology();
//                if (topology.getEtag() != null) {
//                    command.failoverTopologyEtag = topology.getEtag();
//                }
//            }
//        }
//
//        Reference<String> urlRef = new Reference<>();
//        HttpRequestBase request = createRequest(chosenNode, command, urlRef);
//
//        if (request == null) {
//            return;
//        }
//
//        if (requestRef != null) {
//            requestRef.value = request;
//        }
//
//        if (request == null) {
//            return;
//        }
//
//        //noinspection SimplifiableConditionalExpression
//        boolean noCaching = sessionInfo != null ? sessionInfo.isNoCaching() : false;
//
//        Reference<String> cachedChangeVectorRef = new Reference<>();
//        Reference<String> cachedValue = new Reference<>();
//
//        try (HttpCache.ReleaseCacheItem cachedItem = getFromCache(command, !noCaching, urlRef.value, cachedChangeVectorRef, cachedValue)) {
//            if (cachedChangeVectorRef.value != null) {
//                if (tryGetFromCache(command, cachedItem, cachedValue.value)) {
//                    return;
//                }
//            }
//
//            setRequestHeaders(sessionInfo, cachedChangeVectorRef.value, request);
//
//            command.numberOfAttempts = command.numberOfAttempts + 1;
//            int attemptNum = command.numberOfAttempts;
//            EventHelper.invoke(_onBeforeRequest, this, new BeforeRequestEventArgs(_databaseName, urlRef.value, request, attemptNum));
//
//            CloseableHttpResponse response = sendRequestToServer(chosenNode, nodeIndex, command, shouldRetry, sessionInfo, request, urlRef.value);
//
//            if (response == null) {
//                return;
//            }
//
//            CompletableFuture<Void> refreshTask = refreshIfNeeded(chosenNode, response);
//
//            command.statusCode = response.getStatusLine().getStatusCode();
//
//            ResponseDisposeHandling responseDispose = ResponseDisposeHandling.AUTOMATIC;
//
//            try {
//                if (response.getStatusLine().getStatusCode() == HttpStatus.SC_NOT_MODIFIED) {
//                    EventHelper.invoke(_onSucceedRequest, this, new SucceedRequestEventArgs(_databaseName, urlRef.value, response, request, attemptNum));
//
//                    cachedItem.notModified();
//
//                    try {
//                        if (command.getResponseType() == RavenCommandResponseType.OBJECT) {
//                            command.setResponse(cachedValue.value, true);
//                        }
//                    } catch (IOException e) {
//                        throw ExceptionsUtils.unwrapException(e);
//                    }
//
//                    return;
//                }
//
//                if (response.getStatusLine().getStatusCode() >= 400) {
//                    if (!handleUnsuccessfulResponse(chosenNode, nodeIndex, command, request, response, urlRef.value, sessionInfo, shouldRetry)) {
//                        Header dbMissingHeader = response.getFirstHeader("Database-Missing");
//                        if (dbMissingHeader != null && dbMissingHeader.getValue() != null) {
//                            throw new DatabaseDoesNotExistException(dbMissingHeader.getValue());
//                        }
//
//                        throwFailedToContactAllNodes(command, request);
//                    }
//                    return; // we either handled this already in the unsuccessful response or we are throwing
//                }
//
//                EventHelper.invoke(_onSucceedRequest, this, new SucceedRequestEventArgs(_databaseName, urlRef.value, response, request, attemptNum));
//
//                responseDispose = command.processResponse(cache, response, urlRef.value);
//                _lastReturnedResponse = new Date();
//            } finally {
//                if (responseDispose == ResponseDisposeHandling.AUTOMATIC) {
//                    IOUtils.closeQuietly(response, null);
//                }
//
//                try {
//                    refreshTask.get();
//                } catch (Exception e) {
//                    //noinspection ThrowFromFinallyBlock
//                    throw ExceptionsUtils.unwrapException(e);
//                }
//            }
//        }
//    }
//
//    private CompletableFuture<Void> refreshIfNeeded(ServerNode chosenNode, CloseableHttpResponse response) {
//        Boolean refreshTopology = Optional.ofNullable(HttpExtensions.getBooleanHeader(response, Constants.Headers.REFRESH_TOPOLOGY)).orElse(false);
//        Boolean refreshClientConfiguration = Optional.ofNullable(HttpExtensions.getBooleanHeader(response, Constants.Headers.REFRESH_CLIENT_CONFIGURATION)).orElse(false);
//
//        if (refreshTopology || refreshClientConfiguration) {
//            ServerNode serverNode = new ServerNode();
//            serverNode.setUrl(chosenNode.getUrl());
//            serverNode.setDatabase(_databaseName);
//
//            UpdateTopologyParameters updateParameters = new UpdateTopologyParameters(serverNode);
//            updateParameters.setTimeoutInMs(0);
//            updateParameters.setDebugTag("refresh-topology-header");
//
//            CompletableFuture<Boolean> topologyTask = refreshTopology ? updateTopologyAsync(updateParameters) : CompletableFuture.completedFuture(false);
//            CompletableFuture<Void> clientConfiguration = refreshClientConfiguration ? updateClientConfigurationAsync(serverNode) : CompletableFuture.completedFuture(null);
//
//            return CompletableFuture.allOf(topologyTask, clientConfiguration);
//        }
//
//        return CompletableFuture.allOf();
//    }

    /**
     * @throws \Exception
     */
    private function sendRequestToServer(
        ServerNode $chosenNode,
        int $nodeIndex,
        RavenCommand $command,
        bool $shouldRetry,
        ?SessionInfo $sessionInfo,
        HttpRequestInterface $request
    ): ?HttpResponseInterface {

        try {
            $this->numberOfServerRequests->incrementAndGet();

            $timeout = $command->getTimeout() ?? $this->defaultTimeout;

            if ($timeout != null) {
                // @todo implement timeout strategy call
                // this call is just here to execute something for now, when implementing timout strategy we should remove it
                return $this->send($chosenNode, $command, $sessionInfo, $request);


//                AggressiveCacheOptions callingTheadAggressiveCaching = aggressiveCaching.get();
//
//                CompletableFuture<CloseableHttpResponse> sendTask = CompletableFuture.supplyAsync(() -> {
//                    AggressiveCacheOptions aggressiveCacheOptionsToRestore = aggressiveCaching.get();
//
//                    try {
//                        aggressiveCaching.set(callingTheadAggressiveCaching);
//                        return send(chosenNode, command, sessionInfo, request);
//                    } catch (IOException e) {
//                        throw ExceptionsUtils.unwrapException(e);
//                    } finally {
//                        aggressiveCaching.set(aggressiveCacheOptionsToRestore);
//                    }
//                }, _executorService);
//
//                try {
//                    return sendTask.get(timeout.toMillis(), TimeUnit.MILLISECONDS);
//                } catch (InterruptedException e) {
//                    throw ExceptionsUtils.unwrapException(e);
//                } catch (TimeoutException e) {
//                    request.abort();
//
//                    net.ravendb.client.exceptions.TimeoutException timeoutException = new net.ravendb.client.exceptions.TimeoutException("The request for " + request.getURI() + " failed with timeout after " + TimeUtils.durationToTimeSpan(timeout), e);
//                    if (!shouldRetry) {
//                        if (command.getFailedNodes() == null) {
//                            command.setFailedNodes(new HashMap<>());
//                        }
//
//                        command.getFailedNodes().put(chosenNode, timeoutException);
//                        throw timeoutException;
//                    }
//
//                    if (!handleServerDown(url, chosenNode, nodeIndex, command, request, null, timeoutException, sessionInfo, shouldRetry)) {
//                        throwFailedToContactAllNodes(command, request);
//                    }
//
//                    return null;
//                } catch (ExecutionException e) {
//                    Throwable rootCause = ExceptionUtils.getRootCause(e);
//                    if (rootCause instanceof IOException) {
//                        throw (IOException) rootCause;
//                    }
//
//                    throw ExceptionsUtils.unwrapException(e);
//                }
            } else {
                return $this->send($chosenNode, $command, $sessionInfo, $request);
            }
        } catch (\Throwable $exception) {
            if (!$shouldRetry) {
                throw ExceptionsUtils::unwrapException($exception);
            }

            $url = $request->getUrl();
//            if (!$this->handleServerDown($url, $chosenNode, $nodeIndex, $command, $request, null, $exception, $sessionInfo,  $shouldRetry)) {
//                throwFailedToContactAllNodes($command, $request);
//            }

            return null;
        }
    }

//    private <TResult> CloseableHttpResponse sendRequestToServer(ServerNode chosenNode, Integer nodeIndex, RavenCommand<TResult> command,
//                                                                boolean shouldRetry, SessionInfo sessionInfo, HttpRequestBase request, String url) {
//        try {
//            numberOfServerRequests.incrementAndGet();
//
//            Duration timeout = ObjectUtils.firstNonNull(command.getTimeout(), _defaultTimeout);
//            if (timeout != null) {
//                AggressiveCacheOptions callingTheadAggressiveCaching = aggressiveCaching.get();
//
//                CompletableFuture<CloseableHttpResponse> sendTask = CompletableFuture.supplyAsync(() -> {
//                    AggressiveCacheOptions aggressiveCacheOptionsToRestore = aggressiveCaching.get();
//
//                    try {
//                        aggressiveCaching.set(callingTheadAggressiveCaching);
//                        return send(chosenNode, command, sessionInfo, request);
//                    } catch (IOException e) {
//                        throw ExceptionsUtils.unwrapException(e);
//                    } finally {
//                        aggressiveCaching.set(aggressiveCacheOptionsToRestore);
//                    }
//                }, _executorService);
//
//                try {
//                    return sendTask.get(timeout.toMillis(), TimeUnit.MILLISECONDS);
//                } catch (InterruptedException e) {
//                    throw ExceptionsUtils.unwrapException(e);
//                } catch (TimeoutException e) {
//                    request.abort();
//
//                    net.ravendb.client.exceptions.TimeoutException timeoutException = new net.ravendb.client.exceptions.TimeoutException("The request for " + request.getURI() + " failed with timeout after " + TimeUtils.durationToTimeSpan(timeout), e);
//                    if (!shouldRetry) {
//                        if (command.getFailedNodes() == null) {
//                            command.setFailedNodes(new HashMap<>());
//                        }
//
//                        command.getFailedNodes().put(chosenNode, timeoutException);
//                        throw timeoutException;
//                    }
//
//                    if (!handleServerDown(url, chosenNode, nodeIndex, command, request, null, timeoutException, sessionInfo, shouldRetry)) {
//                        throwFailedToContactAllNodes(command, request);
//                    }
//
//                    return null;
//                } catch (ExecutionException e) {
//                    Throwable rootCause = ExceptionUtils.getRootCause(e);
//                    if (rootCause instanceof IOException) {
//                        throw (IOException) rootCause;
//                    }
//
//                    throw ExceptionsUtils.unwrapException(e);
//                }
//            } else {
//                return send(chosenNode, command, sessionInfo, request);
//            }
//        } catch (IOException e) {
//            if (!shouldRetry) {
//                throw ExceptionsUtils.unwrapException(e);
//            }
//
//            if (!handleServerDown(url, chosenNode, nodeIndex, command, request, null, e, sessionInfo, shouldRetry)) {
//                throwFailedToContactAllNodes(command, request);
//            }
//
//            return null;
//        }
//    }


    /**
     * @throws \RavenDB\Exceptions\InvalidResultAssignedToCommandException
     */
    private function send(
        ServerNode $chosenNode,
        RavenCommand $command,
        ?SessionInfo $sessionInfo,
        HttpRequestInterface $request
    ): HttpResponseInterface {
        $response = $command->send($this->getHttpClient(), $request);

        return $response;
    }
//    private <TResult> CloseableHttpResponse send(ServerNode chosenNode, RavenCommand<TResult> command, SessionInfo sessionInfo, HttpRequestBase request) throws IOException {
//        CloseableHttpResponse response = null;
//
//        if (shouldExecuteOnAll(chosenNode, command)) {
//            response = executeOnAllToFigureOutTheFastest(chosenNode, command);
//        } else {
//            response = command.send(getHttpClient(), request);
//        }
//
//        // PERF: The reason to avoid rechecking every time is that servers wont change so rapidly
//        //       and therefore we dimish its cost by orders of magnitude just doing it
//        //       once in a while. We dont care also about the potential race conditions that may happen
//        //       here mainly because the idea is to have a lax mechanism to recheck that is at least
//        //       orders of magnitude faster than currently.
//        if (chosenNode.shouldUpdateServerVersion()) {
//            String serverVersion = tryGetServerVersion(response);
//            if (serverVersion != null) {
//                chosenNode.updateServerVersion(serverVersion);
//
//            }
//        }
//
//        lastServerVersion = chosenNode.getLastServerVersion();
//
//        if (sessionInfo != null && sessionInfo.getLastClusterTransactionIndex() != null) {
//            // if we reach here it means that sometime a cluster transaction has occurred against this database.
//            // Since the current executed command can be dependent on that, we have to wait for the cluster transaction.
//            // But we can't do that if the server is an old one.
//
//            if (lastServerVersion == null || lastServerVersion.compareToIgnoreCase("4.1") < 0) {
//                throw new ClientVersionMismatchException("The server on " + chosenNode.getUrl() + " has an old version and can't perform " +
//                        "the command since this command dependent on a cluster transaction which this node doesn't support.");
//            }
//        }
//
//        return response;
//    }
//
//    private void setRequestHeaders(SessionInfo sessionInfo, String cachedChangeVector, HttpRequest request) {
//        if (cachedChangeVector != null) {
//            request.addHeader("If-None-Match", "\"" + cachedChangeVector + "\"");
//        }
//
//        if (!_disableClientConfigurationUpdates) {
//            request.addHeader(Constants.Headers.CLIENT_CONFIGURATION_ETAG, "\"" + clientConfigurationEtag + "\"");
//        }
//
//        if (sessionInfo != null && sessionInfo.getLastClusterTransactionIndex() != null) {
//            request.addHeader(Constants.Headers.LAST_KNOWN_CLUSTER_TRANSACTION_INDEX, sessionInfo.getLastClusterTransactionIndex().toString());
//        }
//
//        if (!_disableTopologyUpdates) {
//            request.addHeader(Constants.Headers.TOPOLOGY_ETAG, "\"" + topologyEtag + "\"");
//        }
//
//        if (request.getFirstHeader(Constants.Headers.CLIENT_VERSION) == null) {
//            request.addHeader(Constants.Headers.CLIENT_VERSION, RequestExecutor.CLIENT_VERSION);
//        }
//    }
//
//    private <TResult> boolean tryGetFromCache(RavenCommand<TResult> command, HttpCache.ReleaseCacheItem cachedItem, String cachedValue) {
//        AggressiveCacheOptions aggressiveCacheOptions = aggressiveCaching.get();
//        if (aggressiveCacheOptions != null &&
//                cachedItem.getAge().compareTo(aggressiveCacheOptions.getDuration()) < 0 &&
//                (!cachedItem.getMightHaveBeenModified() || aggressiveCacheOptions.getMode() != AggressiveCacheMode.TRACK_CHANGES) &&
//                command.canCacheAggressively()) {
//            try {
//                if (cachedItem.item.flags.contains(ItemFlags.NOT_FOUND)) {
//                    // if this is a cached delete, we only respect it if it _came_ from an aggressively cached
//                    // block, otherwise, we'll run the request again
//
//                    if (cachedItem.item.flags.contains(ItemFlags.AGGRESSIVELY_CACHED)) {
//                        command.setResponse(cachedValue, true);
//                        return true;
//                    }
//                } else {
//                    command.setResponse(cachedValue, true);
//                    return true;
//                }
//            } catch (IOException e) {
//                throw new RuntimeException(e);
//            }
//        }
//        return false;
//    }
//
//    private static String tryGetServerVersion(CloseableHttpResponse response) {
//        Header serverVersionHeader = response.getFirstHeader(Constants.Headers.SERVER_VERSION);
//
//        if (serverVersionHeader != null) {
//            return serverVersionHeader.getValue();
//        }
//
//        return null;
//    }


    private function throwFailedToContactAllNodes(RavenCommand $command, HttpRequestInterface $request): void
    {
        if (($command->getFailedNodes() == null) || $command->getFailedNodes()->count() == 0) { //precaution, should never happen at this point
            throw new IllegalStateException("Received unsuccessful response and couldn't recover from it. " .
                    "Also, no record of exceptions per failed nodes. This is weird and should not happen.");
        }

        if (count($command->getFailedNodes()) == 1) {
            throw ExceptionsUtils::unwrapException($command->getFailedNodes()->first()->value);
        }

        $message = 'Tried to send ' . $command->getResultClass() . " request via " . $request->getMethod()
                . ' ' . $request->getUrl() . " to all configured nodes in the topology, none of the attempt succeeded." . PHP_EOL;

//        if (_topologyTakenFromNode != null) {
//            message += "I was able to fetch " + _topologyTakenFromNode.getDatabase()
//                    + " topology from " + _topologyTakenFromNode.getUrl() + "." + System.lineSeparator();
//        }
//
//        List<ServerNode> nodes = null;
//        if (_nodeSelector != null && _nodeSelector.getTopology() != null) {
//            nodes = _nodeSelector.getTopology().getNodes();
//        }
//
//        if (nodes == null) {
//            message += "Topology is empty.";
//        } else {
//            message += "Topology: ";
//
//            for (ServerNode node : nodes) {
//                Exception exception = command.getFailedNodes().get(node);
//                message += System.lineSeparator() +
//                        "[Url: " + node.getUrl() + ", " +
//                        "ClusterTag: " + node.getClusterTag() + ", " +
//                        "ServerRole: " + node.getServerRole() + ", " +
//                        "Exception: " + (exception != null ? exception.getMessage() : "No exception") + "]";
//
//            }
//        }
//
        throw new AllTopologyNodesDownException($message);
    }
//
//    public boolean inSpeedTestPhase() {
//        return Optional.ofNullable(_nodeSelector).map(NodeSelector::inSpeedTestPhase).orElse(false);
//    }
//
//    private <TResult> boolean shouldExecuteOnAll(ServerNode chosenNode, RavenCommand<TResult> command) {
//        return conventions.getReadBalanceBehavior() == ReadBalanceBehavior.FASTEST_NODE &&
//                _nodeSelector != null &&
//                _nodeSelector.inSpeedTestPhase() &&
//                Optional.ofNullable(_nodeSelector)
//                        .map(NodeSelector::getTopology)
//                        .map(Topology::getNodes)
//                        .map(x -> x.size() > 1)
//                        .orElse(false) &&
//                command.isReadRequest() &&
//                command.getResponseType() == RavenCommandResponseType.OBJECT &&
//                chosenNode != null &&
//                !(command instanceof IBroadcast);
//    }
//
//    @SuppressWarnings("ConstantConditions")
//    private <TResult> CloseableHttpResponse executeOnAllToFigureOutTheFastest(ServerNode chosenNode, RavenCommand<TResult> command) {
//        AtomicInteger numberOfFailedTasks = new AtomicInteger();
//
//        CompletableFuture<IndexAndResponse> preferredTask = null;
//
//        List<ServerNode> nodes = _nodeSelector.getTopology().getNodes();
//        List<CompletableFuture<IndexAndResponse>> tasks = new ArrayList<>(Collections.nCopies(nodes.size(), null));
//
//        for (int i = 0; i < nodes.size(); i++) {
//            final int taskNumber = i;
//            numberOfServerRequests.incrementAndGet();
//
//            CompletableFuture<IndexAndResponse> task = CompletableFuture.supplyAsync(() -> {
//                try {
//                    Reference<String> strRef = new Reference<>();
//                    HttpRequestBase request = createRequest(nodes.get(taskNumber), command, strRef);
//                    setRequestHeaders(null, null, request);
//                    return new IndexAndResponse(taskNumber, command.send(getHttpClient(), request));
//                } catch (Exception e){
//                    numberOfFailedTasks.incrementAndGet();
//                    tasks.set(taskNumber, null);
//                    throw new RuntimeException("Request execution failed", e);
//                }
//            }, _executorService);
//
//            if (nodes.get(i).getClusterTag().equals(chosenNode.getClusterTag())) {
//                preferredTask = task;
//            } else {
//                task.thenAcceptAsync(result -> IOUtils.closeQuietly(result.response, null));
//            }
//
//            tasks.set(i, task);
//        }
//
//        while (numberOfFailedTasks.get() < tasks.size()) {
//            try {
//                IndexAndResponse fastest = (IndexAndResponse) CompletableFuture
//                        .anyOf(tasks.stream().filter(Objects::nonNull)
//                        .toArray(CompletableFuture[]::new))
//                        .get();
//                _nodeSelector.recordFastest(fastest.index, nodes.get(fastest.index));
//                break;
//            } catch (InterruptedException | ExecutionException e) {
//                for (int i = 0; i < nodes.size(); i++) {
//                    if (tasks.get(i).isCompletedExceptionally()) {
//                        numberOfFailedTasks.incrementAndGet();
//                        tasks.set(i, null);
//                    }
//                }
//            }
//        }
//
//        // we can reach here if the number of failed task equal to the number
//        // of the nodes, in which case we have nothing to do
//
//        try {
//            return preferredTask.get().response;
//        } catch (InterruptedException | ExecutionException e) {
//            throw ExceptionsUtils.unwrapException(e);
//        }
//    }
//
//    private <TResult> HttpCache.ReleaseCacheItem getFromCache(RavenCommand<TResult> command, boolean useCache, String url, Reference<String> cachedChangeVector, Reference<String> cachedValue) {
//        if (useCache && command.canCache() && command.isReadRequest() && command.getResponseType() == RavenCommandResponseType.OBJECT) {
//            return cache.get(url, cachedChangeVector, cachedValue);
//        }
//
//        cachedChangeVector.value = null;
//        cachedValue.value = null;
//        return new HttpCache.ReleaseCacheItem();
//    }
//
//    private <TResult> HttpRequestBase createRequest(ServerNode node, RavenCommand<TResult> command, Reference<String> url) {
//        try {
//            HttpRequestBase request = command.createRequest(node, url);
//            if (request == null) {
//                return null;
//            }
//            URI builder = new URI(url.value);
//
//            if (requestPostProcessor != null) {
//                requestPostProcessor.accept(request);
//            }
//
//            if (command instanceof IRaftCommand) {
//                IRaftCommand raftCommand = (IRaftCommand) command;
//
//                String raftRequestString = "raft-request-id=" + raftCommand.getRaftUniqueRequestId();
//
//                builder = new URI(builder.getQuery() != null ? builder.toString() + "&" + raftRequestString : builder.toString() + "?" + raftRequestString);
//            }
//
//            if (shouldBroadcast(command)) {
//                command.setTimeout(ObjectUtils.firstNonNull(command.getTimeout(), _firstBroadcastAttemptTimeout));
//            }
//
//            request.setURI(builder);
//
//            return request;
//        } catch (URISyntaxException e) {
//            throw new IllegalArgumentException("Unable to parse URL", e);
//        }
//    }

    private function handleUnsuccessfulResponse(
        ServerNode $chosenNode,
        ?int $nodeIndex,
        RavenCommand $command,
        HttpRequestInterface $request,
        HttpResponseInterface $response
//        String url,
//        SessionInfo sessionInfo,
//        boolean shouldRetry
    ): bool
    {

        try {
            switch ($response->getStatusCode()) {
                case HttpStatusCode::NOT_FOUND:
                    // @todo: implement cache
//                    cache.setNotFound(url, aggressiveCaching.get() != null);
                    if ($command->getResponseType()->isEmpty()) {
                        return true;
                    }

                    if ($command->getResponseType()->isObject()) {
                        $command->setResponse(null, false);
                    } else {
                        $command->setResponseRaw($response);
                    }

                    return true;

                case HttpStatusCode::FORBIDDEN:
                    $msg = $this->tryGetResponseOfError($response);

                    $errorMessage = "Forbidden access to ";
                    $errorMessage .= $chosenNode->getDatabase();
                    $errorMessage .= "@";
                    $errorMessage .= $chosenNode->getUrl();
                    $errorMessage .= ", ";

                    if ($this->authOptions == null) {
                        $errorMessage .= "a certificate is required. ";
                    } else {
                        $errorMessage .= "certificate does not have permission to access it or is unknown. ";
                    }

                    $errorMessage .= "Method: ";
                    $errorMessage .= $request->getMethod();
                    $errorMessage .= ', Request: ';
                    $errorMessage .= $request->getUrl();
                    $errorMessage .= PHP_EOL;
                    $errorMessage .= $msg;

                    throw new AuthorizationException($errorMessage);

//                case HttpStatus.SC_GONE: // request not relevant for the chosen node - the database has been moved to a different one
//                    if (!shouldRetry) {
//                        return false;
//                    }
//
//                    if (nodeIndex != null) {
//                        _nodeSelector.onFailedRequest(nodeIndex);
//                    }
//
//                    if (command.getFailedNodes() == null) {
//                        command.setFailedNodes(new HashMap<>());
//                    }
//
//                    if (!command.isFailedWithNode(chosenNode)) {
//                        command.getFailedNodes().put(chosenNode, new UnsuccessfulRequestException("Request to " + request.getURI() + " (" + request.getMethod() + ") is not relevant for this node anymore."));
//                    }
//
//                    CurrentIndexAndNode indexAndNode = chooseNodeForRequest(command, sessionInfo);
//
//                    if (command.getFailedNodes().containsKey(indexAndNode.currentNode)) {
//                        // we tried all the nodes, let's try to update topology and retry one more time
//                        UpdateTopologyParameters updateParameters = new UpdateTopologyParameters(chosenNode);
//                        updateParameters.setTimeoutInMs(60_000);
//                        updateParameters.setForceUpdate(true);
//                        updateParameters.setDebugTag("handle-unsuccessful-response");
//                        Boolean success = updateTopologyAsync(updateParameters).get();
//                        if (!success) {
//                            return false;
//                        }
//
//                        command.getFailedNodes().clear(); // we just update the topology
//                        indexAndNode = chooseNodeForRequest(command, sessionInfo);
//
//                        execute(indexAndNode.currentNode, indexAndNode.currentIndex, command, false, sessionInfo);
//                        return true;
//                    }
//
//                    execute(indexAndNode.currentNode, indexAndNode.currentIndex, command, false, sessionInfo);
//                    return true;
//                case HttpStatusCode::INTERNAL_SERVER_ERROR: // @todo: check do we need to add this line or default route will handle it as it should
                case HttpStatusCode::GATEWAY_TIMEOUT:
                case HttpStatusCode::REQUEST_TIMEOUT:
                case HttpStatusCode::BAD_GATEWAY:
                case HttpStatusCode::SERVICE_UNAVAILABLE:
                    return $this->handleServerDown(
//                        url,
                        $chosenNode,
                        $nodeIndex,
                        $command,
                        $request,
                        $response,
                        null,
//                        sessionInfo,
//                        shouldRetry
                    );
//                case HttpStatus.SC_CONFLICT:
//                    handleConflict(response);
//                    break;
//                case 425: // TooEarly
//                    if (!shouldRetry) {
//                        return false;
//                    }
//
//                    if (nodeIndex != null) {
//                        _nodeSelector.onFailedRequest(nodeIndex);
//                    }
//
//                    if (command.getFailedNodes() == null) {
//                        command.setFailedNodes(new HashMap<>());
//                    }
//
//                    if (!command.isFailedWithNode(chosenNode)) {
//                        command.getFailedNodes().put(chosenNode,
//                                new UnsuccessfulRequestException("Request to '" + request.getURI() + "' ("  +request.getMethod() + ") is processing and not yet available on that node."));
//                    }
//
//                    CurrentIndexAndNode nextNode = chooseNodeForRequest(command, sessionInfo);
//                    execute(nextNode.currentNode, nextNode.currentIndex, command, true, sessionInfo);
//
//                    if (nodeIndex != null) {
//                        _nodeSelector.restoreNodeIndex(nodeIndex);
//                    }
//
//                    return true;
                default:
                    $command->onResponseFailure($response);
                    ExceptionDispatcher::throwException($response);
                    break;
            }
        } catch (\Throwable $e) {
            throw ExceptionsUtils::unwrapException($e);
        }

        return false;
    }

    private function tryGetResponseOfError(HttpResponseInterface $response): string
    {
        try {
            return $response->getContent();
        } catch (\Throwable $exception) {
            return "Could not read request: " . $exception->getMessage();
        }

    }

//
//    private static void handleConflict(CloseableHttpResponse response) {
//        ExceptionDispatcher.throwException(response);
//    }
//
//    public static InputStream readAsStream(CloseableHttpResponse response) throws IOException {
//        return response.getEntity().getContent();
//    }

    private function handleServerDown(
//          String url,
          ServerNode $chosenNode,
          ?int $nodeIndex,
          RavenCommand $command,
          HttpRequestInterface $request,
          HttpResponseInterface $response,
          ?Exception $e
//          SessionInfo sessionInfo,
//          boolean shouldRetry
    ): bool {
        $failedNodes = $command->getFailedNodes();
        if ($failedNodes == null) {
            $failedNodes = new DSMap();
        }

        $failedNodes->put($chosenNode, self::readExceptionFromServer($request, $response, $e));
        $command->setFailedNodes($failedNodes);

        if ($nodeIndex === null) {
            //We executed request over a node not in the topology. This means no failover...
            return false;
        }

        if ($this->nodeSelector == null) {
            // @todo: uncomment this linec
//            $this->spawnHealthChecks($chosenNode, $nodeIndex);
            return false;
        }

        // As the server is down, we discard the server version to ensure we update when it goes up.
        $chosenNode->discardServerVersion();

        $this->nodeSelector->onFailedRequest($nodeIndex);

        // @todo: Uncomment this

//        if ($this->shouldBreadcast($command)) {
//            $command->setResult($this->broadcast($command, $sessionInfo));
//            return true;
//        }

        // @todo: uncomment this
//        $this->spawnHealthChecks($chosenNode, $nodeIndex);

//        $indexAndNodeAndEtag = $this->nodeSelector->getPreferredNodeWithTopology();

        if ($command->failoverTopologyEtag != $this->topologyEtag) {
//            command.getFailedNodes().clear();
//            command.failoverTopologyEtag = topologyEtag;
        }

//        if (command.getFailedNodes().containsKey(indexAndNodeAndEtag.currentNode)) {
//            return false;
//        }
//
//        onFailedRequestInvoke(url, e);
//
//        execute(indexAndNodeAndEtag.currentNode, indexAndNodeAndEtag.currentIndex, command, shouldRetry, sessionInfo);
//
        return true;
    }
//
//    private <TResult> boolean shouldBroadcast(RavenCommand<TResult> command) {
//        if (!(command instanceof IBroadcast)) {
//            return false;
//        }
//
//        List<ServerNode> topologyNodes = getTopologyNodes();
//
//        if (topologyNodes == null || topologyNodes.size() < 2) {
//            return false;
//        }
//
//        return true;
//    }
//
//    public static class BroadcastState<TResult> {
//        private RavenCommand<TResult> command;
//        private int index;
//        private ServerNode node;
//        private HttpRequestBase request;
//
//        public RavenCommand<TResult> getCommand() {
//            return command;
//        }
//
//        public void setCommand(RavenCommand<TResult> command) {
//            this.command = command;
//        }
//
//        public int getIndex() {
//            return index;
//        }
//
//        public void setIndex(int index) {
//            this.index = index;
//        }
//
//        public ServerNode getNode() {
//            return node;
//        }
//
//        public void setNode(ServerNode node) {
//            this.node = node;
//        }
//
//        public HttpRequestBase getRequest() {
//            return request;
//        }
//
//        public void setRequest(HttpRequestBase request) {
//            this.request = request;
//        }
//    }
//
//    private <TResult> TResult broadcast(RavenCommand<TResult> command, SessionInfo sessionInfo) {
//        if (!(command instanceof IBroadcast)) {
//            throw new IllegalStateException("You can broadcast only commands that implement 'IBroadcast'.");
//        }
//
//        IBroadcast broadcastCommand = (IBroadcast) command;
//        final Map<ServerNode, Exception> failedNodes = command.getFailedNodes();
//
//        command.setFailedNodes(new HashMap<>()); // clean the current failures
//        Map<CompletableFuture<Void>, BroadcastState<TResult>> broadcastTasks = new HashMap<>();
//
//        try {
//            sendToAllNodes(broadcastTasks, sessionInfo, broadcastCommand);
//
//            return waitForBroadcastResult(command, broadcastTasks);
//        } finally {
//            for (Map.Entry<CompletableFuture<Void>, BroadcastState<TResult>> broadcastState : broadcastTasks.entrySet()) {
//                CompletableFuture<Void> task = broadcastState.getKey();
//                if (task != null) {
//                    task.exceptionally(throwable -> {
//                        int index = broadcastState.getValue().getIndex();
//                        ServerNode node = _nodeSelector.getTopology().getNodes().get(index);
//                        if (failedNodes.containsKey(node)) {
//                            // if other node succeed in broadcast we need to send health checks to the original failed node
//                            spawnHealthChecks(node, index);
//                        }
//                        return null;
//                    });
//                }
//            }
//        }
//    }
//
//    private <TResult> TResult waitForBroadcastResult(RavenCommand<TResult> command, Map<CompletableFuture<Void>, BroadcastState<TResult>> tasks) {
//        while (!tasks.isEmpty()) {
//            Exception error = null;
//            try {
//                CompletableFuture.anyOf(tasks.keySet().toArray(new CompletableFuture[0])).get();
//            } catch (InterruptedException | ExecutionException e) {
//                error = e;
//            }
//
//            CompletableFuture<Void> completed = tasks
//                    .keySet()
//                    .stream()
//                    .filter(CompletableFuture::isDone)
//                    .findFirst()
//                    .orElse(null);
//
//            if (error != null) {
//                BroadcastState<TResult> failed = tasks.get(completed);
//                ServerNode node = _nodeSelector.getTopology().getNodes().get(failed.index);
//
//                command.getFailedNodes().put(node, error.getCause() != null ? (Exception) error.getCause() : error);
//
//                _nodeSelector.onFailedRequest(failed.getIndex());
//                spawnHealthChecks(node, failed.getIndex());
//
//                tasks.remove(completed);
//                continue;
//            }
//
//            for (BroadcastState<TResult> state : tasks.values()) {
//                HttpRequestBase request = state.getRequest();
//                if (request != null) {
//                    request.abort();
//                }
//            }
//
//            _nodeSelector.restoreNodeIndex(tasks.get(completed).getIndex());
//            return tasks.get(completed).getCommand().getResult();
//        }
//
//        String exceptions = command
//                .getFailedNodes()
//                .entrySet()
//                .stream()
//                .map(x -> new UnsuccessfulRequestException(x.getKey().getUrl(), x.getValue()))
//                .map(Throwable::toString)
//                .collect(Collectors.joining(", "));
//
//        throw new AllTopologyNodesDownException("Broadcasting " + command.getClass().getSimpleName() + " failed: " + exceptions);
//    }
//
//    @SuppressWarnings("unchecked")
//    private <TResult> void sendToAllNodes(Map<CompletableFuture<Void>, BroadcastState<TResult>> tasks, SessionInfo sessionInfo, IBroadcast command) {
//        for (int index = 0; index < _nodeSelector.getTopology().getNodes().size(); index++) {
//            BroadcastState<TResult> state = new BroadcastState<>();
//            state.setCommand((RavenCommand<TResult>)command.prepareToBroadcast(getConventions()));
//            state.setIndex(index);
//            state.setNode(_nodeSelector.getTopology().getNodes().get(index));
//
//            state.getCommand().setTimeout(_secondBroadcastAttemptTimeout);
//
//            AggressiveCacheOptions callingTheadAggressiveCaching = aggressiveCaching.get();
//
//            CompletableFuture<Void> task = CompletableFuture.runAsync(() -> {
//                AggressiveCacheOptions aggressiveCacheOptionsToRestore = aggressiveCaching.get();
//
//                aggressiveCaching.set(callingTheadAggressiveCaching);
//                try {
//                    Reference<HttpRequestBase> requestRef = new Reference<>();
//                    execute(state.getNode(), null, state.getCommand(), false, sessionInfo, requestRef);
//                    state.setRequest(requestRef.value);
//                } finally {
//                    aggressiveCaching.set(aggressiveCacheOptionsToRestore);
//                }
//            }, _executorService);
//            tasks.put(task, state);
//        }
//    }
//
//    public ServerNode handleServerNotResponsive(String url, ServerNode chosenNode, int nodeIndex, Exception e) {
//        spawnHealthChecks(chosenNode, nodeIndex);
//        if (_nodeSelector != null) {
//            _nodeSelector.onFailedRequest(nodeIndex);
//        }
//        CurrentIndexAndNode preferredNode = getPreferredNode();
//
//        if (_disableTopologyUpdates) {
//            performHealthCheck(chosenNode, nodeIndex);
//        } else {
//            try {
//                UpdateTopologyParameters updateParameters = new UpdateTopologyParameters(preferredNode.currentNode);
//                updateParameters.setTimeoutInMs(0);
//                updateParameters.setForceUpdate(true);
//                updateParameters.setDebugTag("handle-server-not-responsive");
//                updateTopologyAsync(updateParameters).get();
//            } catch (InterruptedException | ExecutionException ee) {
//                throw ExceptionsUtils.unwrapException(e);
//            }
//        }
//
//        onFailedRequestInvoke(url, e);
//
//        return preferredNode.currentNode;
//    }
//
//    private void spawnHealthChecks(ServerNode chosenNode, int nodeIndex) {
//        if (_nodeSelector != null && _nodeSelector.getTopology().getNodes().size() < 1) {
//            return;
//        }
//
//        NodeStatus nodeStatus = new NodeStatus(this, nodeIndex, chosenNode);
//
//        if (_failedNodesTimers.putIfAbsent(chosenNode, nodeStatus) == null) {
//            nodeStatus.startTimer();
//        }
//    }
//
//    private void checkNodeStatusCallback(NodeStatus nodeStatus) {
//        List<ServerNode> copy = getTopologyNodes();
//
//        if (nodeStatus.nodeIndex >= copy.size()) {
//            return; // topology index changed / removed
//        }
//
//        ServerNode serverNode = copy.get(nodeStatus.nodeIndex);
//        if (serverNode != nodeStatus.node) {
//            return;  // topology changed, nothing to check
//        }
//
//        try {
//            NodeStatus status;
//
//            try {
//                performHealthCheck(serverNode, nodeStatus.nodeIndex);
//            } catch (Exception e) {
//                if (logger.isInfoEnabled()) {
//                    logger.info(serverNode.getClusterTag() + " is still down", e);
//                }
//
//                status = _failedNodesTimers.get(nodeStatus.node);
//                if (status != null) {
//                    status.updateTimer();
//                }
//
//                return; // will wait for the next timer call
//            }
//
//            status = _failedNodesTimers.get(nodeStatus.node);
//            if (status != null) {
//                _failedNodesTimers.remove(nodeStatus.node);
//                status.close();
//            }
//
//            if (_nodeSelector != null) {
//                _nodeSelector.restoreNodeIndex(nodeStatus.nodeIndex);
//            }
//
//        } catch (Exception e) {
//            if (logger.isInfoEnabled()) {
//                logger.info("Failed to check node topology, will ignore this node until next topology update", e);
//            }
//        }
//    }
//
//    protected void performHealthCheck(ServerNode serverNode, int nodeIndex) {
//        try {
//            if (!_useOldFailureCheckOperation.contains(serverNode.getUrl())) {
//                execute(serverNode, nodeIndex, failureCheckOperation.getCommand(conventions), false, null);
//            } else {
//                executeOldHealthCheck(serverNode, nodeIndex);
//            }
//        } catch (Exception e) {
//            if (e.getMessage().contains("RouteNotFoundException")) {
//                _useOldFailureCheckOperation.add(serverNode.getUrl());
//                executeOldHealthCheck(serverNode, nodeIndex);
//                return;
//            }
//
//            throw ExceptionsUtils.unwrapException(e);
//        }
//    }
//
//    private void executeOldHealthCheck(ServerNode serverNode, int nodeIndex) {
//        execute(serverNode, nodeIndex, backwardCompatibilityFailureCheckOperation.getCommand(conventions), false, null);
//    }

    private static function readExceptionFromServer(
        HttpRequestInterface $request,
        ?HttpResponseInterface $response,
        ?Exception $e
    ): Exception {

        if ($response != null) {

            $responseJson = '';
            try {
                $responseJson = $response->getContent();
                $exceptionScheme = JsonExtensions::getDefaultMapper()->deserialize($responseJson, ExceptionSchema::class, 'json');
                return ExceptionDispatcher::get($exceptionScheme, $response->getStatusCode(), $e);
            } catch (Exception $exception) {
                $exceptionScheme = new ExceptionSchema();
                $exceptionScheme->setUrl($request->getUrl());
                $exceptionScheme->setMessage("Get unrecognized response from the server");
                $exceptionScheme->setError($responseJson);
                $exceptionScheme->setType("Unparsable Server Response");

                return ExceptionDispatcher::get($exceptionScheme, $response->getStatusCode(), $exception);
            }
        }

        // this would be connections that didn't have response, such as "couldn't connect to remote server"
        $exceptionScheme = new ExceptionSchema();
        $exceptionScheme->setUrl($request->getUrl());
        $exceptionScheme->setMessage($e->getMessage());
        $exceptionScheme->setError("An exception occurred while contacting " . $request->getUrl() . "." . PHP_EOL . $e->getTraceAsString());
        $exceptionScheme->setType(get_class($e));

        return ExceptionDispatcher::get($exceptionScheme, HttpStatusCode::SERVICE_UNAVAILABLE, $e);
    }

//    protected CompletableFuture<Void> _firstTopologyUpdate;
//    protected String[] _lastKnownUrls;
//    protected boolean _disposed;
//
//    @Override
//    public void close() {
//        if (_disposed) {
//            return;
//        }
//
//        _disposed = true;
//        cache.close();
//
//        if (_updateTopologyTimer != null) {
//            _updateTopologyTimer.close();
//        }
//
//        disposeAllFailedNodesTimers();
//    }
//
//    private CloseableHttpClient createClient() {
//        HttpClientBuilder httpClientBuilder = HttpClients
//                .custom()
//                .setMaxConnPerRoute(30)
//                .setMaxConnTotal(40)
//                .setDefaultRequestConfig(
//                        RequestConfig.custom()
//                                .setConnectionRequestTimeout(3000)
//                                .build()
//                );
//
//        if (conventions.hasExplicitlySetCompressionUsage() && !conventions.isUseCompression()) {
//            httpClientBuilder.disableContentCompression();
//        }
//
//        httpClientBuilder
//                .setRetryHandler(new StandardHttpRequestRetryHandler(0, false))
//                .setDefaultSocketConfig(SocketConfig.custom().setTcpNoDelay(true).build());
//
//        if (certificate != null) {
//            try {
//                httpClientBuilder.setSSLHostnameVerifier((s, sslSession) -> {
//                    // Here we are explicitly ignoring trust issues in the case of ClusterRequestExecutor.
//                    // this is because we don't actually require trust, we just use the certificate
//                    // as a way to authenticate. Either we encounter the same server certificate which we already
//                    // trust, or the admin is going to tell us which specific certs we can trust.
//                    return true;
//                });
//
//                httpClientBuilder.setSSLContext(createSSLContext());
//            } catch ( Exception e) {
//                throw new IllegalStateException("Unable to configure ssl context: " + e.getMessage(), e);
//            }
//        }
//
//        if (configureHttpClient != null) {
//            configureHttpClient.accept(httpClientBuilder);
//        }
//
//        return httpClientBuilder.build();
//    }
//
//    public SSLContext createSSLContext() throws UnrecoverableKeyException, NoSuchAlgorithmException, KeyStoreException, KeyManagementException {
//        SSLContextBuilder sslContextBuilder = SSLContexts.custom()
//                .loadKeyMaterial(certificate, keyPassword);
//
//        if (this.trustStore != null) {
//            sslContextBuilder.loadTrustMaterial(trustStore, null);
//        }
//
//        return sslContextBuilder.build();
//    }
//
//    public static class NodeStatus implements CleanCloseable {
//
//        private Duration _timerPeriod;
//        private final RequestExecutor _requestExecutor;
//        public final int nodeIndex;
//        public final ServerNode node;
//        private Timer _timer;
//
//        public NodeStatus(RequestExecutor requestExecutor, int nodeIndex, ServerNode node) {
//            _requestExecutor = requestExecutor;
//            this.nodeIndex = nodeIndex;
//            this.node = node;
//            _timerPeriod = Duration.ofMillis(100);
//        }
//
//        private Duration nextTimerPeriod() {
//            if (_timerPeriod.compareTo(Duration.ofSeconds(5)) >= 0) {
//                return Duration.ofSeconds(5);
//            }
//
//            _timerPeriod = _timerPeriod.plus(Duration.ofMillis(100));
//
//            return _timerPeriod;
//        }
//
//        public void startTimer() {
//            _timer = new Timer(this::timerCallback, _timerPeriod, _requestExecutor._executorService);
//        }
//
//        private void timerCallback() {
//            if (_requestExecutor._disposed) {
//                close();
//                return;
//            }
//
//            _requestExecutor.checkNodeStatusCallback(this);
//        }
//
//        public void updateTimer() {
//            _timer.change(nextTimerPeriod());
//        }
//
//        @Override
//        public void close() {
//            _timer.close();
//        }
//    }
//
//    public CurrentIndexAndNode getRequestedNode(String nodeTag) {
//        ensureNodeSelector();
//
//        return _nodeSelector.getRequestedNode(nodeTag);
//    }

    public function getPreferredNode(): CurrentIndexAndNode
    {
        $this->ensureNodeSelector();

        return $this->nodeSelector->getPreferredNode();
    }

    public function getNodeBySessionId(int $sessionId): CurrentIndexAndNode
    {
        $this->ensureNodeSelector();

        return $this->nodeSelector->getNodeBySessionId($sessionId);
    }

    public function getFastestNode(): CurrentIndexAndNode
    {
        $this->ensureNodeSelector();

        return $this->nodeSelector->getFastestNode();
    }

    private function ensureNodeSelector(): void
    {
        if (!$this->disableTopologyUpdates) {
//            $this->waitForTopologyUpdate($this->firstTopologyUpdate);
        }

        if ($this->nodeSelector == null) {
            $topology = new Topology();

            $topology->setNodes($this->getTopologyNodes());
            $topology->setEtag($this->topologyEtag);

            $this->nodeSelector = new NodeSelector($topology);
        }
    }

    protected function onTopologyUpdatedInvoke(Topology $newTopology): void
    {
        EventHelper::invoke($this->onTopologyUpdated, $this, new TopologyUpdatedEventArgs($newTopology));
    }

//    public static class IndexAndResponse {
//        public final int index;
//        public final CloseableHttpResponse response;
//
//        public IndexAndResponse(int index, CloseableHttpResponse response) {
//            this.index = index;
//            this.response = response;
//        }
//    }

    public function close(): void
    {
        // TODO: Implement close() method.
    }
}
