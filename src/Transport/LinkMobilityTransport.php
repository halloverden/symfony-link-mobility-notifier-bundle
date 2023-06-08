<?php

namespace HalloVerden\LinkMobilityNotifierBundle\Transport;

use HalloVerden\LinkMobilityNotifierBundle\Options\LinkMobilityMessageType;
use HalloVerden\LinkMobilityNotifierBundle\Options\LinkMobilityOptions;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LinkMobilityTransport extends AbstractTransport {
  protected const HOST = 'xml.pswin.com';

  private readonly string $username;
  private readonly string $password;
  private readonly string $from;
  private readonly string $sessionData;

  /**
   * LinkMobilityTransport constructor.
   */
  public function __construct(
    string $username,
    #[\SensitiveParameter] string $password,
    string $from = '',
    string $sessionData = '',
    HttpClientInterface $client = null,
    EventDispatcherInterface $dispatcher = null
  ) {
    parent::__construct($client, $dispatcher);
    $this->password = $password;
    $this->username = $username;
    $this->from = $from;
    $this->sessionData = $sessionData;
  }

  /**
   * @throws TransportExceptionInterface
   */
  protected function doSend(MessageInterface $message): SentMessage {
    if (!$message instanceof SmsMessage) {
      throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
    }

    $options = $message->getOptions() ?? new LinkMobilityOptions();
    if (!$options instanceof LinkMobilityOptions) {
      throw new LogicException(\sprintf('options passed to "%s", must be instance of "%s"', __CLASS__, LinkMobilityOptions::class));
    }

    $phoneNumberUtil = PhoneNumberUtil::getInstance();

    try {
      $phoneNumber = $phoneNumberUtil->parse($message->getPhone());
    } catch (NumberParseException $e) {
      throw new InvalidArgumentException(\sprintf('Unable to parse phone number (%s)', $message->getPhone()), previous: $e);
    }

    if (!$phoneNumberUtil->isValidNumber($phoneNumber)) {
      throw new InvalidArgumentException(\sprintf('The phone number (%s) is not valid', $message->getPhone()));
    }

    $from = $message->getFrom() ?: $this->from;
    $this->validateFrom($from);

    $response = $this->client->request(Request::METHOD_POST, \sprintf('https://%s', $this->getEndpoint()), [
      'headers' => [
        'Content-Type' => 'application/xml; charset=utf-8'
      ],
      'body' => $this->createXml($message->getSubject(), $phoneNumber, $from, $options->getSessionData() ?? $this->sessionData, $options->getMessageType()),
    ]);

    try {
      $content = $response->getContent();
    } catch (ExceptionInterface $e) {
      throw new TransportException('Unable to send SMS (response status is not successful)', $response, previous: $e);
    }

    $xmlElement = \simplexml_load_string($content);
    if (false === $xmlElement) {
      throw new TransportException('Unable to send SMS (Invalid XML)', $response);
    }

    if ((string) $xmlElement->LOGON !== 'OK') {
      throw new TransportException(\sprintf('Unable to send SMS, authentication failed (%s)', $xmlElement->REASON), $response);
    }

    if ((string) $xmlElement->MSGLST->MSG->STATUS !== 'OK') {
      throw new TransportException(\sprintf('Unable to send SMS (%s)', $xmlElement->MSGLST->MSG->INFO), $response);
    }

    $sentMessage = new SentMessage($message, (string) $this);

    if ($messageId = (string) $xmlElement->MSGLST->MSG->REF) {
      $sentMessage->setMessageId($messageId);
    }

    return $sentMessage;
  }

  public function supports(MessageInterface $message): bool {
    return $message instanceof SmsMessage && (null === $message->getOptions() || $message->getOptions() instanceof LinkMobilityOptions);
  }

  public function __toString(): string {
    return \sprintf('%s://%s?from=%s&session_data=%s', LinkMobilityTransportFactory::SCHEME, $this->getEndpoint(), \urlencode($this->from), \urlencode($this->sessionData));
  }

  /**
   * @param string                  $message
   * @param PhoneNumber             $phoneNumber
   * @param string                  $from
   * @param string                  $sessionData
   * @param LinkMobilityMessageType $messageType
   *
   * @return string
   */
  private function createXml(string $message, PhoneNumber $phoneNumber, string $from, string $sessionData, LinkMobilityMessageType $messageType): string {
    $xmlElement = new \SimpleXMLElement('<SESSION></SESSION>');
    $xmlElement->CLIENT = $this->username;
    $xmlElement->PW = $this->password;
    $xmlElement->SD = $sessionData;

    $xmlElement->MSGLST->MSG->OP = $messageType->value;

    $xmlElement->MSGLST->MSG->TEXT = null;
    $node = \dom_import_simplexml($xmlElement->MSGLST->MSG->TEXT);
    $node->appendChild($node->ownerDocument->createCDATASection($message));

    $xmlElement->MSGLST->MSG->RCV = $phoneNumber->getCountryCode() . $phoneNumber->getNationalNumber();
    $xmlElement->MSGLST->MSG->SND = $from;

    return $xmlElement->asXML();
  }

  /**
   * @param string $from
   *
   * @return void
   */
  private function validateFrom(string $from): void {
    if (preg_match('/^[a-zA-Z0-9]{0,11}$/', $from)) {
      return;
    }

    if (preg_match('/^[0-9]{0,15}$/', $from)) {
      return;
    }

    throw new InvalidArgumentException(\sprintf('Invalid "from" (%s), it must numeric with max 15 digits or alphanumeric with up to 11 characters', $from));
  }

}
