<?php

declare(strict_types=1);

namespace BowlOfSoup\NormalizerBundle\Service\Extractor;

class MethodExtractor extends AbstractExtractor
{
    /** @var string */
    public const TYPE = 'method';

    /**
     * Extract all annotations for a (reflected) class method.
     *
     * @param string|object $annotation
     */
    public function extractMethodAnnotations(\ReflectionMethod $objectMethod, $annotation): array
    {
        $annotations = [];

        $docComment = $objectMethod->getDocComment();
        if ($docComment
            && false !== strpos(strtolower($docComment), '@inheritdoc')
            && empty($this->annotationReader->getMethodAnnotations($objectMethod))
        ) {
            try {
                $objectMethod = $objectMethod->getDeclaringClass()->getParentClass()->getMethod($objectMethod->getName());
            } catch (\ReflectionException $e) {
                // No parent class, or method does not exist in parent. This can happen when normalizing a proxy class.
            }
        }

        $methodAnnotations = $this->annotationReader->getMethodAnnotations($objectMethod);
        foreach ($methodAnnotations as $methodAnnotation) {
            if ($methodAnnotation instanceof $annotation) {
                $annotations[] = $methodAnnotation;
            }
        }

        return $annotations;
    }

    /**
     * @param object|string $object
     *
     * @throws \ReflectionException
     */
    public function getMethods($object): array
    {
        if (!is_object($object)) {
            return [];
        }

        $reflectedClass = new \ReflectionClass($object);

        return $reflectedClass->getMethods(
            \ReflectionMethod::IS_PUBLIC |
            \ReflectionMethod::IS_PROTECTED |
            \ReflectionMethod::IS_PRIVATE
        );
    }
}
