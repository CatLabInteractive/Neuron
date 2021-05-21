<?php


namespace Neuron\Models;

/**
 * Class Error
 * @package Neuron\Models
 */
class Error
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var mixed[]
     */
    private $arguments = [];

    /**
     * @var string
     */
    private $subject;

	/**
	 * @var string
	 */
    private $code;

    /**
     * Error constructor.
     * @param $message
     * @param array $arguments
     */
    public function __construct($message, $arguments = [])
    {
        $this->template = $message;
        $this->arguments = $arguments;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

	/**
	 * @param $code
	 * @return $this
	 */
    public function setCode($code)
	{
		$this->code = $code;
		return $this;
	}

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return mixed[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }

	/**
	 * @return string
	 */
    public function getCode()
	{
		return $this->code;
	}

    /**
     * @return string
     */
    public function getMessage()
    {
        return vsprintf($this->getTemplate(), $this->getArguments());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getMessage();
    }
}
