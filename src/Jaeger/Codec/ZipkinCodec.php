<?php

namespace Jaeger\Codec;

use Jaeger\SpanContext;

use const Jaeger\DEBUG_FLAG;
use const Jaeger\SAMPLED_FLAG;

class ZipkinCodec implements CodecInterface
{
    const SAMPLED_NAME = 'X-B3-Sampled';
    const TRACE_ID_NAME = 'X-B3-TraceId';
    const SPAN_ID_NAME = 'X-B3-SpanId';
    const PARENT_ID_NAME = 'X-B3-ParentSpanId';
    const FLAGS_NAME = 'X-B3-Flags';

    /**
     * {@inheritdoc}
     *
     * @see \Jaeger\Tracer::inject
     *
     * @param SpanContext $spanContext
     * @param mixed $carrier
     *
     * @return void
     */
    public function inject(SpanContext $spanContext, &$carrier)
    {
        $traceIdHex = dechex($spanContext->getTraceId());

        if ($spanContext->getTraceIdHigh() !== null) {
            $traceIdHex = sprintf('%x%016x', $spanContext->getTraceIdHigh(), $spanContext->getTraceId());
        }

        $carrier[self::TRACE_ID_NAME] = $traceIdHex;
        $carrier[self::SPAN_ID_NAME] = dechex($spanContext->getSpanId());
        if ($spanContext->getParentId() != null) {
            $carrier[self::PARENT_ID_NAME] = dechex($spanContext->getParentId());
        }
        $carrier[self::FLAGS_NAME] = (int) $spanContext->getFlags();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Jaeger\Tracer::extract
     *
     * @param mixed $carrier
     * @return SpanContext|null
     */
    public function extract($carrier): ?SpanContext
    {
        $flags = 0;

        if (isset($carrier[strtolower(self::SAMPLED_NAME)])) {
            if ($carrier[strtolower(self::SAMPLED_NAME)] === "1" ||
                strtolower($carrier[strtolower(self::SAMPLED_NAME)] === "true")
            ) {
                $flags = $flags | SAMPLED_FLAG;
            }
        }

        $traceId = $this->getHeader($carrier, self::TRACE_ID_NAME);

        $parentId = $this->getHeaderAsInt64($carrier, self::PARENT_ID_NAME);

        $spanId = $this->getHeaderAsInt64($carrier, self::SPAN_ID_NAME);

        if (isset($carrier[strtolower(self::FLAGS_NAME)])) {
            if ($carrier[strtolower(self::FLAGS_NAME)] === "1") {
                $flags = $flags | DEBUG_FLAG;
            }
        }

        if (null === $traceId || null === $spanId || 0 === $spanId) {
            return null;
        }

        $spanContext = new SpanContext();

        $spanContext->setTraceId($traceId)
            ->setSpanId($spanId)
            ->setParentId($parentId)
            ->setFlags($flags);

        if ($spanContext->getTraceId() === 0) {
            return null;
        }

        return $spanContext;
    }

    private function getHeader(array $carrier, string $name): ?string
    {
        return $carrier[strtolower($name)] ?? null;
    }

    private function getHeaderAsInt64(array $carrier, string $name): ?int
    {
        $value = $this->getHeader($carrier, $name);

        return $value ? CodecUtility::hexToInt64($value) : null;
    }
}
