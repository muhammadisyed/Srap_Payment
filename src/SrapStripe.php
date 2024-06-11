<?php

namespace Drupal\srap_payment;

use Drupal\srap_tweaks\SrapTweaks;
use Drupal\user\Entity\User;
use Stripe\Card;
use Stripe\Token;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Invoice;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\InvoiceItem;
use Stripe\TaxRate;
use Stripe\Plan;

class SrapStripe {
  public function __construct() {
    Stripe::setApiKey(\Drupal::config('apps.keys.settings')->get('stripe_secret_key'));
  }
  public function getStripeCustomerId($user_id) {
    $stripe_customer_id = SrapTweaks::getPaymentInfo($user_id, 'stripe_customer_id');
    $member = User::load($user_id);
    $name = $member->get('field_first_name')->getValue()[0]['value']." ".$member->get('field_last_name')->getValue()[0]['value'];
    $email = $member->getEmail();
    if (!$stripe_customer_id) {
      $customer_data = [
        'name' => $name,
        'email'=> $email
      ];
      $customer = Customer::create($customer_data);
      $stripe_customer_id = $customer->id;
    }
    return $stripe_customer_id;
  }
  public function setCustomerCard($customer_id, $card_details = []) {
    $card_token = Token::create($card_details);
    Customer::createSource($customer_id, ['source' => $card_token['id']]);
    $customer = Customer::retrieve($customer_id);
    $customer->default_source = $card_token['card']['id'];
    $customer->save();
    return $card_token;
  }
  public function makePayment($user_id, $customer_id, $amount, $tax_percent, $province, $payment_type, $description = null) {
    if (!$description) {
      $description = "Invoice for full payment against Membership Fee";
    }
    $tax_rate_id = self::getTaxRateId($province);
    if ($payment_type == 'full_payment') {
      $invoiceItem = InvoiceItem::create([
        "customer" 	=> $customer_id,
        "price"			=> self::getPlanId("Full Payment One-time")
      ]);
      $invoice = Invoice::create([
        'customer' 	=> $customer_id,
        'default_tax_rates' => [
          $tax_rate_id
        ],
        'metadata' => [
          'user_id' => $user_id,
          'member_kit' => false,
          'recurring' => false
        ]
      ]);
      return $invoice->pay($invoice->id);
    }
    $subscription = Subscription::create([
     'customer'  => $customer_id,
     'items' => [
        ['price' => self::getPlanId("Recurring Payment", 'recurring')],
      ],
      'default_tax_rates' => [
        $tax_rate_id
      ],
      'metadata'    => ['user_id'=>$user_id, 'member_kit'=>false, 'recurring' => true]
    ]);
    return $subscription['id'];
  }
  public function generateInvoice($customer_id, $tax_percent, $invoiceItems = []) {
    if (isset($invoiceItems['enrollment_forms_quantity']) && $invoiceItems['enrollment_forms_quantity'] != NULL) {
      \Drupal::logger('srap_enroller_dashboard')->info('Price for enrollnment_forms is: '.$this->getPrice($invoiceItems['brochures_quantity'], 'enrollnment_forms'));
      InvoiceItem::create([
        "customer"  => $customer_id,
        "amount"    => $this->getPrice($invoiceItems['enrollment_forms_quantity'], 'enrollnment_forms'),
        "currency"  => "cad",
        "description" => "Invoice for ".$invoiceItems['enrollment_forms_quantity']." Enrollment Forms"
      ]);
    }
    if (isset($invoiceItems['brochures_quantity']) && $invoiceItems['brochures_quantity'] != NULL) {
      \Drupal::logger('srap_enroller_dashboard')->info('Price for brochures is: '.$this->getPrice($invoiceItems['brochures_quantity'], 'brochures'));
      InvoiceItem::create([
        "customer"  => $customer_id,
        "amount"    => $this->getPrice($invoiceItems['brochures_quantity'], 'brochures'),
        "currency"  => "cad",
        "description" => "Invoice for ".$invoiceItems['brochures_quantity']." Brochures",
        "tax_percent" => $tax_percent
      ]);
    }
    $invoice = Invoice::create([
      'customer'  => $customer_id,
      'billing'   => 'send_invoice',
      'days_until_due'  => 30
    ]);
    return $invoice;
  }
  public function sendInvoice($invoice_id) {
    $invoice = Invoice::retrieve($invoice_id);
    $invoice->sendInvoice();
    return true;
  }
  public function getPrice($quantity, $type) {
    $prices = [
      'enrollnment_forms' => [
        '10' => 00.00,
        '25' => 00.00,
        '50' => 00.00
      ],
      'brochures' => [
        '50'  => 17.95,
        '100' => 34.00,
        '250' => 76.75,
        '500' => 145.00
      ]
    ];
    return $prices[$type][$quantity] * 100;
  }
  public function getTaxRateId($province) {
    $provinces = [
      "YT" => "Yukon",
      "QC" => "Quebec",
      "AB" => "Alberta",
      "NU" => "Nunavut",
      "MB" => "Manitoba",
      "BC" => "British Columbia",
      "SK" => "Saskatchewan",
      "NT" => "Northwest Territories",
      "ON" => "Ontario",
      "NS" => "Nova Scotia",
      "NB" => "New Brunswick",
      "PE" => "Prince Edward Island",
      "NL" => "Newfoundland and Labrador",
    ];
    $tax_rates = TaxRate::all(['limit' => 50]);
    foreach ($tax_rates->data as $tax_rate) {
      if ($tax_rate->active && $tax_rate->state == $province) {
        return $tax_rate->id;
      }
    }
    return false;
  }
  public function getPlanId($description, $type = 'full') {
    global $base_url;
    if ($type == 'full' && $base_url == 'https://live-srap.pantheonsite.io') {
      return 'price_1GsstcKTvg6fMszSaBhCmSC3';
    }
    if ($type == 'full' && $base_url != 'https://live-srap.pantheonsite.io') {
      return 'price_1IsN42KTvg6fMszSpNHoDSG1';
    }
    $all_plans = Plan::all(['limit' => 100]);
    foreach ($all_plans->data as $stripe_plan) {
      if ($stripe_plan->active && $stripe_plan->nickname == $description) {
        return $stripe_plan->id;
      }
    }
    return false;
  }
}
