<?php

namespace Drupal\srap_payment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\srap_tweaks\SrapTweaks;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ConfirmationForm extends FormBase {

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(Messenger $messenger, AccountInterface $account, EntityTypeManager $entity_type_manager) {
    $this->messenger = $messenger;
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'srap_match_found_confirmation_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('user_id');
    $user_id = $tempstore->get('get_id');
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    $provinces = SrapTweaks::getProvinces();
    $form['first_name'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('First Name'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user->get('field_first_name')->value,
    ];
    $form['last_name'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Surname'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user->get('field_last_name')->value,
    ];
    $form['email'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Email Address'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user->getEmail(),
    ];
    $form['phone_number'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Phone Number'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 phone-number-field">',
      '#suffix'   => '</div>',
      '#default_value' => $user->get('field_phone')->value,
      '#attributes' => [
        'maxlength' => 12,
        'placeholder' => t('123-456-7890')
      ]
    ];
    $form['address_line_one'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Address Line 1'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user->get('field_address_line_1')->value,
    ];
    $form['address_line_two'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Address Line 2'),
      '#required' => FALSE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user->get('field_address_line_2')->value,
    ];
    $form['city'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('City'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user->get('field_city')->value,
      '#attributes' => [
        'maxlength' => 30
      ]
    ];
    $form['province'] = [
      '#type'     => 'select',
      '#title'    => $this->t('Province'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">',
      '#suffix'   => '</div>',
      '#options'  => $provinces,
      '#empty_option'=>t('Province'),
      '#default_value' => $user->get('field_province')->value,
    ];
    $form['postal_code'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Postal Code'),
      '#required' => True,
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user->get('field_postal_code')->value,
      '#attributes' => [
        'maxlength' => 7,
        'minlength' => 7,
        'placeholder' => t('A1A 1A1')
      ],
    ];
    $form['actions'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Confirm and Continue to Secure Checkout'),
      '#prefix'   => '<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">',
      '#suffix'   => '</div>',
    ];
    $form['clear'] = [
      '#type'     => 'item',
      '#markup'   => "<div class='clear'>".t('Clear')."</div>",
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">',
      '#suffix'   => '</div>',
    ];
    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $phone_number = $form_state->getValue('phone_number');
    $postal_code = $form_state->getValue('postal_code');
    if (isset($phone_number) && !empty($phone_number) && !preg_match('/\d{3}-\d{3}-\d{4}/', $phone_number)) {
      $form_state->setErrorByName('phone_number', t('Phone number must be in correct format.'));
    }
    if (!preg_match('/^[A-Z0-9]{3,3}[\s][A-Z0-9]{3,3}+$/', $postal_code)) {
      $form_state->setErrorByName('postal_code', t('Postal code is incorrect.'));
    }
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $first_name        = $form_state->getValue('first_name');
    $last_name         = $form_state->getValue('last_name');
    $email             = $form_state->getValue('email');
    $phone_number      = $form_state->getValue('phone_number');
    $address_line_one  = $form_state->getValue('address_line_one');
    $address_line_two  = $form_state->getValue('address_line_two');
    $city              = $form_state->getValue('city');
    $province          = $form_state->getValue('province');
    $postal_code       = $form_state->getValue('postal_code');
    $confirmation_info = array(
      'first_name'       => $first_name,
      'last_name'        => $last_name,
      'email'            => $email,
      'phone_number'     => $phone_number,
      'address_line_one' => $address_line_one,
      'address_line_two' => $address_line_two,
      'city'             => $city,
      'province'         => $province,
      'postal_code'      => $postal_code
    );
    $tempstore = \Drupal::service('tempstore.private')->get('user_confirmation_info');
    $tempstore->delete('user_confirmation_info');
    $user_id = $_GET['id'];
    $user_data = $this->entityTypeManager->getStorage('user')->load($user_id);
    $user_data->set('field_phone', $phone_number);
    $user_data->set('field_address_line_1', $address_line_one);
    $user_data->set('field_address_line_2', $address_line_two);
    $user_data->set('field_city', $city);
    $user_data->set('field_province', $province);
    $user_data->set('field_postal_code', $postal_code);
    $user_data->save();
    $tempstore = \Drupal::service('tempstore.private')->get('user_confirmation_info');
    $tempstore->set('user_confirmation_info', $confirmation_info);
    $response = new RedirectResponse("members-request");
    $response->send();
  }
}
