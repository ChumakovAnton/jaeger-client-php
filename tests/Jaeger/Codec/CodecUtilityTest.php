<?php

namespace Jaeger\Tests\Codec;

use Jaeger\Codec\CodecUtility;
use PHPUnit\Framework\TestCase;

class CodecUtilityTest extends TestCase
{
    /**
     * @dataProvider hexDataProvider
     */
    public function testHexToInt64(string $hex, int $expected)
    {
        $result = CodecUtility::hexToInt64($hex);

        $this->assertSame($expected, $result);
    }

    public function hexDataProvider()
    {
        yield [
            '0',
            0
        ];

        yield [
            '32834e4115071776',
            3639838965278119798
        ];

        yield [
            '39aaa11fa7f44005a57d13f2796e0778',
            -6522034753222342792
        ];
    }
}
