<?php

namespace HalloVerden\LinkMobilityNotifierBundle\Options;

enum LinkMobilityMessageType : string {
  case PLAIN_TEXT = '1'; // Non GSM 7-Bit chars will be converted to '?'
  CASE UNICODE = '9'; // Message text should be the HEX representation of a UCS-2 encoded message
}
