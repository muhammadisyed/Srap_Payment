<?php

namespace Drupal\srap_payment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AppendCommand;

class PaymentForm extends FormBase {

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
    return 'srap_payment_info_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#disable_inline_form_errors'] = TRUE;
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="result_message_payment collapse"></div>',
    ];
    $form['error'] = [
      '#type' => 'markup',
      '#markup' => '<div class="stripe-errors"></div>',
    ];
    $form['drupal_error'] = [
      '#type' => 'markup',
      '#markup' => '<div class="payment_error_message"></div>',
    ];
    $form['card_name'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Name on Card'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '<div class="card_name alert alert-danger alert-sm"></div></div>',
    ];
    $form['credit_card_number'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Credit Card Number'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '<div class="credit_card_number alert alert-danger alert-sm"></div></div>',
      '#attributes' => [
        'maxlength' => 16
      ]
    ];
    $form['expire'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Expiration - MM/YYYY'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '<div class="expire alert alert-danger alert-sm"></div></div>',
      '#attributes' => [
        'id' => 'edit-card-expiry'
      ]
    ];
    $form['cvv_code'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Security Code - CVV/CVC'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '<div class="cvv_code alert alert-danger alert-sm"></div></div>',
      '#attributes' => [
        'id' => 'edit-cvc-code'
      ]
    ];
    $form['secure'] = [
      '#type'     => 'item',
      '#markup'   => '<div class="lock">'.t("Your privacy is important to us. Your information is safe and secure.").'</div>',
      '#prefix'   => '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">',
      '#suffix'   => '</div>',
    ];
    $form['actions'] = [
      '#type'      => 'button',
      '#value'     => $this->t('Continue'),
      '#weight'    => 2,
      '#ajax'      => [
        'callback' => '::PaymentFormAjaxResponse',
      ],
    ];
    $form['clear'] = [
      '#type'     => 'item',
      '#weight'   => 3,
      '#markup'   => "<div class='clear'>".t('Clear')."</div>
      <div class='required-fields'>* ".t('Required Field')."</div>",
    ];
    return $form;
  }
  public function PaymentFormAjaxResponse(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $check = 0;
    if ($form_state->getValue('card_name') == ""){
      $response->addCommand(new HtmlCommand('.card_name',t('This field is required')));
      $response->addCommand(new InvokeCommand('.form-item-card-name', 'addClass', array('has-error')));
      $check = 1;
    } else {
      $response->addCommand(new HtmlCommand('.card_name',''));
      $response->addCommand(new InvokeCommand('.form-item-card-name', 'removeClass', array('has-error')));
    }
    if ($form_state->getValue('credit_card_number') == ""){
      $response->addCommand(new HtmlCommand('.credit_card_number',t('This field is required')));
      $response->addCommand(new InvokeCommand('.form-item-credit-card-number', 'addClass', array('has-error')));
      $check = 1;
    } else {
      $response->addCommand(new HtmlCommand('.credit_card_number',''));
      $response->addCommand(new InvokeCommand('.form-item-credit-card-number', 'removeClass', array('has-error')));
    }
    if ($form_state->getValue('expire') == ""){
      $response->addCommand(new HtmlCommand('.expire',t('This field is required')));
      $response->addCommand(new InvokeCommand('.form-item-expire', 'addClass', array('has-error')));
      $check = 1;
    } else {
      $response->addCommand(new HtmlCommand('.expire',''));
      $response->addCommand(new InvokeCommand('.form-item-expire', 'removeClass', array('has-error')));
    }
    if ($form_state->getValue('cvv_code') == ""){
      $response->addCommand(new HtmlCommand('.cvv_code',t('This field is required')));
      $response->addCommand(new InvokeCommand('.form-item-cvv-code', 'addClass', array('has-error')));
      $check = 1;
    } else {
      $response->addCommand(new HtmlCommand('.cvv_code',''));
      $response->addCommand(new InvokeCommand('.form-item-cvv-code', 'removeClass', array('has-error')));
    }
    if ($check == 1) {
      $response->addCommand(new HtmlCommand('.payment_error_message', ""));
    }else {
      $tempstore = \Drupal::service('tempstore.private')->get('user_information');
      $user_info = $tempstore->get('user_billing_info');
      $user_payment_info = array(
        'card_name'          => $form_state->getValue('card_name') ,
        'credit_card_number' => $form_state->getValue('credit_card_number'),
        'expire'             => $form_state->getValue('expire'),
        'cvv_code'           => $form_state->getValue('cvv_code'),
      );
      $tempstore = \Drupal::service('tempstore.private')->get('user_payment_info');
      $tempstore->set('user_payment_info', $user_payment_info);
      $response->addCommand(
        new HtmlCommand(
          '.result_message_payment',
          '<div class="message-response">' . t('The results is @result', ['@result' => ($user_info['first_name'])]) . '</div>'
        )
      );
      $response->addCommand(new HtmlCommand('.payment_error_message', ""));
      $response->addCommand(new HtmlCommand('.stripe-errors', ""));
    }
    return $response;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
