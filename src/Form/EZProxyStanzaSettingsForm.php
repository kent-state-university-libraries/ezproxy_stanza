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

class EZProxyStanzaSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ezproxy_stanza_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = \Drupal::state()->get('ezproxy_stanza_settings');

    $form['public'] = [
      '#type' => 'details',
      '#title' => $this->t('OCLC repository'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $public_origin = isset($settings['public']['origin']) ? $settings['public']['origin'] : 'https://github.com/kent-state-university-libraries/oclc-ezproxy-database-stanzas';
    $form['public']['origin'] = [
      '#type' => 'textfield',
      '#title' => $this->t("URL to repository that stores OCLC's official stanzas"),
      '#description' => $this->t('Git repository with public access to checkout. This URL might eventually be configurable, but for now use the one implemented for this module.'),
      '#required' => TRUE,
      '#size' => strlen($public_origin),
      '#default_value' => $public_origin,
      '#disabled' => TRUE,
    ];

    $form['priv'] = [
      '#type' => 'details',
      '#title' => $this->t('Your local repository'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['priv']['origin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL to config.txt repository'),
      '#default_value' => isset($settings['priv']['origin']) ? $settings['priv']['origin'] : '',
    ];

    $description = 'Many git repository management systems, such as GitLab and GitHub, offer web hooks when actions are performed on a repository.<br>';
    $description .= 'If your local repository is hosted in such a management system, you can add a webhook whenever the repository is pushed to to access this URL:<br>';
    $url = Url::fromRoute('ezproxy_stanza.pull_config', [], ['absolute' => TRUE]);
    $description .= Link::fromTextandUrl($url->toString(), $url)->toString();
    $description .= '<br><br><strong>Not configuring this has a performance impact</strong>. This will keep your local repository up to date with changes made outside of this system.<br>';
    $description .= 'If you don\'t configure this, every time you perform an action your system will need to check in with your remote repository to ensure it is up to date';
    $form['priv']['auto_update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Local repository is automatically updated from remote'),
      '#description' => $this->t($description),
      '#default_value' => !empty($settings['priv']['auto_update']),
    ];

    $form['authentication'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication'),
      '#description' => $this->t('How to authenticate. Either by using the SSH private key for you repository or username password.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['authentication']['ssh'] = [
      '#type' => 'details',
      '#title' => $this->t('SSH'),
      '#description' => $this->t('Ideally this is the private key of a deploy key that only has access to your local repository.'),
      '#open' => TRUE, //!empty($settings['authentication']['ssh']['private_key']),
      '#tree' => TRUE,
    ];
    $form['authentication']['ssh']['private_key'] = [
      '#type' => 'textarea',
      '#title' => 'Private Key',
      '#default_value' => isset($settings['authentication']['ssh']['private_key']) ? $settings['authentication']['ssh']['private_key'] : '',
      '#rows' => 27,
    ];

    /**
     * @todo support username/passwords
     *
    $form['authentication']['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('Credentials'),
      '#description' => $this->t('Ideally this is a user account that only has access to your local repository.'),
      '#open' => !empty($settings['authentication']['credentials']['username']),
      '#tree' => TRUE,
    ];
    $form['authentication']['credentials']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username to private repository'),
      '#default_value' => isset($settings['authentication']['credentials']['username']) ? $settings['authentication']['credentials']['username'] : '',
    ];
    $form['authentication']['credentials']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password to private repository'),
      '#default_value' => isset($settings['authentication']['credentials']['password']) ? $settings['authentication']['credentials']['password'] : '',
    ];
    */

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
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
    $ezproxy_stanza_settings = [];
    $keys = ['public', 'priv', 'authentication'];
    foreach ($keys as $key) {
      $ezproxy_stanza_settings[$key] = $form_state->getValue($key);
    }
    $old_settings = \Drupal::state()->get('ezproxy_stanza_settings');
    \Drupal::state()->set('ezproxy_stanza_settings', $ezproxy_stanza_settings);

    if (!isset($old_settings['priv']['origin']) || $old_settings['priv']['origin'] !== $ezproxy_stanza_settings['priv']['origin']) {
      $git = new PrivateRepo();
      $git->updateOrigin($ezproxy_stanza_settings['priv']['origin']);
      $git->pullRemote();
    }

    drupal_set_message($this->t('Your changes have been saved.'));

    $form_state->setRedirect('view.ezproxy_stanzas.page_1');
  }
}
