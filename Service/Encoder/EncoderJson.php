<?php

namespace BowlOfSoup\NormalizerBundle\Service\Encoder;

use BowlOfSoup\NormalizerBundle\Exception\BosSerializerException;

class EncoderJson extends AbstractEncoder
{
    /** @var string */
    const ERROR_NO_ERROR = 'No error';

    /** @var string */
    const EXCEPTION_PREFIX = 'Error when encoding JSON: ';

    /** @var int */
    private $options;

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return EncoderFactory::TYPE_JSON;
    }

    /**
     * Set json_encode options, keep in mind that options need to be divided like JSON_HEX_TAG | JSON_HEX_QUOT.
     *
     * @param int $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    public function encode($value)
    {
        if (null !== $this->wrapElement) {
            $value = array($this->wrapElement => $value);
        }

        $encodedValue = json_encode($value, (int) $this->options);

        $this->getError();

        return $encodedValue;
    }

    /**
     * Throws error messages.
     *
     * @throws \BowlOfSoup\NormalizerBundle\Exception\BosNormalizerException
     */
    protected function getError()
    {
        if (!$this->jsonLastErrorMsgExists()) {
            return;
        }

        $errorMessage = json_last_error_msg();
        if (static::ERROR_NO_ERROR !== $errorMessage) {
            throw new BosSerializerException(static::EXCEPTION_PREFIX . $errorMessage);
        }
    }

    /**
     * @return bool
     */
    protected function jsonLastErrorMsgExists()
    {
        return function_exists('json_last_error_msg');
    }
}