<?php

namespace Drupal\ezproxy_stanza\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\ezproxy_stanza\Git\PrivateRepo;
use Drupal\Core\Url;
use Drupal\Core\Link;

class EZProxyStanzaConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ezproxy_stanza_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'ezproxy_stanza/config-ui';

    $form['filter'] = [
      '#type' => 'container',
    ];
    foreach (['status', 'review'] as $filter) {
      $$filter = $form_state->getValue($filter);
      if (is_null($$filter) || $$filter == -1) {
        $$filter = -1;
      }
      elseif (!isset($form['filter']['reset'])) {
        $form['filter']['reset'] = [
          '#type' => 'submit',
          '#value' => $this->t('Reset'),
          '#weight' => 101,
        ];
      }
    }

    $form['filter']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('In config.txt'),
      '#options' => [
        -1 => $this->t('- All -'),
        NODE_PUBLISHED => $this->t('Yes'),
        NODE_NOT_PUBLISHED => $this->t('No'),
      ],
      '#default_value' => $status,
    ];
    $form['filter']['review'] = [
      '#type' => 'select',
      '#title' => $this->t('Needs review'),
      '#options' => [
        -1 => $this->t('- All -'),
        TRUE => $this->t('Yes'),
        FALSE => $this->t('No'),
      ],
      '#default_value' => $review,
    ];

    $form['filter']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#weight' => 100,
    ];

    // check if the repository has had any changes
    $repo = new PrivateRepo();
    if ($repo->hasChanges()) {

      // if changes have been made, show the `git diff` as a fieldset
      $form['diff'] = [
        '#type' => 'fieldset',
        '#title' => t('Pending changes'),
        '#description' => '',
        '#weight' => -100,
      ];

      $repo->diff();
      $lines = explode("\n", $repo->getOutput());
      foreach ($lines as $line) {
        if (strlen($line) > 2) {
          $first_chr = substr($line, 0, 1);
          $second_chr = substr($line, 1, 1);

          if ($first_chr === '-' && $second_chr !== '-') {
            $line = '<span class="deleted">' . $line . '</span>';
          }
          elseif ($first_chr === '+' && $second_chr !== '+') {
            $line = '<span class="added">' . $line . '</span>';
          }
        }
        $form['diff']['#description'] .= $line . '<br>';
      }
    }

    $args = [':type' => 'resource'];
    $sql = "SELECT o.field_ezproxy_order_value AS `order`,
      r.field_ezproxy_review_value AS needs_review,
      n.title, n.nid, n.changed, n.status, u.name
      FROM {node_field_data} n
      INNER JOIN {node_revision} revision ON revision.vid = n.vid
      LEFT JOIN {users_field_data} u ON u.uid = revision_uid
      INNER JOIN {node__field_ezproxy_order} o ON n.nid = o.entity_id AND o.deleted = '0'
      LEFT JOIN {node__field_ezproxy_review} r ON n.nid = r.entity_id AND r.deleted = '0'
      WHERE n.type = :type";

    $filter = FALSE;
    if ($status != -1) {
      $sql .= ' AND n.status = :status';
      $args[':status'] = $status;
      $filter = TRUE;
    }
    if ($review != -1) {
      $sql .= ' AND r.field_ezproxy_review_value = :review';
      $args[':review'] = $review;
      $filter = TRUE;
    }
    $sql .= " ORDER BY `order` ASC, title ASC";

    $form['config'] = [
      '#type' => 'tableselect',
      '#header' => [
        $this->t('Name'),
        $this->t('Last Updated'),
        $this->t('Last Updated By')
      ],
      '#sticky' => TRUE,
    ];
    $result = \Drupal::database()->query($sql, $args);
    $date_formatter = \Drupal::service('date.formatter');
    $form['config']['#options'] = [];
    foreach ($result as $node) {
      $row = &$form['config']['#options'][$node->nid];

      $uri_options = [
        'query' => [
          'destination' =>  Url::fromRoute('ezproxy_stanza.manage')->toString()
        ]
      ];
      $row[] = [
        'class' => 'title',
        'data' => Link::fromTextandUrl($node->title, Url::fromRoute('entity.node.edit_form', ['node' => $node->nid], $uri_options))->toString()
      ];

      $row[] = $date_formatter->formatDiff($node->changed, REQUEST_TIME, [
        'granularity' => 2,
        'return_as_object' => FALSE,
      ]) . ' ' . $this->t('ago');

      // if this node is published // in config.txt
      // set the tableselect checkbox to checked
      if ($node->status) {
        // if there are filters applied we had to perform a $form_state->setRebuild()
        // so we'll have to set #value for checkboxes to be checked since #default_value is ignored on form rebuilds
        // We won't be able to save/deploy the form so setting #value here is fine (setting #value ignored user input)
        // we're just setting #value for looks i.e. to check the boxes next to resources in config.txt
        $index = $filter ? '#value' : '#default_value';
        $form['config'][$index][$node->nid] = $node->nid;
      }

      // if the stanza needs reviewed, add a visual cue
      if (!empty($node->needs_review)) {
        $row['#attributes'] = ['class' => ['messages', 'messages--warning']];
      }

      if (strlen($node->name)) {
        $row[] = $node->name;
      }
      elseif (is_null($node->name)) {
        $row[] = $this->t('Unknown');
      }
      else {
        $row[] = $this->t('OCLC Update');
      }
    }

    if (!$form_state->get('ezproxy_stanza_published')) {
      $form_state->set('ezproxy_stanza_published', $form['config']['#default_value']);
    }

    // if there are no filters applied, add the save/deploy buttons
    if (count($args) === 1) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['save'] = [
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

    drupal_set_message($this->t('If OCLC provided additional instructions for a stanza it will be marked like this.'), 'warning');

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
    $op = $form_state->getValue('op')->__toString();

    if ($op == $this->t('Apply')->__toString()) {
      $form_state->setRebuild();
      drupal_set_message($this->t('You will need to reset all filters before you can save or deploy.'), 'error');
      return;
    }
    elseif ($op == $this->t('Reset')->__toString()) {
      return;
    }

    $previously_published = $form_state->get('ezproxy_stanza_published');
    $published = $unpublished = [];
    foreach ($form_state->getValue('config') as $nid => $value) {
      $status = -1;
      if ($value) {
        if (!in_array($nid, $previously_published)) {
          $node = Node::load($nid);
          $published[] = $node->label();
          $status = NODE_PUBLISHED;
        }
      }
      else {
        if (in_array($nid, $previously_published)) {
          $node = Node::load($nid);
          $unpublished[] = $node->label();
          $status = NODE_NOT_PUBLISHED;
        }
      }

      if ($status !== -1) {
        $node->set('status', $status);
        $node->setNewRevision(TRUE);
        $node->revision_log = $status === NODE_PUBLISHED ? $this->t('Adding to config.txt') : $this->t('Removing from config.txt');
        $node->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        $node->setRevisionUserId(\Drupal::currentUser()->id());
        $node->save();
      }
    }

    $msg = '';
    if (count($published)) {
      drupal_set_message($this->t('Successfully added @titles to config.txt.', ['@titles' => implode(', ', $published)]));
      $msg .= $this->t('Added @titles to config.txt.', ['@titles' => implode(', ', $published)])->__toString();
    }
    if (count($unpublished)) {
      drupal_set_message($this->t('Successfully removed @titles from config.txt.', ['@titles' => implode(', ', $unpublished)]));
      if (count($published)) {
        $msg .= ' ';
      }
      $msg .= $this->t('Removed @titles to config.txt.', ['@titles' => implode(', ', $unpublished)])->__toString();
    }

    $repo = new PrivateRepo();
    $repo->setConfig();

    if ($op === $this->t('Save and deploy')->__toString()) {
      if ($repo->hasChanges()) {
        if (strlen($msg) == '') {
          $msg = $this->t('Update config.txt')->__toString();
        }
        $repo->updateRemote($msg);
        drupal_set_message($this->t('Deployed successfully.'));
      }
      else {
        drupal_set_message($this->t('No changes to deploy.'));
      }
    }
  }
}
