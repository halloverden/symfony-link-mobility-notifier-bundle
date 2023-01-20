<?php

namespace HalloVerden\LinkMobilityNotifierBundle\Options;

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final class LinkMobilityOptions implements MessageOptionsInterface {
  const OPTION_SESSION_DATA = 'session_data';

  public function __construct(private array $options = []) {
  }

  public function getSessionData(): ?string {
    return $this->options[self::OPTION_SESSION_DATA] ?? null;
  }

  public function setSessionData(string $sessionData): self {
    $this->options[self::OPTION_SESSION_DATA] = $sessionData;
    return $this;
  }

  public function toArray(): array {
    return $this->options;
  }

  public function getRecipientId(): ?string {
    return null;
  }

}
