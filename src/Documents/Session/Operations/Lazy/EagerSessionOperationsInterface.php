<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Session\ResponseTimeInformation;

/**
 * Allow to perform eager operations on the session
 */
interface EagerSessionOperationsInterface
{
    /**
     * Execute all the lazy requests pending within this session
     * @return ResponseTimeInformation Information about response times
     */
    function executeAllPendingLazyOperations(): ResponseTimeInformation;
}
