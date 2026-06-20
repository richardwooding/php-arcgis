<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Exception;

use RichardWooding\ArcGis\Internal\Coerce;

/**
 * ApiException surfaces an error reported by an ArcGIS service. ArcGIS commonly
 * reports failures with an HTTP 200 status and an error envelope in the body,
 * so this is raised even on otherwise-successful responses.
 *
 * The ArcGIS error code is available via getCode(); the human-readable service
 * message via getArcGisMessage(); and any additional detail lines via
 * getDetails().
 */
final class ApiException extends ArcGisException
{
    /**
     * @param list<string> $details
     */
    public function __construct(
        int $code,
        private readonly string $arcgisMessage,
        private readonly array $details = [],
    ) {
        parent::__construct(self::format($code, $arcgisMessage, $details), $code);
    }

    public function getArcGisMessage(): string
    {
        return $this->arcgisMessage;
    }

    /**
     * @return list<string>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * fromArray builds an ApiException from a decoded ArcGIS error object
     * ({"code":…, "message":…, "details":[…]}).
     *
     * @param array<string, mixed> $error
     */
    public static function fromArray(array $error): self
    {
        $details = [];
        foreach (Coerce::map($error['details'] ?? null) as $detail) {
            $details[] = Coerce::toString($detail);
        }

        return new self(
            Coerce::toInt($error['code'] ?? null),
            Coerce::toString($error['message'] ?? null),
            $details,
        );
    }

    /**
     * @param list<string> $details
     */
    private static function format(int $code, string $message, array $details): string
    {
        if ($details !== []) {
            return sprintf('arcgis error %d: %s (%s)', $code, $message, implode(', ', $details));
        }

        return sprintf('arcgis error %d: %s', $code, $message);
    }
}
