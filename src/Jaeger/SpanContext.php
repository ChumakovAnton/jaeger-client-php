<?php

namespace Jaeger;

use ArrayIterator;
use Jaeger\Codec\CodecUtility;
use OpenTracing\SpanContext as OTSpanContext;

class SpanContext implements OTSpanContext
{
    private $traceIdLow;

    private $traceIdHigh;

    private $spanId;

    private $parentId;

    private $flags;

    /**
     * @var array
     */
    private $baggage;

    private $debugId;

    /**
     * SpanContext constructor.
     */
    public function __construct(
        ?int $traceId = null,
        ?int $spanId = null,
        ?int $parentId = null,
        ?int $flags = null,
        ?array $baggage = [],
        ?string $debugId = null,
        ?int $traceIdHigh = null
    )
    {
        $this->traceIdLow = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->flags = $flags;
        $this->baggage = is_array($baggage) ? $baggage : [];
        $this->debugId = $debugId;
        $this->traceIdHigh = $traceIdHigh;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->baggage);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem(string $key): ?string
    {
        return array_key_exists($key, $this->baggage) ? $this->baggage[$key] : null;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param string $value
     * @return SpanContext
     */
    public function withBaggageItem(string $key, string $value): OTSpanContext
    {
        return new self(
            $this->traceIdLow,
            $this->spanId,
            $this->parentId,
            $this->flags,
            [$key => $value] + $this->baggage,
            $this->debugId,
            $this->traceIdHigh
        );
    }

    public function setBaggage(array $baggage): self
    {
        $this->baggage = $baggage;
        return $this;
    }

    public function getTraceId(): ?int
    {
        return $this->traceIdLow;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getSpanId(): ?int
    {
        return $this->spanId;
    }

    public function getFlags(): ?int
    {
        return $this->flags;
    }

    public function getBaggage(): array
    {
        return $this->baggage;
    }

    public function getDebugId(): ?string
    {
        return $this->debugId;
    }

    public function isDebugIdContainerOnly(): bool
    {
        return ($this->traceIdLow === null) && ($this->debugId !== null);
    }

    public function setTraceId(string $traceId): self
    {
        if (strlen($traceId) > 16) {
            $this->traceIdLow = CodecUtility::hexToInt64(substr($traceId, 16));
            $this->traceIdHigh = CodecUtility::hexToInt64(substr($traceId, 0, 16));
        } else {
            $this->traceIdLow = CodecUtility::hexToInt64($traceId);
            $this->traceIdHigh = null;
        }

        return $this;
    }

    public function getTraceIdHigh(): ?int
    {
        return $this->traceIdHigh;
    }

    public function setSpanId(?int $spanId): self
    {
        $this->spanId = $spanId;
        return $this;
    }

    public function setParentId(?int $parentId): self
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function setFlags(?int $flags): SpanContext
    {
        $this->flags = $flags;
        return $this;
    }
}
