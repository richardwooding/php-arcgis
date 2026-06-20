<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Exception;

/**
 * TransportException is raised when the request cannot be completed at the HTTP
 * level: a transport failure (DNS, connection, timeout), a non-200 status code,
 * or a response body that is not valid JSON.
 */
class TransportException extends ArcGisException
{
}
