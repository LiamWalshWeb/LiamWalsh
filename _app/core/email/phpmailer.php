<?php

class Statamic_PhpMailer
{
    /**
     * @var PHPMailer
     */
    private $mailer;

    /**
     * @var array
     */
    private $attributes;

    /**
     * Create a new instance
     *
     * @param array $attributes
     */
    public function __construct($attributes)
    {
        $this->mailer = $this->createMailer();
        $this->attributes = $attributes;

        $this->populateMessage();
    }

    /**
     * Send an email message
     */
    public function send()
    {
        $this->mailer->send();
    }

    /**
     * Create the PHPMailer instance
     *
     * @return PHPMailer
     */
    private function createMailer()
    {
        $this->mailer = new PHPMailer(true);

        if ($this->attributes['smtp']) {
            $this->smtp();
        } elseif (array_get($this->attributes, 'sendmail')) {
            $this->mailer->isSendmail();
        } else {
            $this->mailer->isMail();
        }

        return $this->mailer;
    }

    /**
     * Add SMTP settings to the mailer
     */
    private function smtp()
    {
        $smtp = array_get($this->attributes, 'smtp');

        $this->mailer->isSMTP();

        foreach (array('host', 'port', 'username', 'password') as $field) {
            if ($value = array_get($smtp, $field)) {
                $method = ucfirst($field);
                $this->mailer->$method($value);
            }
        }

        if ($smtp_secure = array_get($smtp, 'secure', false)) {
            $this->mailer->SMTPSecure = $smtp_secure;
        }

        if (array_get($smtp, 'auth', false) === true) {
            $this->mailer->SMTPAuth = true;
        }
    }

    /**
     * Populate the email message
     */
    private function populateMessage()
    {
        $attributes = $this->attributes;

        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Subject = $attributes['subject'];

        $from_parts = Email::explodeEmailString($attributes['from']);
        $this->mailer->setFrom($from_parts['email'], $from_parts['name']);

        $to = Helper::ensureArray($this->attributes['to']);
        foreach ($to as $to_addr) {
            $to_parts = Email::explodeEmailString($to_addr);
            $this->mailer->addAddress($to_parts['email'], $to_parts['name']);
        }

        if (isset($attributes['cc'])) {
            $cc = Helper::ensureArray($attributes['cc']);
            foreach ($cc as $cc_addr) {
                $cc_parts = Email::explodeEmailString($cc_addr);
                $this->mailer->addCC($cc_parts['email'], $cc_parts['name']);
            }
        }

        if (isset($attributes['bcc'])) {
            $bcc = Helper::ensureArray($attributes['bcc']);
            foreach ($bcc as $bcc_addr) {
                $bcc_parts = Email::explodeEmailString($bcc_addr);
                $this->mailer->addBCC($bcc_parts['email'], $bcc_parts['name']);
            }
        }

        if (isset($attributes['html'])) {
            $this->mailer->msgHTML($attributes['html']);

            if (isset($attributes['text'])) {
                $this->mailer->AltBody = $attributes['text'];
            }
        } elseif (isset($attributes['text'])) {
            $this->mailer->msgHTML($attributes['text']);
        }

    }
}
