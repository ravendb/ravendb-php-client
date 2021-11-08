<?php

namespace RavenDB\Documents\Commands;

use InvalidArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetDocumentsCommand extends RavenCommand
{
    protected array $ids = [];
    protected array $includes = [];
    private bool $metadataOnly = false;

    private ?string $startWith = null;
    private ?string $matches = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private ?string $exclude = null;
    private ?string $startAfter = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $ids, array $includes, bool $metadataOnly)
    {
        parent::__construct(GetDocumentsResult::class);

        if (empty($ids)) {
            throw new InvalidArgumentException("Please supply at least one id");
        }

        $this->ids = $ids;
        $this->includes = $includes;
        $this->metadataOnly = $metadataOnly;
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
            // todo: find php alternative for escapeDataString
//            pathBuilder.append("&startsWith=");
//            pathBuilder.append(UrlUtils.escapeDataString(_startWith));
//
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

//        if (_includeAllCounters) {
//            pathBuilder
//                    .append("&counter=")
//                    .append(urlEncode(Constants.Counters.ALL));
//        } else if (_counters != null && _counters.length > 0) {
//            for (String counter : _counters) {
//                pathBuilder.append("&counter=").append(urlEncode(counter));
//            }
//        }

//        if (_timeSeriesIncludes != null) {
//            for (AbstractTimeSeriesRange tsInclude : _timeSeriesIncludes) {
//                if (tsInclude instanceof TimeSeriesRange) {
//                    TimeSeriesRange range = (TimeSeriesRange) tsInclude;
//                    pathBuilder.append("&timeseries=")
//                            .append(urlEncode(range.getName()))
//                            .append("&from=")
//                            .append(range.getFrom() != null ? NetISO8601Utils.format(range.getFrom(), true) : "")
//                            .append("&to=")
//                            .append(range.getTo() != null ? NetISO8601Utils.format(range.getTo(), true) : "");
//                } else if (tsInclude instanceof TimeSeriesTimeRange) {
//                    TimeSeriesTimeRange timeRange = (TimeSeriesTimeRange) tsInclude;
//                    pathBuilder
//                            .append("&timeseriestime=")
//                            .append(urlEncode(timeRange.getName()))
//                            .append("&timeType=")
//                            .append(urlEncode(SharpEnum.value(timeRange.getType())))
//                            .append("&timeValue=")
//                            .append(timeRange.getTime().getValue())
//                            .append("&timeUnit=")
//                            .append(urlEncode(SharpEnum.value(timeRange.getTime().getUnit())));
//                } else if (tsInclude instanceof TimeSeriesCountRange) {
//                    TimeSeriesCountRange countRange = (TimeSeriesCountRange) tsInclude;
//                    pathBuilder
//                            .append("&timeseriescount=")
//                            .append(urlEncode(countRange.getName()))
//                            .append("&countType=")
//                            .append(urlEncode(SharpEnum.value(countRange.getType())))
//                            .append("&countValue=")
//                            .append(countRange.getCount());
//                } else {
//                    throw new InvalidArgumentException("Unexpected TimeSeries range " + tsInclude.getClass());
//                }
//            }
//        }

//        if (_compareExchangeValueIncludes != null) {
//            for (String compareExchangeValue : _compareExchangeValueIncludes) {
//                pathBuilder
//                        .append("&cmpxchg=")
//                        .append(urlEncode(compareExchangeValue));
//            }
//        }

        return $path;
    }
}
