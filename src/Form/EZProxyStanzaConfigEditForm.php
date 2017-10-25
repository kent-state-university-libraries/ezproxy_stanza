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
    foreach (scandir($repo->getDirectory()) as $file) {
      if (in_array($file, ['.', '..', '.git'])) {
        continue;
      }
      $files[$file] = $file;
    }

    $open_file = $form_state->getValue('file');
    $form['file'] = [
      '#type' => 'select',
      '#title' => $this->t('File'),
      '#options' => $files,
      '#required' => TRUE,
      '#default_value' => $open_file,
      '#disabled' => $open_file,
    ];
    if ($open_file) {
      $contents = $repo->getFileContents($open_file);
      $form['contents'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Contents'),
        '#rows' => max(10, count($contents) + 1),
        '#default_value' => implode("\n", $contents)
      ];
      $form['commit_msg'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Commit message (optional)'),
        '#default_value' => 'Update ' . $open_file,
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $open_file ? $this->t('Save') : $this->t('Edit'),
      '#button_type' => 'primary',
      '#suffix' => $open_file ? Link::fromTextandUrl($this->t('Close'), Url::fromRoute('ezproxy_stanza.edit_config'))->toString() : '',
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
    $repo = new PrivateRepo();
    if ($form_state->getValue('op')->__toString() == $this->t('Edit')->__toString()) {
      $form_state->setRebuild();
    }
    else {
      $contents = $form_state->getValue('contents');
      $file = $form_state->getValue('file');

      $repo->setFileContents($file, $contents);

      // if something was edited, commit the changes
      if ($repo->hasChanges()) {
        $commit_msg = $form_state->getValue('commit_msg');
        $repo->updateRemote($commit_msg, $file);
        drupal_set_message('Your changes have been saved.');
      }
      else {
        drupal_set_message('No changes detected.');
      }
    }
  }
}
