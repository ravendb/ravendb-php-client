<?php

namespace RavenDB\Documents\Commands;

use InvalidArgumentException;
use RavenDB\Constants\Counters;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRangeArray;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCountRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesTimeRange;
use RavenDB\Documents\Queries\HashCalculator;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\NetISO8601Utils;
use RavenDB\Type\StringArray;
use RavenDB\Utils\UrlUtils;

// !status: IN PROGRESS
// only prepareRequestWithMultipleIds is left to be implemented in this class
class GetDocumentsCommand extends RavenCommand
{
    private string $id = '';

    private ?StringArray $ids = null;
    private ?StringArray $includes = null;
    private ?StringArray $counters = null;
    private bool $includeAllCounters = false;

    private ?AbstractTimeSeriesRangeArray $timeSeriesIncludes = null;
    private ?StringArray $compareExchangeValueIncludes = null;

    private bool $metadataOnly = false;

    private ?string $startWith = null;
    private ?string $matches = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private ?string $exclude = null;
    private ?string $startAfter = null;

    protected function __construct()
    {
        parent::__construct(GetDocumentsResult::class);
    }

    public static function forMultipleDocuments(StringArray $ids, ?StringArray $includes, bool $metadataOnly = false): GetDocumentsCommand
    {
        if (empty($ids)) {
            throw new InvalidArgumentException("Please supply at least one id");
        }
        $command = new GetDocumentsCommand();

        $command->ids = $ids;
        $command->includes = $includes ?? new StringArray();
        $command->metadataOnly = $metadataOnly;

        return $command;
    }

    public static function forSingleDocument(string $id, ?StringArray $includes = null, bool $metadataOnly = false): GetDocumentsCommand
    {
        if (empty($id)) {
            throw new IllegalArgumentException("id cannot be null");
        }

        $command = new GetDocumentsCommand();
        $command->id = $id;
        $command->includes = $includes ?? new StringArray();
        $command->metadataOnly = $metadataOnly;

        return $command;
    }

    public static function withStartAndPageSize(int $start, int $pageSize): GetDocumentsCommand
    {
        $command = new GetDocumentsCommand();
        $command->start = $start;
        $command->pageSize = $pageSize;

        return $command;
    }

    public static function withCounters(
        ?StringArray $ids,
        ?StringArray $includes,
        ?StringArray $counterIncludes,
        ?AbstractTimeSeriesRangeArray $timeSeriesIncludes,
        ?StringArray $compareExchangeValueIncludes,
        bool $metadata
    ): GetDocumentsCommand {
        $command = GetDocumentsCommand::forMultipleDocuments($ids, $includes, $metadata);

        $command->counters = $counterIncludes;
        $command->timeSeriesIncludes = $timeSeriesIncludes;
        $command->compareExchangeValueIncludes = $compareExchangeValueIncludes;

        return $command;
    }

    public static function withAllCounters(
        StringArray $ids,
        StringArray $includes,
        bool $includeAllCounters,
        AbstractTimeSeriesRangeArray $timeSeriesIncludes,
        StringArray $compareExchangeValueIncludes,
        bool $metadataOnly
    ): GetDocumentsCommand {
        $command = GetDocumentsCommand::forMultipleDocuments($ids, $includes, $metadataOnly);

        $command->includeAllCounters = $includeAllCounters;
        $command->timeSeriesIncludes = $timeSeriesIncludes;
        $command->compareExchangeValueIncludes = $compareExchangeValueIncludes;

        return $command;
    }

    public static function withStartWith(
        ?string $startWith,
        ?string $startAfter,
        ?string $matches,
        ?string $exclude,
        int $start,
        int $pageSize,
        bool $metadataOnly
    ): GetDocumentsCommand {
        if (empty($startWith)) {
            throw new IllegalArgumentException("startWith cannot be null");
        }

        $command = new GetDocumentsCommand();

        $command->startWith = $startWith;
        $command->startAfter = $startAfter;
        $command->matches = $matches;
        $command->exclude = $exclude;
        $command->start = $start;
        $command->pageSize = $pageSize;
        $command->metadataOnly = $metadataOnly;

        return $command;
    }

    protected function createUrl(ServerNode $serverNode): string
    {
        $path = $serverNode->getUrl();
        $path .= '/databases/';
        $path .= $serverNode->getDatabase();
        $path .= '/docs?';

        if ($this->start != null) {
            $path .= '&start=' . $this->start;
        }

        if ($this->pageSize != null) {
            $path .= '&pageSize=' . $this->pageSize;
        }

        if ($this->metadataOnly) {
            $path .= '&metadataOnly=true';
        }

        if ($this->startWith != null) {
            $path .= '&startsWith=' . UrlUtils::escapeDataString($this->startWith);

            if ($this->matches != null) {
                $path .= '&matches=' . urlEncode($this->matches);
            }

            if ($this->exclude != null) {
                $path .= '&exclude=' . urlEncode($this->exclude);
            }

            if ($this->startAfter != null) {
                $path .= '&startAfter=' . $this->startAfter;
            }
        }

        if (count($this->includes)) {
            foreach ($this->includes as $include) {
                $path .= '&include=' . urlEncode($include);
            }
        }

        if ($this->includeAllCounters) {
            $path .= '&counter=' . urlEncode(Counters::ALL);
        } else if (($this->counters !== null) && count($this->counters)) {
            foreach ($this->counters as $counter) {
                $path .= '&counter=' . urlEncode($counter);
            }
        }

        if (!empty($this->timeSeriesIncludes)) {
            foreach ($this->timeSeriesIncludes as $tsInclude) {
                if ($tsInclude instanceof TimeSeriesRange) {
                    $range = $tsInclude;
                    $path .= '&timeseries=' . urlEncode($range->getName());
                    $path .= '&from=';
                    $path .= $range->getFrom() != null ? NetISO8601Utils::format($range->getFrom(), true) : "";
                    $path .= '&to=';
                    $path .= $range->getTo() != null ? NetISO8601Utils::format($range->getTo(), true) : "";
                } elseif ($tsInclude instanceof TimeSeriesTimeRange) {
                    $timeRange = $tsInclude;
                    $path .= "&timeseriestime=" . urlEncode($timeRange->getName());
                    $path .= "&timeType=" . urlEncode($timeRange->getType());
                    $path .= "&timeValue=" . $timeRange->getTime()->getValue();
                    $path .= "&timeUnit=" . urlEncode($timeRange->getTime()->getUnit());
                } elseif ($tsInclude instanceof TimeSeriesCountRange) {
                    $countRange = $tsInclude;
                    $path .= "&timeseriescount=" . urlEncode($countRange->getName());
                    $path .= "&countType=" . urlEncode($countRange->getType());
                    $path .= "&countValue=" . $countRange->getCount();
                } else {
                    throw new InvalidArgumentException("Unexpected TimeSeries range" . $tsInclude->getClass());
                }
            }
        }

        if (($this->compareExchangeValueIncludes !== null) && count($this->compareExchangeValueIncludes)) {
            foreach ($this->compareExchangeValueIncludes as $compareExchangeValue) {
                $path .= "&cmpxchg=" . urlEncode($compareExchangeValue);
            }
        }

        if (!empty($this->id)) {
            $path .= '&id=' . UrlUtils::escapeDataString($this->id);
        } else if (!empty($this->ids)) {
//            request = prepareRequestWithMultipleIds(pathBuilder, request, _ids);
        }

        return $path;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

//    public static HttpRequestBase prepareRequestWithMultipleIds(StringBuilder pathBuilder, HttpRequestBase request, String[] ids) {
//        Set<String> uniqueIds = new LinkedHashSet<>();
//        Collections.addAll(uniqueIds, ids);
//
//        // if it is too big, we drop to POST (note that means that we can't use the HTTP cache any longer)
//        // we are fine with that, requests to load > 1024 items are going to be rare
//        boolean isGet = uniqueIds.stream()
//                .filter(Objects::nonNull)
//                .map(String::length)
//                .reduce(Integer::sum)
//                .orElse(0) < 1024;
//
//        if (isGet) {
//            uniqueIds.forEach(x -> {
//                pathBuilder.append("&id=");
//                pathBuilder.append(
//                        UrlUtils.escapeDataString(
//                                ObjectUtils.firstNonNull(x, "")));
//            });
//
//            return new HttpGet();
//        } else {
//            HttpPost httpPost = new HttpPost();
//
//            try {
//                String calculateHash = calculateHash(uniqueIds);
//                pathBuilder.append("&loadHash=");
//                pathBuilder.append(calculateHash);
//            } catch (IOException e) {
//                throw new RuntimeException("Unable to compute query hash:" + e.getMessage(), e);
//            }
//
//            ObjectMapper mapper = JsonExtensions.getDefaultMapper();
//
//            httpPost.setEntity(new ContentProviderHttpEntity(outputStream -> {
//                try (JsonGenerator generator = mapper.getFactory().createGenerator(outputStream)) {
//                    generator.writeStartObject();
//                    generator.writeFieldName("Ids");
//                    generator.writeStartArray();
//
//                    for (String id : uniqueIds) {
//                        generator.writeString(id);
//                    }
//
//                    generator.writeEndArray();
//                    generator.writeEndObject();
//
//                } catch (IOException e) {
//                    throw new RuntimeException(e);
//                }
//            }, ContentType.APPLICATION_JSON));
//
//            return httpPost;
//        }
//    }

    private static function calculateHash(StringArray $uniqueIds): string
    {
        $hasher = new HashCalculator();

        foreach ($uniqueIds as $x) {
            $hasher->write($x);
        }

        return $hasher->getHash();
}

    public function setResponse(string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->result = null;
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
