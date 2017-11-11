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
    $form['edit'] = [
      '#type' => 'submit',
      '#value' => $open_file ? $this->t('Close') : $this->t('Edit'),
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

      $form['actions']['#type'] = 'actions';
      $form['actions']['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Close'),
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ];
      $form['actions']['deploy'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save and deploy'),
        '#button_type' => 'danger',
        '#attributes' => [
          'onclick' => "return confirm('" . $this->t('You sure? This will deploy your config.txt to your EZProxy server.') . "')",
        ],
      ];

    }
    else {
      $form[] = [
        '#markup' => '<p>' . $this->t('Select an EZProxy configuration file to edit') . '</p>'
      ];
    }
    $form['#attached']['library'][] = 'ezproxy_stanza/config-edit';
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
    $submit_button = $form_state->getValue('op')->__toString();
    if ($submit_button === $this->t('Edit')->__toString()) {
      $form_state->setRebuild();
    }
    elseif ($submit_button !== $this->t('Close')->__toString()) {
      $contents = $form_state->getValue('contents');
      $file = $form_state->getValue('file');

      $repo->setFileContents($file, $contents);

      // if something was edited
      if ($repo->hasChanges()) {
        // commit the changes if they select to deploy
        if ($submit_button === $this->t('Save and deploy')->__toString()) {
          $commit_msg = $form_state->getValue('commit_msg');
          $repo->updateRemote($commit_msg, $file);
        }

        drupal_set_message('Your changes have been saved.');
      }
      else {
        drupal_set_message('No changes detected.');
      }
    }
  }
}
