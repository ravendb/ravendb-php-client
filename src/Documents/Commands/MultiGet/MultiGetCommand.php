<?php

namespace RavenDB\Documents\Commands\MultiGet;

use RavenDB\Constants\Headers;
use RavenDB\Constants\HttpStatusCode;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Extensions\HttpExtensions;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\AggressiveCacheOptions;
use RavenDB\Http\HttpCache;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\HttpResponseInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\RavenCommandResponseType;
use RavenDB\Http\RequestExecutor;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\CleanCloseable;

class MultiGetCommand extends RavenCommand implements CleanCloseable
{
    private ?RequestExecutor $requestExecutor = null;
    private ?HttpCache $httpCache = null;
    private ?GetRequestList $commands = null;

    private ?string $baseUrl = null;
    private ?Cached $cached = null;

    public bool $aggressivelyCached = false;

    public function __construct(?RequestExecutor $requestExecutor, ?GetRequestList $commands)
    {
        parent::__construct(GetResponseList::class);

        if ($requestExecutor == null) {
            throw new IllegalArgumentException("RequestExecutor cannot be null");
        }

        $cache = $requestExecutor->getCache();

        if ($cache == null) {
            throw new IllegalArgumentException("Cache cannot be null");
        }

        if ($commands == null) {
            throw new IllegalArgumentException("Command cannot be null");
        }

        $this->requestExecutor = $requestExecutor;
        $this->httpCache = $requestExecutor->getCache();
        $this->commands = $commands;
        $this->responseType = RavenCommandResponseType::raw();
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $this->baseUrl = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase();
        return $this->baseUrl . "/multi_get";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
//        if ($this->maybeReadAllFromCache($this->requestExecutor->aggressiveCaching)) {
//            $this->aggressivelyCached = true;
//            return null; // aggressively cached
//        }

        $data = [];
        $data['Requests'] = [];
        foreach ($this->commands as $command) {

            $req = [];
            $req["Url"] = "/databases/" . $serverNode->getDatabase() . $command->getUrl();
            $req["Query"] = $command->getQuery();
            $req["Method"] = $command->getMethod();

            $req['Headers'] = [];
            foreach ($command->getHeaders() as $key => $value) {
                $req['Headers'][$key] = $value;
            }

            $req['Content'] = $command->getContent() != null ? $command->getContent()->writeContent() : null;


            $data['Requests'][] = $req;
        }

        $options = [
            'json' => $data,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }

    private function maybeReadAllFromCache(?AggressiveCacheOptions $options): bool
    {
        return false;

//        closeCache();
//
//        boolean readAllFromCache = options != null;
//        boolean trackChanges = readAllFromCache && options.getMode() == AggressiveCacheMode.TRACK_CHANGES;
//
//        for (int i = 0; i < _commands.size(); i++) {
//            GetRequest command = _commands.get(i);
//
//            String cacheKey = getCacheKey(command, new Reference<>());
//
//            Reference<String> changeVectorRef = new Reference<>();
//            Reference<String> cachedRef = new Reference<>();
//
//            HttpCache.ReleaseCacheItem cachedItem = _httpCache.get(cacheKey, changeVectorRef, cachedRef);
//            if (cachedItem.item == null) {
//                try {
//                    readAllFromCache = false;
//                    continue;
//                } finally {
//                    cachedItem.close();
//                }
//            }
//
//            if (readAllFromCache && (trackChanges && cachedItem.getMightHaveBeenModified() || cachedItem.getAge().compareTo(options.getDuration()) > 0) || !command.isCanCacheAggressively()) {
//                readAllFromCache = false;
//            }
//
//            command.getHeaders().put(Constants.Headers.IF_NONE_MATCH, changeVectorRef.value);
//            if (_cached == null) {
//                _cached = new Cached(_commands.size());
//            }
//
//            _cached.values[i] = Tuple.create(cachedItem, cachedRef.value);
//        }
//
//        if (readAllFromCache) {
//            try (CleanCloseable context = _cached) {
//                result = new ArrayList<>(_commands.size());
//
//                for (int i = 0; i < _commands.size(); i++) {
//                    Tuple<HttpCache.ReleaseCacheItem, String> itemAndCached = _cached.values[i];
//                    GetResponse getResponse = new GetResponse();
//                    getResponse.setResult(itemAndCached.second);
//                    getResponse.setStatusCode(HttpStatus.SC_NOT_MODIFIED);
//
//                    result.add(getResponse);
//                }
//            }
//
//            _cached = null;
//        }
//
//        return readAllFromCache;
    }

    private function getCacheKey(?GetRequest $command, string &$requestUrl): string
    {
        $requestUrl = $this->baseUrl . $command->getUrlAndQuery();
        return $command->getMethod() != null ? $command->getMethod() . "-" . $requestUrl : $requestUrl;
    }

//    public void setResponseRaw(CloseableHttpResponse response, InputStream stream) {
    public function setResponseRaw(HttpResponseInterface $response): void
    {
        $deserializedResponse = json_decode($response->getContent(), true); //$this->getMapper()->deserialize($response->getContent(), null, 'json');

        if (array_key_first($deserializedResponse) !== 'Results') {
            $this->throwInvalidResponse();
        }

        $results = $this->getMapper()->denormalize($deserializedResponse['Results'], GetResponseList::class);

        try {
            $i = 0;
            $this->result = new GetResponseList();
            foreach ($results as $getResponse) {
                $command = $this->commands[$i];
                $this->maybeSetCache($getResponse, $command, $i);

                if ($this->cached != null && $getResponse->getStatusCode() == HttpStatusCode::NOT_MODIFIED) {
                    $clonedResponse = new GetResponse();
                    $clonedResponse->setResult($this->cached->values[$i][1]);
                    $clonedResponse->setStatusCode(HttpStatusCode::NOT_MODIFIED);
                    $this->result->append($clonedResponse);
                } else {
                    $this->result->append($getResponse);
                }

                $i++;
            }
        } finally {
            $this->cached?->close();
        }
    }

//    private static List<GetResponse> readResponses(ObjectMapper mapper, JsonParser parser) throws IOException {
//        if (parser.nextToken() != JsonToken.START_ARRAY) {
//            throwInvalidResponse();
//        }
//
//        List<GetResponse> responses = new ArrayList<>();
//
//        while (true) {
//            if (parser.nextToken() == JsonToken.END_ARRAY) {
//                break;
//            }
//
//            responses.add(readResponse(mapper, parser));
//        }
//
//        return responses;
//    }
//
//    private static GetResponse readResponse(ObjectMapper mapper, JsonParser parser) throws IOException {
//        if (parser.currentToken() != JsonToken.START_OBJECT) {
//            throwInvalidResponse();
//        }
//
//        GetResponse getResponse = new GetResponse();
//
//        while (true) {
//            if (parser.nextToken() == null) {
//                throwInvalidResponse();
//            }
//
//            if (parser.currentToken() == JsonToken.END_OBJECT) {
//                break;
//            }
//
//            if (parser.currentToken() != JsonToken.FIELD_NAME) {
//                throwInvalidResponse();
//            }
//
//            String property = parser.getValueAsString();
//            switch (property) {
//                case "Result":
//                    JsonToken jsonToken = parser.nextToken();
//                    if (jsonToken == null) {
//                        throwInvalidResponse();
//                    }
//
//                    if (parser.currentToken() == JsonToken.VALUE_NULL) {
//                        continue;
//                    }
//
//                    if (parser.currentToken() != JsonToken.START_OBJECT) {
//                        throwInvalidResponse();
//                    }
//
//                    TreeNode treeNode = mapper.readTree(parser);
//                    getResponse.setResult(treeNode.toString());
//                    continue;
//                case "Headers":
//                    if (parser.nextToken() == null) {
//                        throwInvalidResponse();
//                    }
//
//                    if (parser.currentToken() == JsonToken.VALUE_NULL) {
//                        continue;
//                    }
//
//                    if (parser.currentToken() != JsonToken.START_OBJECT) {
//                        throwInvalidResponse();
//                    }
//
//                    ObjectNode headersMap = mapper.readTree(parser);
//                    headersMap.fieldNames().forEachRemaining(field -> getResponse.getHeaders().put(field, headersMap.get(field).asText()));
//                    continue;
//                case "StatusCode":
//                    int statusCode = parser.nextIntValue(-1);
//                    if (statusCode == -1) {
//                        throwInvalidResponse();
//                    }
//
//                    getResponse.setStatusCode(statusCode);
//                    continue;
//                default:
//                    throwInvalidResponse();
//                    break;
//            }
//        }
//
//        return getResponse;
//    }

    private function maybeSetCache(?GetResponse $getResponse, ?GetRequest $command, ?int $cachedIndex): void
    {
        if ($getResponse->getStatusCode() == HttpStatusCode::NOT_MODIFIED) {
            // if not modified - update age
            if ($this->cached !== null) {
                $this->cached->values[$cachedIndex][0]->notModified();
            }
            return;
        }

        $requestUrl = '';
        $cacheKey = $this->getCacheKey($command, $requestUrl);

        $result = $getResponse->getResult();
        if ($result == null) {
            $this->httpCache->setNotFound($cacheKey, $this->aggressivelyCached);
            return;
        }

        $changeVector = HttpExtensions::getEtagHeaderFromArray($getResponse->getHeaders()->getArrayCopy());
        if ($changeVector == null) {
            return;
        }

        $this->httpCache->set($cacheKey, $changeVector, $result);
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function close(): void
    {
        $this->closeCache();
    }

    public function closeCache(): void
    {
        // If _cached is not null - it means that the client approached with this multitask request to node and the request failed.
        // and now client tries to send it to another node.
        if ($this->cached != null) {
            $this->cached->close();

            $this->cached = null;

            // The client sends the commands.
            // Some of which could be saved in cache with a response
            // that includes the change vector that received from the old fallen node.
            // The client can't use those responses because their URLs are different
            // (include the IP and port of the old node), because of that the client
            // needs to get those docs again from the new node.

            /** @var GetRequest $command */
            foreach ($this->commands as $command) {
                $command->getHeaders()->offsetUnset(Headers::IF_NONE_MATCH);
            }
        }
    }
}
