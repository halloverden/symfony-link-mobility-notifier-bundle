<?php

namespace HalloVerden\LinkMobilityNotifierBundle\Tests\Transport;

use HalloVerden\LinkMobilityNotifierBundle\Transport\LinkMobilityTransportFactory;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;

class LinkMobilityTransportFactoryTest extends TransportFactoryTestCase {

  public function createFactory(): TransportFactoryInterface {
    return new LinkMobilityTransportFactory();
  }

  /**
   * @inheritDoc
   */
  public function supportsProvider(): iterable {
    yield [true, 'link-mobility://user:pass@default'];
    yield [false, 'something-else://user:pass@default'];
  }

  /**
   * @inheritDoc
   */
  public function createProvider(): iterable {
    yield [
      'link-mobility://host.test?from=&session_data=',
      'link-mobility://user:pass@host.test',
    ];
  }

  /**
   * @inheritDoc
   */
  public function incompleteDsnProvider(): iterable {
    yield ['link-mobility://user@default'];
    yield ['link-mobility://default'];
  }

}
