<?php

namespace Drupal\ezproxy_stanza\Git;

use Drupal\ezproxy_stanza\Git\Git;

class PublicRepo extends Git {

  public function __construct() {
    parent::__construct(EZPROXY_STANZA_REPO_PUBLIC);
  }
}
