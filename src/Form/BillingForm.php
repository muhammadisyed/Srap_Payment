<?php

namespace Drupal\srap_payment\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\srap_tweaks\SrapTweaks;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AppendCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
class BillingForm extends FormBase {

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
    $tempstore = \Drupal::service('tempstore.private')->get('user_confirmation_info');
    if ($tempstore->get('user_confirmation_info') == NULL) {
      $response = new RedirectResponse('/en/members-request-search');
      return $response->send();
    }
    return 'srap_payment_billing_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('user_confirmation_info');
    $user_confirmation_info = $tempstore->get('user_confirmation_info');
    $form['#disable_inline_form_errors'] = TRUE;
    $provinces = SrapTweaks::getProvinces();
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="result_message"></div>',
    ];
    $form['drupal_error'] = [
      '#type' => 'markup',
      '#markup' => '<div class="billing_error_message"></div>',
    ];
    $form['province_error'] = [
      '#type' => 'markup',
      '#markup' => '<div class="province_error_message"></div>',
    ];
    $form['first_name'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('First Name'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '<div class="first-name-error alert alert-danger alert-sm"></div></div>',
      '#default_value' => $user_confirmation_info['first_name'],
    ];
    $form['last_name'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Surname'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '<div class="last-name-error alert alert-danger alert-sm"></div></div>',
      '#default_value' => $user_confirmation_info['last_name'],
    ];
    $form['email'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Email Address'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user_confirmation_info['email'],
    ];
    $form['phone_number'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Phone Number'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 phone-number-field">',
      '#suffix'   => '<div class="phone-number-error alert alert-danger alert-sm"></div></div>',
      '#default_value' => $user_confirmation_info['phone_number'],
      '#attributes' => [
          'maxlength' => 12,
          'placeholder' => t('123-456-7890')
      ],
    ];
    $form['address_line_one'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Address Line 1'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '<div class="address-line-one-error alert alert-danger alert-sm"></div></div>',
      '#default_value' => $user_confirmation_info['address_line_one'],
    ];
    $form['address_line_two'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Address Line 2'),
      '#required' => FALSE,
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' => $user_confirmation_info['address_line_two'],
    ];
    $form['city'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('City'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 city">',
      '#suffix'   => '<div class="city-error alert alert-danger alert-sm"></div></div>',
      '#default_value' => $user_confirmation_info['city'],
      '#attributes' => [
        'maxlength' => 30
      ]
    ];
    $form['province'] = [
      '#type'     => 'select',
      '#title'    => $this->t('Province'),
      '#required' => TRUE,
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 province">',
      '#suffix'   => '<div class="province-error alert alert-danger alert-sm"></div></div>',
      '#options'  => $provinces,
      '#empty_option'=>t('Province'),
      '#default_value' => $user_confirmation_info['province'],
    ];
    $form['postal_code'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Postal Code'),
      '#required' => True,
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 postal-code">',
      '#suffix'   => '<div class="postal-code-error alert alert-danger alert-sm"></div></div>',
      '#default_value' => $user_confirmation_info['postal_code'],
      '#attributes' => [
          'maxlength' => 7,
          'placeholder' => t('A1A 1A1')
      ],
    ];
    $form['shipping'] = [
      '#type'     => 'item',
      '#markup'   => "<h4>".t('SHIPPING INFORMATION')."</h4>",
      '#prefix'   => '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">',
      '#suffix'   => '</div>',
    ];
    $form['billing'] = array(
      '#type'     => 'checkbox',
      '#title'    => $this->t('Same as billing information'),
      '#prefix'   => '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">',
      '#suffix'   => '</div>',
      '#default_value' =>TRUE,
    );

    $form['billing_first_name'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('First Name'),
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 billing">',
      '#suffix'   => '<div class="billing-first-name-error alert alert-danger alert-sm"></div></div>',
      '#states' => array(
        'required' => array(
          ':input[name="billing"]' => array('checked' => FALSE),
        ),
      ),
    ];
    $form['billing_last_name'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Surname'),
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 billing">',
      '#suffix'   => '<div class="billing-last-name-error alert alert-danger alert-sm"></div></div>',
      '#states' => array(
        'required' => array(
          ':input[name="billing"]' => array('checked' => FALSE),
        ),
      ),
    ];
    $form['billing_email'] = [
      '#type'     => 'textfield',
      '#required' => TRUE,
      '#title'    => $this->t('Email Address'),
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 billing">',
      '#suffix'   => '</div>',
    ];
    $form['billing_phone_number'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Phone Number'),
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 billing phone-number-field">',
      '#suffix'   => '<div class="billing-phone-number-error alert alert-danger alert-sm"></div></div>',
      '#states' => array(
        'required' => array(
          ':input[name="billing"]' => array('checked' => FALSE),
        ),
      ),
      '#attributes' => [
        'maxlength' => 12,
        'placeholder' => t('123-456-7890')
      ]
    ];
    $form['billing_address_line_one'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Address Line 1'),
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 billing">',
      '#suffix'   => '<div class="billing-line-one-error alert alert-danger alert-sm"></div></div>',
      '#states' => array(
        'required' => array(
          ':input[name="billing"]' => array('checked' => FALSE),
        ),
      ),
    ];
    $form['billing_address_line_two'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Address Line 2'),
      '#prefix'   => '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 billing">',
      '#suffix'   => '</div>',
    ];
    $form['billing_city'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('City'),
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 billing billing-city">',
      '#suffix'   => '<div class="billing-city-error alert alert-danger alert-sm"></div></div>',
      '#states' => array(
        'required' => array(
          ':input[name="billing"]' => array('checked' => FALSE),
        ),
      ),
      '#attributes' => [
        'maxlength' => 30
      ]
    ];
    $form['billing_province'] = [
      '#type'     => 'select',
      '#title'    => $this->t('Province'),
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 billing billing-province">',
      '#suffix'   => '<div class="billing-province-error alert alert-danger alert-sm"></div></div>',
      '#states' => array(
        'required' => array(
          ':input[name="billing"]' => array('checked' => FALSE),
        ),
      ),
      '#options'  => $provinces,
      '#empty_option'=>t('Province'),
    ];
    $form['billing_postal_code'] = [
      '#type'     => 'textfield',
      '#title'    => $this->t('Postal Code'),
      '#prefix'   => '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 billing billing-postal-code">',
      '#suffix'   => '<div class="billing-postal-code-error alert alert-danger alert-sm"></div></div>',
      '#states' => array(
        'required' => array(
          ':input[name="billing"]' => array('checked' => FALSE),
        ),
      ),
      '#attributes' => [
          'maxlength' => 7,
          'placeholder' => t('A1A 1A1')
      ],
    ];
    $form['actions'] = [
      '#type'  => 'button',
      '#value' => $this->t('Continue'),
      '#ajax'  => [
        'callback' => '::BillingFormAjaxResponse',
      ],
    ];
    $form['clear'] = [
      '#type'     => 'item',
      '#markup'   => "<div class='clear'>".t('Clear')."</div>
      <div class='required-fields'>* ".t('Required Field')."</div>",
    ];
    return $form;
  }
  public function BillingFormAjaxResponse(array $form, FormStateInterface $form_state) {
    $province_check = false;
    $response = new AjaxResponse();
    $status_messages = array('#type' => 'status_messages');
    $messages = \Drupal::service('renderer')->renderRoot($status_messages);
    if ($form_state->getValue('billing') == TRUE) {
      $shipping_check = 0;
      if ($form_state->getValue('first_name') == "") {
        $response->addCommand(new HtmlCommand('.first-name-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-first-name', 'addClass', array('has-error')));
        $shipping_check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.first-name-error',''));
        $response->addCommand(new InvokeCommand('.form-item-first-name', 'removeClass', array('has-error')));
      }
      if ($form_state->getValue('last_name') == "") {
        $response->addCommand(new HtmlCommand('.last-name-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-last-name', 'addClass', array('has-error')));
        $shipping_check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.last-name-error',''));
        $response->addCommand(new InvokeCommand('.form-item-last-name', 'removeClass', array('has-error')));
      }
      if ($form_state->getValue('phone_number') == "") {
        $response->addCommand(new HtmlCommand('.phone-number-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-phone-number', 'addClass', array('has-error')));
        $shipping_check = 1;
      } else {
        $regex = "/\d{3}-\d{3}-\d{4}/";
        $phone_number = $form_state->getValue('phone_number');
        if (isset($phone_number) && !empty($phone_number) && !preg_match($regex, $phone_number)) {
          $response->addCommand(new HtmlCommand('.phone-number-error', t('Phone number must be in correct format.')));
          $response->addCommand(new InvokeCommand('.form-item-phone-number', 'addClass', array('has-error')));
          $shipping_check = 1;
        } else {
          $response->addCommand(new HtmlCommand('.phone-number-error', ''));
          $response->addCommand(new InvokeCommand('.form-item-phone-number', 'removeClass', array('has-error')));
        }
      }
      if ($form_state->getValue('address_line_one') == "") {
        $response->addCommand(new HtmlCommand('.address-line-one-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-address-line-one', 'addClass', array('has-error')));
        $shipping_check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.address-line-one-error',''));
        $response->addCommand(new InvokeCommand('.form-item-address-line-one', 'removeClass', array('has-error')));
      }
      if ($form_state->getValue('city') == "") {
        $response->addCommand(new HtmlCommand('.city-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-city', 'addClass', array('has-error')));
        $shipping_check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.city-error',''));
        $response->addCommand(new InvokeCommand('.form-item-city', 'removeClass', array('has-error')));
      }
      if ($form_state->getValue('province') == "") {
        $response->addCommand(new HtmlCommand('.province-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-province', 'addClass', array('has-error')));
        $shipping_check = 1;
      } else {
        $province_check = SrapTweaks::checkProvinceTax($form_state->getValue('province'));
        if(!$province_check) {
          $response->addCommand(new HtmlCommand(
            '.province-error',t('Please enter valid province.')));
          $response->addCommand(new HtmlCommand('.billing_error_message',""));
          $response->addCommand(new InvokeCommand('.form-item-province', 'addClass', array('has-error')));
        } else {
          $response->addCommand(new HtmlCommand('.province-error',''));
          $response->addCommand(new InvokeCommand('.form-item-province', 'removeClass', array('has-error')));
        }
      }
      if ($form_state->getValue('postal_code') == "") {
        $response->addCommand(new HtmlCommand('.postal-code-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-postal-code', 'addClass', array('has-error')));
        $shipping_check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.postal-code-error',''));
        $response->addCommand(new InvokeCommand('.form-item-postal-code', 'removeClass', array('has-error')));
      }
      if (!preg_match('/^[A-Z0-9]{3,3}[\s][A-Z0-9]{3,3}+$/', $form_state->getValue('postal_code'))) {
        $response->addCommand(new HtmlCommand('.postal-code-error', t('Postal code is incorrect.')));
        $response->addCommand(new InvokeCommand('.form-item-postal-code', 'addClass', array('has-error')));
        $shipping_check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.postal-code-error',''));
        $response->addCommand(new InvokeCommand('.form-item-postal-code', 'removeClass', array('has-error')));
      }
      if ($shipping_check == 1) {
        $response->addCommand(new HtmlCommand('.result_message','<div class="response"></div>'));
      }else {
        $province_check = SrapTweaks::checkProvinceTax($form_state->getValue('province'));
        if(!$province_check) {
          $response->addCommand(new HtmlCommand(
            '.province-error',t('Please enter valid province.')));
          $response->addCommand(new HtmlCommand('.billing_error_message',""));
        } else {
          $kit_price = 8.95;
          $tax_percent = $province_check;
          $tax_amount = $kit_price * ($tax_percent / 100);
          $total_amount = $kit_price + $tax_amount;
          if (strlen($total_amount) > 5) { $total_amount = substr($total_amount, 0, -2); }
          if (strlen($tax_amount) > 5) { $tax_amount = substr($tax_amount, 0, -2); }
          $response->addCommand(
            new HtmlCommand(
            '.tax-calculation',
            '<div class="cost">'.t("Cost").'<div>$'.$kit_price.' ('.t("includes shipping and postage").')</div></div>
            <div class="tax_percent">'.t("Tax ").'('.$tax_percent.'%)<div>'."$".$tax_amount.'</div></div>
            <div class="total_amount">'.t("Order Total").'<div class="total">$'.$total_amount.'</div></div>
            <div class="total-kit-price collapse" >'.$total_amount.'</div></div>'
            )
          );
          $response->addCommand(
            new HtmlCommand(
            '.result_message',
            '<div class="response">Successful</div>'
            )
          );
          $response->addCommand(new HtmlCommand('.billing_error_message',""));
          $response->addCommand(new HtmlCommand('.province_error_message',""));
          $response->addCommand(new InvokeCommand('#total-amount','val',[$total_amount]));
        }
      }
    } else {
      $check = 0;
      if ($form_state->getValue('billing_first_name') == "") {
        $response->addCommand(new HtmlCommand('.billing-first-name-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-billing-first-name', 'addClass', array('has-error')));
        $check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.billing-first-name-error',''));
        $response->addCommand(new InvokeCommand('.form-item-billing-first-name', 'removeClass', array('has-error')));
      }
      if ($form_state->getValue('billing_last_name') == "") {
        $response->addCommand(new HtmlCommand('.billing-last-name-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-billing-last-name', 'addClass', array('has-error')));
        $check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.billing-last-name-error',''));
        $response->addCommand(new InvokeCommand('.form-item-billing-last-name', 'removeClass', array('has-error')));
      }
      if ($form_state->getValue('billing_phone_number') == "") {
        $response->addCommand(new HtmlCommand('.billing-phone-number-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-billing-phone-number', 'addClass', array('has-error')));
        $check = 1;
      } else {
        $regex = "/\d{3}-\d{3}-\d{4}/";
        $phone_number = $form_state->getValue('billing_phone_number');
        if (isset($phone_number) && !empty($phone_number) && !preg_match($regex, $phone_number)) {
          $response->addCommand(new HtmlCommand('.billing-phone-number-error', t('Phone number must be in correct format.')));
          $response->addCommand(new InvokeCommand('.form-item-billing-phone-number', 'addClass', array('has-error')));
          $check = 1;
        } else {
          $response->addCommand(new HtmlCommand('.billing-phone-number-error', ''));
          $response->addCommand(new InvokeCommand('.form-item-billing-phone-number', 'removeClass', array('has-error')));
        }
      }
      if ($form_state->getValue('billing_address_line_one') == "") {
        $response->addCommand(new HtmlCommand('.billing-line-one-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-billing-address-line-one', 'addClass', array('has-error')));
        $check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.billing-line-one-error',''));
        $response->addCommand(new InvokeCommand('.form-item-billing-address-line-one', 'removeClass', array('has-error')));
      }
      if ($form_state->getValue('billing_city') == "") {
        $response->addCommand(new HtmlCommand('.billing-city-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-billing-city', 'addClass', array('has-error')));
        $check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.billing-city-error',''));
        $response->addCommand(new InvokeCommand('.form-item-billing-city', 'removeClass', array('has-error')));
      }
      if ($form_state->getValue('billing_province') == "") {
        $response->addCommand(new HtmlCommand('.billing-province-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-billing-province', 'addClass', array('has-error')));
        $check = 1;
      } else {
        $province_check = SrapTweaks::checkProvinceTax($form_state->getValue('billing_province'));
        if(!$province_check) {
          $response->addCommand(new HtmlCommand(
            '.billing-province-error',t('Please enter valid province.')));
          $response->addCommand(new HtmlCommand('.billing_error_message',""));
          $response->addCommand(new InvokeCommand('.form-item-billing-province', 'addClass', array('has-error')));
        } else {
          $response->addCommand(new HtmlCommand('.billing-province-error',''));
          $response->addCommand(new InvokeCommand('.form-item-billing-province', 'removeClass', array('has-error')));
        }
      }
      if ($form_state->getValue('billing_postal_code') == "") {
        $response->addCommand(new HtmlCommand('.billing-postal-code-error', t('This field is required')));
        $response->addCommand(new InvokeCommand('.form-item-billing-postal-code', 'addClass', array('has-error')));
        $check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.billing-postal-code-error',''));
        $response->addCommand(new InvokeCommand('.form-item-billing-postal-code', 'removeClass', array('has-error')));
      }
      if (!preg_match('/^[A-Z0-9]{3,3}[\s][A-Z0-9]{3,3}+$/', $form_state->getValue('billing_postal_code'))) {
        $response->addCommand(new HtmlCommand('.billing-postal-code-error', t('Postal code is incorrect.')));
        $response->addCommand(new InvokeCommand('.form-item-billing-postal-code', 'addClass', array('has-error')));
        $check = 1;
      } else {
        $response->addCommand(new HtmlCommand('.billing-postal-code-error',''));
        $response->addCommand(new InvokeCommand('.form-item-billing-postal-code', 'removeClass', array('has-error')));
      }

      if ($check == 1) {
        $response->addCommand(new HtmlCommand('.result_message','<div class="response"></div>'));
      } else {
        $province_check = SrapTweaks::checkProvinceTax($form_state->getValue('billing_province'));
        if(!$province_check) {
          $response->addCommand(new HtmlCommand(
            '.billing-province-error',t('Please enter valid province.')));
          $response->addCommand(new HtmlCommand('.billing_error_message',""));
        } else {
          $kit_price = 8.95;
          $tax_percent = $province_check;
          $tax_amount = $kit_price * ($tax_percent / 100);
          $total_amount = $kit_price + $tax_amount;
          if (strlen($total_amount) > 5) { $total_amount = substr($total_amount, 0, -2); }
          if (strlen($tax_amount) > 5) { $tax_amount = substr($tax_amount, 0, -2); }
          $response->addCommand(
            new HtmlCommand(
            '.tax-calculation',
            '<div class="cost">'.t("Cost").'<div>$'.$kit_price.' ('.t("includes shipping and postage").')</div></div>
            <div class="tax_percent">'.t("Tax ").'('.$tax_percent.'%)<div>'."$".$tax_amount.'</div></div>
            <div class="total_amount">'.t("Order Total").'<div class="total">$'.$total_amount.'</div></div>
            <div class="total-kit-price collapse" >'.$total_amount.'</div></div>'
            )
          );
          $response->addCommand(
            new HtmlCommand(
            '.result_message',
            '<div class="response">Successful</div>'
            )
          );
          $response->addCommand(new HtmlCommand('.billing_error_message',""));
          $response->addCommand(new HtmlCommand('.province_error_message',""));
          $response->addCommand(new InvokeCommand('#total-amount','val',[$total_amount]));
          $user_info = array(
            'first_name'        => $form_state->getValue('billing_first_name') ,
            'last_name'         => $form_state->getValue('billing_last_name'),
            'email'             => $form_state->getValue('billing_email'),
            'phone_number'      => $form_state->getValue('billing_phone_number'),
            'address_line_one'  => $form_state->getValue('billing_address_line_one'),
            'address_line_two'  => $form_state->getValue('billing_address_line_two'),
            'city'              => $form_state->getValue('billing_city'),
            'province'          => $form_state->getValue('billing_province'),
            'postal_code'       => $form_state->getValue('billing_postal_code')
          );
          $tempstore = \Drupal::service('tempstore.private')->get('user_information');
          $tempstore->set('user_billing_info', $user_info);
        }
      }
    }
    $user_info = array(
      'first_name'        => $form_state->getValue('first_name') ,
      'last_name'         => $form_state->getValue('last_name'),
      'email'             => $form_state->getValue('email'),
      'phone_number'      => $form_state->getValue('phone_number'),
      'address_line_one'  => $form_state->getValue('address_line_one'),
      'address_line_two'  => $form_state->getValue('address_line_two'),
      'city'              => $form_state->getValue('city'),
      'province'          => $form_state->getValue('province'),
      'postal_code'       => $form_state->getValue('postal_code')
    );
    $tempstore = \Drupal::service('tempstore.private')->get('user_information');
    $tempstore->set('user_billing_info', $user_info);
    if ($form_state->getValue('billing') == FALSE) {
      $user_shipping_info = array(
        'shipping_first_name'        => $form_state->getValue('billing_first_name') ,
        'shipping_last_name'         => $form_state->getValue('billing_last_name'),
        'shipping_email'             => $form_state->getValue('billing_email'),
        'shipping_phone_number'      => $form_state->getValue('billing_phone_number'),
        'shipping_address_line_one'  => $form_state->getValue('billing_address_line_one'),
        'shipping_address_line_two'  => $form_state->getValue('billing_address_line_two'),
        'shipping_city'              => $form_state->getValue('billing_city'),
        'shipping_province'          => $form_state->getValue('billing_province'),
        'shipping_postal_code'       => $form_state->getValue('billing_postal_code')
      );
      $tempstore = \Drupal::service('tempstore.private')->get('user_information');
      $tempstore->set('user_shipping_info', $user_shipping_info);
    }
    return $response;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
