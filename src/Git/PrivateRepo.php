<?php

namespace Drupal\ezproxy_stanza\Git;

use Drupal\ezproxy_stanza\Git\Git;

class PrivateRepo extends Git {
  private $private_key_path;
  private $ezproxy_settings;

  public function __construct() {
    parent::__construct(EZPROXY_STANZA_REPO_PRIV);
    $this->private_key_path = $this->_get_priv_key_path();
    $settings = \Drupal::state()->get('ezproxy_stanza_settings');
    $this->ezproxy_settings = $settings['priv'];
  }

  public function __destruct() {
    // remove the private key (if one exists)
    if (file_exists($this->private_key_path)) {
      $this->unsetPrivateKey();
      unlink($this->private_key_path);
    }
  }

  public function getTopConfig() {

    if (empty($this->ezproxy_settings['auto_update'])) {
      $this->pullRemote();
    }

    $f = fopen($this->getDirectory() . DIRECTORY_SEPARATOR . 'config.txt', 'r');
    $return = [];
    while ($line = fgets($f)) {
      $line = trim($line);
      if ($line === EZPROXY_STANZA_CONFIG_TERMINATOR) {
        break;
      }

      $return[] = $line;
    }

    fclose($f);

    return $return;
  }

  public function setConfig($top_config = FALSE) {
    if (is_string($top_config)) {
      $top_config = explode("\n", $top_config);
    }
    elseif (!$top_config) {
      $top_config = $this->getTopConfig();
    }

    while (trim(end($top_config)) === '') {
      array_pop($top_config);
    }

    if (end($top_config) !== EZPROXY_STANZA_CONFIG_TERMINATOR) {
      $top_config[] = '';
      $top_config[] = EZPROXY_STANZA_CONFIG_TERMINATOR;
    }

    $configs = \Drupal::database()->query('SELECT field_ezproxy_stanza_value
      FROM {node__field_ezproxy_stanza} s
      INNER JOIN {node_field_data} n ON n.nid = s.entity_id AND n.status = 1
      LEFT JOIN {node__field_ezproxy_order} o ON o.entity_id = n.nid
      ORDER BY IF(field_ezproxy_order_value, field_ezproxy_order_value, 0), n.title')->fetchCol();

    if (empty($this->ezproxy_settings['auto_update'])) {
      $this->pullRemote();
    }

    $f = fopen($this->getDirectory() . DIRECTORY_SEPARATOR . 'config.txt', 'w');
    if ($f) {
      foreach ($top_config as $config) {
        $config = trim($config);
        fwrite($f, $config);
        fwrite($f, "\n");
      }

      fwrite($f, "\n\n");

      foreach ($configs as $config) {
        $config = trim($config);
        $config = str_replace("\r\n", "\n", $config);
        fwrite($f, $config);
        fwrite($f, "\n\n");
      }

      fclose($f);
    }
    else {
      drupal_set_message('Could not create config.txt', 'error');
    }
  }

  public function pullRemote() {
    $this->_writePrivateKey();
    parent::pullRemote();
  }

  public function updateRemote($msg = 'Update config.txt') {
    $this->_writePrivateKey();
    parent::updateRemote($msg);
  }

  private function _get_priv_key_path() {
    $path = file_directory_temp();
    $path .= DIRECTORY_SEPARATOR . 'ezproxy_stanza_id_rsa';

    return $path;
  }

  private function _writePrivateKey() {
    $settings = \Drupal::state()->get('ezproxy_stanza_settings');
    if (!empty($settings['authentication']['ssh']['private_key'])) {
      $this->private_key_path = $this->_get_priv_key_path();
      if (!file_exists($this->private_key_path)) {
        $f = fopen($this->private_key_path, 'w');
        if ($f) {
          fwrite($f, $settings['authentication']['ssh']['private_key']);
          fclose($f);
          chmod($this->private_key_path, 0700);
        }
      }
      $this->setPrivateKey($this->private_key_path);
    }
  }
}
