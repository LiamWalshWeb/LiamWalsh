<?php

class Statamic_Stampie
{
    /**
     * @var \Stampie\MessageInterface
     */
    private $message;

    /**
     * Create a new instance
     *
     * @param string $handler
     * @param string $key
     * @param array $attributes
     */
    public function __construct($handler, $key, $attributes)
    {
        $this->handler = $this->createHandler($handler, $key);
        $this->message = $this->createMessage($attributes);
    }

    /**
     * Send an email message
     */
    public function send()
    {
        $this->handler->send($this->message);
    }

    /**
     * Create an email message
     *
     * @param $attributes
     * @return Statamic_Message
     */
    private function createMessage($attributes)
    {
        // Create identities
        $to = Email::explodeEmailString($attributes['to']);
        $to = new \Stampie\Identity($to['email'], $to['name']);
        $from = Email::explodeEmailString($attributes['from']);
        $from = new \Stampie\Identity($from['email'], $from['name']);

        // Initialize the message
        $message = new Statamic_Message($to);

        // Add required fields
        $message->setFrom($from);
        $message->setSubject($attributes['subject']);

        // Add remaining fields
        foreach (array('text', 'html', 'cc', 'bcc', 'headers') as $field) {
            if ($value = array_get($attributes, $field)) {
                $method = 'set' . ucfirst($field);
                $message->$method($value);
            }
        }

        return $message;
    }

    /**
     * Creates a Stampie Mailer instance
     *
     * @param string  $email_handler  Name of the email handler to use
     * @param string  $email_handler_key  Email Handler Token
     * @return \Stampie\Mailer
     */
    private function createHandler($email_handler, $email_handler_key)
    {
        $adapter = new Stampie\Adapter\Buzz(new Buzz\Browser());

        if ($email_handler == 'postmark') {
            return new Stampie\Mailer\Postmark($adapter, $email_handler_key);
        } elseif ($email_handler == 'mandrill') {
            return new Stampie\Mailer\Mandrill($adapter, $email_handler_key);
        } elseif ($email_handler == 'sendgrid') {
            return new Stampie\Mailer\SendGrid($adapter, $email_handler_key);
        } elseif ($email_handler == 'mailgun') {
            return new Stampie\Mailer\MailGun($adapter, $email_handler_key);
        }

        \Log::error("Could not initialize email handler `" . $email_handler . "`. Unknown service.", "core", "email");
    }

}