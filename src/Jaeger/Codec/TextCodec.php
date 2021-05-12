<?php

namespace Jaeger\Codec;

use Exception;
use Jaeger\SpanContext;

use const Jaeger\TRACE_ID_HEADER;
use const Jaeger\BAGGAGE_HEADER_PREFIX;
use const Jaeger\DEBUG_ID_HEADER_KEY;

class TextCodec implements CodecInterface
{
    private $urlEncoding;
    private $traceIdHeader;
    private $baggagePrefix;
    private $debugIdHeader;
    private $prefixLength;

    /**
     * @param bool $urlEncoding
     * @param string $traceIdHeader
     * @param string $baggageHeaderPrefix
     * @param string $debugIdHeader
     */
    public function __construct(
        bool $urlEncoding = false,
        string $traceIdHeader = TRACE_ID_HEADER,
        string $baggageHeaderPrefix = BAGGAGE_HEADER_PREFIX,
        string $debugIdHeader = DEBUG_ID_HEADER_KEY
    )
    {
        $this->urlEncoding = $urlEncoding;
        $this->traceIdHeader = str_replace('_', '-', strtolower($traceIdHeader));
        $this->baggagePrefix = str_replace('_', '-', strtolower($baggageHeaderPrefix));
        $this->debugIdHeader = str_replace('_', '-', strtolower($debugIdHeader));
        $this->prefixLength = strlen($baggageHeaderPrefix);
    }

    /**
     * {@inheritdoc}
     *
     * @param SpanContext $spanContext
     * @param mixed $carrier
     *
     * @return void
     * @see \Jaeger\Tracer::inject
     *
     */
    public function inject(SpanContext $spanContext, &$carrier)
    {
        $carrier[$this->traceIdHeader] = $this->spanContextToString($spanContext);

        $baggage = $spanContext->getBaggage();
        if (empty($baggage)) {
            return;
        }

        foreach ($baggage as $key => $value) {
            $encodedValue = $value;

            if ($this->urlEncoding) {
                $encodedValue = urlencode($value);
            }

            $carrier[$this->baggagePrefix . $key] = $encodedValue;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $carrier
     * @return SpanContext|null
     *
     * @throws Exception
     * @see \Jaeger\Tracer::extract
     *
     */
    public function extract($carrier): ?SpanContext
    {
        $traceId = null;
        $baggage = null;
        $debugId = null;

        foreach ($carrier as $key => $value) {
            $ucKey = strtolower($key);

            if (is_array($value)) {
                $value = current($value);
            }

            if ($this->urlEncoding) {
                $value = urldecode($value);
            }

            if ($ucKey === $this->traceIdHeader) {
                $traceId = $value;
            } elseif ($this->startsWith($ucKey, $this->baggagePrefix)) {
                $attrKey = substr($key, $this->prefixLength);
                if ($baggage === null) {
                    $baggage = [strtolower($attrKey) => $value];
                } else {
                    $baggage[strtolower($attrKey)] = $value;
                }
            } elseif ($ucKey === $this->debugIdHeader) {
                $debugId = $value;
            }
        }

        $spanContext = null;

        if ($traceId !== null) {
            $spanContext = $this->spanContextFromString($traceId);

            if ($baggage !== null) {
                $spanContext = $spanContext->setBaggage($baggage);
            }
        } else {
            if ($baggage !== null) {
                throw new Exception('baggage without trace ctx');
            }

            if ($debugId !== null) {
                $spanContext = new SpanContext(null, null, null, null, [], $debugId);
            }
        }

        return $spanContext;
    }

    private function spanContextToString(SpanContext $spanContext): string
    {
        $parentId = $spanContext->getParentId() ?? 0;

        if (null !== $spanContext->getTraceIdHigh()) {
            return sprintf(
                '%x%016x:%x:%x:%x',
                $spanContext->getTraceIdHigh(),
                $spanContext->getTraceId(),
                $spanContext->getSpanId(),
                $parentId,
                $spanContext->getFlags());
        }

        return sprintf(
            '%x:%x:%x:%x',
            $spanContext->getTraceId(),
            $spanContext->getSpanId(),
            $parentId,
            $spanContext->getFlags()
        );
    }

    /**
     * Create a span context from a string.
     *
     * @throws Exception
     */
    private function spanContextFromString($value): SpanContext
    {
        $parts = explode(':', $value);

        if (count($parts) != 4) {
            throw new Exception('Malformed tracer state string.');
        }

        $spanContext = new SpanContext();

        $spanContext->setTraceId($parts[0])
            ->setSpanId(CodecUtility::hexToInt64($parts[1]))
            ->setParentId(CodecUtility::hexToInt64($parts[2]))
            ->setFlags($parts[3]);

        return $spanContext;
    }

    /**
     * Checks that a string ($haystack) starts with a given prefix ($needle).
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) == $needle;
    }
}
