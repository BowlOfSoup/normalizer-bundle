<?php

declare(strict_types=1);

namespace BowlOfSoup\NormalizerBundle\Tests\Service\Extractor;

use BowlOfSoup\NormalizerBundle\Annotation\Normalize;
use BowlOfSoup\NormalizerBundle\Exception\BosNormalizerException;
use BowlOfSoup\NormalizerBundle\Service\Extractor\PropertyExtractor;
use BowlOfSoup\NormalizerBundle\Tests\ArraySubset;
use BowlOfSoup\NormalizerBundle\Tests\assets\ProxyObject;
use BowlOfSoup\NormalizerBundle\Tests\assets\SomeClass;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;

class PropertyExtractorTest extends TestCase
{
    /** @var \BowlOfSoup\NormalizerBundle\Service\Extractor\PropertyExtractor|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyExtractor;

    protected function setUp(): void
    {
        $this->propertyExtractor = $this
            ->getMockBuilder(PropertyExtractor::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    /**
     * @testdox Get all properties of a class.
     */
    public function testGetProperties()
    {
        /** @var \BowlOfSoup\NormalizerBundle\Service\Extractor\PropertyExtractor $stubPropertyExtractor */
        $stubPropertyExtractor = $this
            ->getMockBuilder(PropertyExtractor::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $someClass = new SomeClass();
        $properties = $stubPropertyExtractor->getProperties($someClass);
        $this->assertCount(5, $properties);

        $property = $properties[0];
        $this->assertSame('property32', $property->getName());
        $this->assertInstanceOf(\ReflectionProperty::class, $property);
        $property->setAccessible(true);
        $this->assertSame(123, $property->getValue($someClass));

        $property = $properties[1];
        $this->assertSame('property53', $property->getName());
        $this->assertInstanceOf(\ReflectionProperty::class, $property);
        $this->assertSame('string', $property->getValue($someClass));

        $property = $properties[2];
        $this->assertSame('property76', $property->getName());
        $this->assertInstanceOf(\ReflectionProperty::class, $property);

        $property = $properties[3];
        $this->assertSame('property2', $property->getName());
        $this->assertInstanceOf(\ReflectionProperty::class, $property);
        $property->setAccessible(true);
        $this->assertSame([], $property->getValue($someClass));

        $property = $properties[4];
        $this->assertSame('property1', $property->getName());
        $this->assertInstanceOf(\ReflectionProperty::class, $property);
        $property->setAccessible(true);
        $this->assertSame('string', $property->getValue($someClass));
    }

    /**
     * @testdox Extracting property annotations.
     */
    public function testExtractPropertyAnnotations(): void
    {
        $annotation = new Normalize([]);
        $someClass = new SomeClass();
        $properties = $this->propertyExtractor->getProperties($someClass);

        $annotationResult = [$annotation];

        /** @var \Doctrine\Common\Annotations\AnnotationReader $mockAnnotationReader */
        $mockAnnotationReader = $this
            ->getMockBuilder(AnnotationReader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPropertyAnnotations'])
            ->getMock();
        $mockAnnotationReader
            ->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($this->equalTo($properties[0]))
            ->willReturn($annotationResult);

        $propertyExtractor = new PropertyExtractor($mockAnnotationReader);
        $result = $propertyExtractor->extractPropertyAnnotations($properties[0], get_class($annotation));

        ArraySubset::assert([$annotation], $result);
    }

    /**
     * @testdox Get a value for a property.
     */
    public function testGetPropertyValue(): void
    {
        $someClass = new SomeClass();
        $properties = $this->propertyExtractor->getProperties($someClass);
        foreach ($properties as $property) {
            if ('property53' === $property->getName()) {
                $result = $this->propertyExtractor->getPropertyValue($someClass, $property);

                $this->assertSame('string', $result);
            }
        }
    }

    /**
     * @testdox Get a value for a property, force get method
     */
    public function testGetPropertyValueForceGetMethod(): void
    {
        $someClass = new SomeClass();
        $properties = $this->propertyExtractor->getProperties($someClass);
        foreach ($properties as $property) {
            if ('property32' === $property->getName()) {
                $result = $this->propertyExtractor->getPropertyValue(
                    $someClass,
                    $property,
                    PropertyExtractor::FORCE_PROPERTY_GET_METHOD
                );

                $this->assertSame(123, $result);
            }
        }
    }

    /**
     * @testdox Get a value for a property, force get method, no method available, force get from public/protected.
     */
    public function testGetPropertyValueForceGetMethodNoMethodAvailable(): void
    {
        $someClass = new SomeClass();
        $properties = $this->propertyExtractor->getProperties($someClass);
        foreach ($properties as $property) {
            if ('property53' === $property->getName()) {
                $result = $this->propertyExtractor->getPropertyValue(
                    $someClass,
                    $property,
                    PropertyExtractor::FORCE_PROPERTY_GET_METHOD
                );

                $this->assertSame('string', $result);
            }
        }
    }

    /**
     * @testdox Get a value for a property, force get method, no method available, force get, but not public/protected.
     */
    public function testGetPropertyValueForceGetMethodNoMethodAvailableNoAccess(): void
    {
        $this->expectException(BosNormalizerException::class);
        $this->expectExceptionMessage('Unable to get property value. No get() method found for property property76');

        $someClass = new SomeClass();
        $properties = $this->propertyExtractor->getProperties($someClass);
        foreach ($properties as $property) {
            if ('property76' === $property->getName()) {
                $this->propertyExtractor->getPropertyValue(
                    $someClass,
                    $property,
                    PropertyExtractor::FORCE_PROPERTY_GET_METHOD
                );
            }
        }
    }

    /**
     * @testdox Get a value for a property, force get method, no method available, force get, is Doctrine Proxy.
     */
    public function testGetPropertyValueForceGetMethodNoMethodAvailableDoctrineProxy(): void
    {
        $this->expectException(BosNormalizerException::class);
        $this->expectExceptionMessage('Unable to initiate Doctrine proxy, not get() method found for property proxyProperty');

        $proxyObject = new ProxyObject();
        $properties = $this->propertyExtractor->getProperties($proxyObject);
        foreach ($properties as $property) {
            if ('proxyProperty' === $property->getName()) {
                $this->propertyExtractor->getPropertyValue(
                    $proxyObject,
                    $property
                );
            }
        }
    }

    /**
     * @testdox Get a value for a property, Doctrine Proxy, force get method, assert ID = integer.
     */
    public function testGetPropertyDoctrineProxyForceGetMethodAssertIdInteger(): void
    {
        $result = null;

        $proxyObject = new ProxyObject();
        $properties = $this->propertyExtractor->getProperties($proxyObject);
        foreach ($properties as $property) {
            if ('id' === $property->getName()) {
                $result = $this->propertyExtractor->getPropertyValue(
                    $proxyObject,
                    $property
                );
            }
        }

        $this->assertSame(123, $result);
    }

    /**
     * @testdox Get a value for a property by specifying method.
     */
    public function testGetPropertyValueByMethod(): void
    {
        $someClass = new SomeClass();
        $result = $this->propertyExtractor->getPropertyValueByMethod($someClass, 'getProperty32');

        $this->assertSame(123, $result);
    }

    /**
     * @testdox Get a value for a property by specifying method, no method available.
     */
    public function testGetPropertyValueByMethodNoMethodAvailable(): void
    {
        $someClass = new SomeClass();
        $result = $this->propertyExtractor->getPropertyValueByMethod($someClass, 'getProperty53');

        $this->assertNull($result);
    }

    /**
     * @testdox Get a value for a property by specifying method, no method available.
     */
    public function testGetId(): void
    {
        $someClass = new SomeClass();
        $result = $this->propertyExtractor->getId($someClass);

        $this->assertSame(777, $result);
    }
}
