<?php

namespace HalloVerden\LinkMobilityNotifierBundle\Tests\Transport;

use HalloVerden\LinkMobilityNotifierBundle\Transport\LinkMobilityTransport;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Test\TransportTestCase;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LinkMobilityTransportTest extends TransportTestCase {

  public function createTransport(HttpClientInterface $client = null): TransportInterface {
    return (new LinkMobilityTransport('user', 'pass', 'test', 'sd', $client ?? $this->createMock(HttpClientInterface::class)))->setHost('host.test');
  }

  /**
   * @inheritDoc
   */
  public function toStringProvider(): iterable {
    yield ['link-mobility://host.test?from=test&session_data=sd', $this->createTransport()];
  }

  /**
   * @inheritDoc
   */
  public function supportedMessagesProvider(): iterable {
    yield [new SmsMessage('12345678', 'Hello')];
  }

  /**
   * @inheritDoc
   */
  public function unsupportedMessagesProvider(): iterable {
    yield [new ChatMessage('Hello!')];
    yield [$this->createMock(MessageInterface::class)];
  }

}
