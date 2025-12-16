<?php

namespace App\Mail\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * SendGrid HTTP API Transport for Laravel Mail.
 *
 * Uses SendGrid's v3 Mail Send API to bypass SMTP port restrictions
 * on hosting providers like DigitalOcean.
 */
class SendGridTransport extends AbstractTransport
{
    /**
     * SendGrid API endpoint.
     */
    protected const API_ENDPOINT = 'https://api.sendgrid.com/v3/mail/send';

    /**
     * The Guzzle HTTP client instance.
     */
    protected Client $client;

    /**
     * The SendGrid API key.
     */
    protected string $apiKey;

    /**
     * Create a new SendGrid transport instance.
     */
    public function __construct(
        string $apiKey,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($dispatcher, $logger);

        $this->apiKey = $apiKey;
        $this->client = new Client([
            'timeout' => 30.0,
            'connect_timeout' => 10.0,
        ]);
    }

    /**
     * Send the email message via SendGrid API.
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $payload = $this->buildPayload($email, $envelope);

        try {
            $response = $this->client->post(self::API_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();

            // SendGrid returns 202 Accepted for successful requests
            if ($statusCode < 200 || $statusCode >= 300) {
                $body = (string) $response->getBody();
                throw new TransportException(
                    sprintf('SendGrid API returned status %d: %s', $statusCode, $body)
                );
            }

            // Extract message ID from response header for tracking
            $messageId = $response->getHeader('X-Message-Id')[0] ?? null;
            if ($messageId) {
                $email->getHeaders()->addHeader('X-SendGrid-Message-ID', $messageId);
            }

        } catch (GuzzleException $e) {
            throw new TransportException(
                sprintf('SendGrid API request failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Build the SendGrid API payload from the email message.
     */
    protected function buildPayload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'personalizations' => [[
                'to' => $this->formatAddresses($this->getToRecipients($email, $envelope)),
            ]],
            'from' => $this->formatAddress($envelope->getSender()),
            'subject' => $email->getSubject() ?? '(No Subject)',
            'content' => [],
        ];

        // Add CC recipients if present
        $cc = $email->getCc();
        if (!empty($cc)) {
            $payload['personalizations'][0]['cc'] = $this->formatAddresses($cc);
        }

        // Add BCC recipients if present
        $bcc = $email->getBcc();
        if (!empty($bcc)) {
            $payload['personalizations'][0]['bcc'] = $this->formatAddresses($bcc);
        }

        // Add Reply-To if present
        $replyTo = $email->getReplyTo();
        if (!empty($replyTo)) {
            $payload['reply_to'] = $this->formatAddress($replyTo[0]);
        }

        // Add content - text first, then HTML (SendGrid requirement)
        $textBody = $email->getTextBody();
        if ($textBody) {
            $payload['content'][] = [
                'type' => 'text/plain',
                'value' => $textBody,
            ];
        }

        $htmlBody = $email->getHtmlBody();
        if ($htmlBody) {
            $payload['content'][] = [
                'type' => 'text/html',
                'value' => $htmlBody,
            ];
        }

        // Ensure at least one content type is present
        if (empty($payload['content'])) {
            $payload['content'][] = [
                'type' => 'text/plain',
                'value' => ' ',
            ];
        }

        return $payload;
    }

    /**
     * Get TO recipients excluding CC and BCC addresses.
     *
     * @return Address[]
     */
    protected function getToRecipients(Email $email, Envelope $envelope): array
    {
        $cc = array_map(fn(Address $a) => $a->getAddress(), $email->getCc());
        $bcc = array_map(fn(Address $a) => $a->getAddress(), $email->getBcc());
        $exclude = array_merge($cc, $bcc);

        return array_filter(
            $envelope->getRecipients(),
            fn(Address $address) => !in_array($address->getAddress(), $exclude, true)
        );
    }

    /**
     * Format an array of Address objects for SendGrid API.
     *
     * @param Address[] $addresses
     */
    protected function formatAddresses(array $addresses): array
    {
        return array_values(array_map(
            fn(Address $address) => $this->formatAddress($address),
            $addresses
        ));
    }

    /**
     * Format a single Address object for SendGrid API.
     */
    protected function formatAddress(Address $address): array
    {
        $formatted = ['email' => $address->getAddress()];

        $name = $address->getName();
        if ($name !== '') {
            $formatted['name'] = $name;
        }

        return $formatted;
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'sendgrid';
    }
}
