<?php

use Stampie\IdentityInterface;

class Statamic_Message extends \Stampie\Message
{
    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    public $subject = "";

    /**
     * @var array
     */
    public $headers = array();

    /**
     * @var string|null
     */
    public $cc = null;

    /**
     * @var string|null
     */
    public $bcc = null;

    /**
     * @return IdentityInterface|string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string|\Stampie\IdentityInterface $from
     */
    public function setFrom($from)
    {
        $email = $from instanceof IdentityInterface ? $from->getEmail() : $from;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }

        $this->from = $from;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return null|string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param null|string $cc
     */
    public function setCc($cc)
    {
        $this->cc = $cc;
    }

    /**
     * @return null|string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @param null|string $bcc
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;
    }
}