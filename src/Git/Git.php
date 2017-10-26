<?php

namespace Drupal\ezproxy_stanza\Git;

use GitWrapper\GitWrapper;

class Git extends GitWrapper {
  private $git = NULL;
  private $ezproxy_settings;

  public function __construct($uri, $repo = 'public') {
    parent::__construct();
    $path = \Drupal::service('file_system')->realpath($uri);
    $this->git = $this->workingCopy($path);

    $settings = \Drupal::state()->get('ezproxy_stanza_settings');
    $this->ezproxy_settings = $settings[$repo];
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

  public function diff() {
    return $this->git->diff(func_get_args());
  }

  public function getDirectory() {
    return $this->git->getDirectory();
  }

  public function setFileContents($file, $contents) {
    if (empty($this->ezproxy_settings['auto_update'])) {
      $this->pullRemote();
    }

    $f = fopen($this->getDirectory() . DIRECTORY_SEPARATOR . $file, 'w');
    if ($f) {
      $contents = str_replace("\r\n", "\n", $contents);
      fwrite($f, $contents);
      fclose($f);
    }

    return $contents;
  }

  public function getFileContents($file, $full = FALSE) {
    if (empty($this->ezproxy_settings['auto_update'])) {
      $this->pullRemote();
    }

    $contents = [];
    $f = fopen($this->getDirectory() . DIRECTORY_SEPARATOR . $file, 'r');
    if ($f) {
      while ($line = fgets($f)) {
        $line = trim($line);
        if (!$full && $line === EZPROXY_STANZA_CONFIG_TERMINATOR) {
          break;
        }

        $contents[] = $line;
      }

      fclose($f);
    }


    return $contents;
  }

  public function pullRemote() {
    $this->git->fetchAll();
    $this->git->checkout('master');
    $this->git->pull('origin', 'master');
  }

  public function updateRemote($msg = 'Update config.txt', $file = 'config.txt') {
    $email = \Drupal::currentUser()->getEmail();
    $this->setEnvVar('GIT_COMMITTER_EMAIL', $email);
    $this->setEnvVar('GIT_AUTHOR_EMAIL', $email);
    $this->git->commit($file, array('m' => $msg));
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
