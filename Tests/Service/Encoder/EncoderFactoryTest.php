<?php

namespace BowlOfSoup\NormalizerBundle\Tests\Service\Encoder;

use BowlOfSoup\NormalizerBundle\Service\Encoder\EncoderFactory;

class ClassExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Factory returns correct encoder.
     */
    public function testManufacturingEncoder()
    {
        $this->assertInstanceOf(
            'BowlOfSoup\NormalizerBundle\Service\Encoder\EncoderJson',
            EncoderFactory::getEncoder(EncoderFactory::TYPE_JSON)
        );
        $this->assertInstanceOf(
            'BowlOfSoup\NormalizerBundle\Service\Encoder\EncoderXml',
            EncoderFactory::getEncoder(EncoderFactory::TYPE_XML)
        );
    }

    /**
     * @testdox Unknown encoder.
     *
     * @expectedException \BowlOfSoup\NormalizerBundle\Exception\BosSerializerException
     * @expectedExceptionMessage Unknown encoder type.
     */
    public function testUnknownEncoder()
    {
        EncoderFactory::getEncoder('something');
    }
}