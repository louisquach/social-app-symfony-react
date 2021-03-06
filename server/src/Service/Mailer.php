<?php
namespace App\Service;

use Twig\Environment;

/**
 * Class Mailer
 * @package App\Service
 */
class Mailer
{
    protected $mailer;
    protected $twig;

    public function __construct(\Swift_Mailer $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * Send email
     *
     * @param   string   $template      email template
     * @param   mixed    $parameters    custom params for template
     * @param   string   $to            to email address or array of email addresses
     * @param   string   $from          from email address
     * @param   string   $fromName      from name
     *
     * @return  boolean                 send status
     */
    public function sendEmail($template, $parameters, $to, $from, $fromName = null)
    {
        $template = $this->twig->loadTemplate('emails/' . $template);

        $subject  = $template->renderBlock('subject', $parameters);
        $bodyHtml = $template->renderBlock('body_html', $parameters);
        $bodyText = $template->renderBlock('body_text', $parameters);

        try {
            $message = (new \Swift_Message($subject))
                ->setFrom($from, $fromName)
                ->setTo($to)
                ->setBody($bodyHtml, 'text/html')
                ->addPart($bodyText, 'text/plain')
            ;
            $response = $this->mailer->send($message);

        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

        return $response;
    }
}