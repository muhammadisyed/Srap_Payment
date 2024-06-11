<?php

/**
 * @file
 * Contains \Drupal\srap_payment\Controller\PaymentController.
 */
namespace Drupal\srap_payment\Controller;

use Drupal\Core\Controller\ControllerBase;

class PaymentController extends ControllerBase {
  public function content() {
    $form = \Drupal::formBuilder()->getForm('Drupal\srap_payment\Form\BillingForm');
    $payment_form = \Drupal::formBuilder()->getForm('Drupal\srap_payment\Form\PaymentForm');
    $place_order_form = \Drupal::formBuilder()->getForm('Drupal\srap_payment\Form\PlaceOrderForm');
    \Drupal::service('page_cache_kill_switch')->trigger();
    return [
      '#theme'             => 'payment_template',
      '#billing_form'      => $form,
      '#payment_info_form' => $payment_form,
      '#place_order_form'  => $place_order_form,
      '#lang_code'         => \Drupal::languageManager()->getCurrentLanguage()->getId()
    ];
  }

  public function temp() {
    $message = t('User Signed Up');
    $message .= t('Reset Your Password');
    $message .= t('Member Address Change Request');
    $message .= t('Member Subscribed to Newsletter');
    $message .= t('Unverified Users Reminder');
    $message .= t('Member Cancellation Request');
    $message .= t('Your Safe Return Assistance Plan Certificate is Attached');
    $message .= t('New Enroller Agreement Submission');
    $message .= t('Become a Member Submission');
    $message .= t('New Funeral Home/Enroller Supplies Order Received');
    $message .= t('New Order Placed');
    $message .= t('Protection for your Family and Executor Couldn’t be Simpler');
    $message .= t('Profile Updated');
    $message .= t('Member Enrollment Kit Request');
    $message .= t('Your New Enrollment Kit Is on Its Way!');
    $message .= t('Member Unsubscribed from Newsletter');
    $message .= t('New Prospective Safe Return Member');
    $message .= t('Account Created for You at SRAP – Safe Return Assistance Plan Inc.');
    $build = [
      '#type'   => 'markup',
      '#markup' => $message,
    ];
    return $build;
  }
}
