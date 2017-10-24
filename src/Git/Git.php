<?php

namespace Drupal\ezproxy_stanza\Git;

use GitWrapper\GitWrapper;

class Git extends GitWrapper {
  private $git = NULL;

  public function __construct($uri) {
    parent::__construct();
    $path = \Drupal::service('file_system')->realpath($uri);
    $this->git = $this->workingCopy($path);
  }

  public function hasChanges() {
    return $this->git->hasChanges();
  }

  public function getOutput() {
    return rtrim($this->git->getOutput());
  }

  public function log() {
    $this->git->clearOutput();
    return rtrim($this->git->log(func_get_args())->getOutput());
  }

  public function getDirectory() {
    return $this->git->getDirectory();
  }

  public function pullRemote() {
    $this->git->fetchAll();
    $this->git->checkout('master');
    $this->git->pull('origin', 'master');
  }

  public function updateRemote($msg = 'Update config.txt') {
    $this->git->commit('config.txt', array('m' => $msg));
    $this->git->push('origin', 'master');
  }

  public function updateOrigin($url) {
    $remote = 'origin';
    if ($this->git->hasRemote($remote)) {
      if ($this->git->getRemoteUrl('origin') === $url) {
        return;
      }
      $this->git->removeRemote($remote);
    }

    $this->git->addRemote($remote, $url);
  }
}
