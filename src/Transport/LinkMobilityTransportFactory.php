<?php

namespace HalloVerden\LinkMobilityNotifierBundle\Transport;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

final class LinkMobilityTransportFactory extends AbstractTransportFactory {
  public const SCHEME = 'link-mobility';

  /**
   * @inheritDoc
   */
  protected function getSupportedSchemes(): array {
    return [self::SCHEME];
  }

  /**
   * @inheritDoc
   */
  public function create(Dsn $dsn): TransportInterface {
    $scheme = $dsn->getScheme();

    if (self::SCHEME !== $scheme) {
      throw new UnsupportedSchemeException($dsn, self::SCHEME, $this->getSupportedSchemes());
    }

    $user = $this->getUser($dsn);
    $password = $this->getPassword($dsn);
    $from = $dsn->getOption('from', '');
    $sessionData = $dsn->getOption('session_data', '');
    $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
    $port = $dsn->getPort();

    return (new LinkMobilityTransport($user, $password, $from, $sessionData, $this->client, $this->dispatcher))->setHost($host)->setPort($port);
  }

}
