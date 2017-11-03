<?php

namespace Drupal\ezproxy_stanza\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\ezproxy_stanza\Git\PrivateRepo;

class EZProxyStanza extends ControllerBase implements ContainerInjectionInterface {

  public function display() {
    $elements = [];
    $query = \Drupal::entityQuery('node')
          ->condition('type', 'resource')
          ->condition('status', NODE_PUBLISHED);
    $total = clone $query;
    $elements[] = [
      '#markup' => '<h2>'. $this->t('Some general numbers') . '</h2><strong>' . $this->t('Stanzas enabled') . '</strong>: ' . count($total->execute())
    ];
    $query->condition('field_ezproxy_review', 1);
    $elements[] = [
      '#markup' => '<br><strong>' . $this->t('Stanzas enabled needing review') . '</strong>: ' . count($query->execute())
    ];

    $repo = new PrivateRepo();
    $output = array();
    exec('wc -l ' . $repo->getDirectory() . DIRECTORY_SEPARATOR . 'config.txt', $output);
    $lines = array_pop($output);
    $lines = explode(' ', $lines);
    $elements[] = [
      '#markup' => '<br><strong>' . $this->t('Number of lines in config.txt') . '</strong>: ' . number_format($lines[0])
    ];

    $log = explode("\n", $repo->log('--max-count=1'));
    $date = $log[2];
    $date = explode(': ', $date);
    $elements[] = [
      '#markup' => '<br><strong>' . $this->t('Last config update') . '</strong>: ' . $date[1]
    ];


    return $elements;
  }

  public function download() {
    // send them to the config.txt
    $url = file_create_url(EZPROXY_STANZA_REPO_PRIV . DIRECTORY_SEPARATOR . 'config.txt');

    return new RedirectResponse($url);
  }

  public function update() {
    // @todo
    $elements = [];
    $elements[] = [
      '#markup' => ''
    ];
    return $elements;
  }

  public function pull() {
    $repo = new PrivateRepo();
    $repo->pullRemote();

    $response = new Response('1');

    return $response->setPrivate();
  }

  public function view() {
    $build = [];
    $build['search'] = [
      '#type' => 'view',
      '#name' => 'ezproxy_stanza_search',
      '#display_id' => 'default',
    ];

    $build['config'] = \Drupal::formBuilder()->getForm('Drupal\ezproxy_stanza\Form\EZProxyStanzaConfigForm');

    return $build;
  }
}
