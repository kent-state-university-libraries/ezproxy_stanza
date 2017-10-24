<?php

namespace Drupal\ezproxy_stanza\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\ezproxy_stanza\Git\PrivateRepo;
use Drupal\Core\Url;
use Drupal\Core\Link;

class EZProxyStanzaConfigEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ezproxy_stanza_config_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $repo = new PrivateRepo();
    $top_config = $repo->getTopConfig();
    $form['config'] = [
      '#type' => 'textarea',
      '#title' => 'config.txt',
      '#required' => TRUE,
      '#rows' => max(10, count($top_config) + 1),
      '#default_value' => implode("\n", $top_config)
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#suffix' => Link::fromTextandUrl($this->t('Cancel'), Url::fromRoute('view.ezproxy_stanzas.page_1'))->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $form_state->getValue('config');

    $repo = new PrivateRepo();
    $repo->setConfig($config);

    // if something was edited, commit the changes
    if ($repo->hasChanges()) {
      $repo->updateRemote();
      drupal_set_message('Your changes have been saved.');
    }
    else {
      drupal_set_message('No changes detected.');
    }

    $form_state->setRedirect('view.ezproxy_stanzas.page_1');
  }
}
