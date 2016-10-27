<?php
/**
 * Email
 * API for sending email through the server or through third-party services
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Email
{
    /**
     * Fields required to be passed into send()
     *
     * @var array
     */
    public static $required = array('to', 'from', 'subject');

    /**
     * Fields allowed to be passed into send()
     *
     * @var array
     */
    public static $allowed = array('to', 'from', 'subject', 'cc', 'bcc', 'headers', 'text', 'html', 'email_handler', 'email_handler_key');


    /**
     * Available Transactional email services
     *
     * @var array
     */
    public static $email_handlers = array('postmark', 'mandrill', 'sendgrid', 'mailgun');


    /**
     * Send an email using a Transactional email service
     * or native PHP as a fallback.
     *
     * @param array  $attributes  A list of attributes for sending
     * @return bool on success
     */
    public static function send($attributes = array())
    {
        // We first need to ensure we have the minimum fields necessary to send an email.
        $required = array_intersect_key($attributes, array_flip(self::$required));

        // Only process if the required fields are there
        if (count($required) < 3) {
            return false;
        }

        // Get email handler settings
        // We check the passed data for a mailer + key first, and then fall back
        // to the global Statamic config.
        $email_handler = array_get($attributes, 'email_handler', Config::get('email_handler', null));
        $email_handler_key = array_get($attributes, 'email_handler_key', Config::get('email_handler_key', null));

        // Get SMTP settings
        $attributes['smtp'] = array_get($attributes, 'smtp', Config::get('smtp'));

        // Create the mailer. Use Stampie for 3rd party handlers, PHPmailer for the rest.
        if (in_array($email_handler, self::$email_handlers) && $email_handler_key) {
            $mailer = new Statamic_Stampie($email_handler, $email_handler_key, $attributes);
        } else {
            $mailer = new Statamic_PhpMailer($attributes);
        }

        // Send!
        $mailer->send();
    }

    /**
     * Takes an email string and outputs an email / name array
     *
     * @param  string $email  Email / Name string (eg. "email@domain.com John Smith")
     * @return array
     */
    public static function explodeEmailString($email)
    {
        if (preg_match('/^(.*)\s\<(.*)\>/', $email, $matches)) {
            $name = $matches[1];
            $email = $matches[2];
        }

        return array(
            'email' => $email,
            'name'  => isset($name) ? $name : null
        );
    }
}
