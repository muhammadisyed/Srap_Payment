<?php

namespace Drupal\srap_payment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\srap_tweaks\SrapTweaks;
use Drupal\srap_payment\SrapStripe;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PlaceOrderForm extends FormBase {
  const SETTINGS = 'apps.keys.settings';

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
    return 'srap_place_order_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#disable_inline_form_errors'] = TRUE;
    $form['message'] = [
      '#type'   => 'markup',
      '#markup' => '<div class="result_message_order"></div>',
    ];
    $form['cost'] = [
      '#type'     => 'item',
      '#markup'   => '<div class="member_replacement">'.t("Replacement Member Kit").'</div>
      <div class="tax-calculation"></div>',
    ];
    $form['total_amount'] = [
      '#type'          => 'hidden',
      '#default_value' =>  '8.95',
      '#attributes' => array('id' => 'total-amount'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Place Order'),
    ];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('user_id');
    $user_id = $tempstore->get('get_id');
    $token  = $_POST['stripeToken'];
    $tempstore = \Drupal::service('tempstore.private')->get('user_information');
    $user_info = $tempstore->get('user_billing_info');
    $user_shipping_info = $tempstore->get('user_shipping_info');
    $tempstore = \Drupal::service('tempstore.private')->get('user_payment_info');
    $user_payment_info = $tempstore->get('user_payment_info');
    $tempstore = \Drupal::service('tempstore.private')->get('membership_number');
    $membership_number = $tempstore->get('membership_number');
    $user_email = $user_info['email'];
    $member_user = $this->entityTypeManager->getStorage('user')->load($user_id);
    require_once('vendor/autoload.php');
    $config = $this->config(static::SETTINGS);
    $stripe = array(
      'stripe_secret_key'      => $config->get('stripe_secret_key'),
      'stripe_publishable_key' => $config->get('stripe_publishable_key')
    );
    \Stripe\Stripe::setApiKey($stripe['stripe_secret_key']);
    $stripe_customer_id = SrapTweaks::getPaymentInfo($user_id, 'stripe_customer_id');
    if(!$stripe_customer_id) {
      $customer = \Stripe\Customer::create(array(
        'email'   => $user_email,
        'name'    => $user_info['first_name'].' '.$user_info['last_name'],
        'source'  => $token
      ));
      $stripe_customer_id = $customer->id;
      $card_details = [
        'card' => [
          'number'    => $user_payment_info['credit_card_number'],
          'exp_month' => explode('/', $user_payment_info['expire'])[0],
          'exp_year'  => explode('/', $user_payment_info['expire'])[1],
          'cvc'       => $user_payment_info['cvv_code'],
          'name'      => $user_payment_info['card_name']
        ]
      ];
      $stripe = new SrapStripe();
      $stripe_card_token  = $stripe->setCustomerCard($stripe_customer_id, $card_details);
      $customer_info = array(
          'user_id'            => $user_id,
          'stripe_customer_id' => $stripe_customer_id,
          'stripe_card_token'  => $stripe_card_token['card']['id'],
          'payment_type'       => 'full_payment'
        );
      SrapTweaks::updatePaymentInformation($user_id,$customer_info);
    }
    //item information
    $itemPrice = $form_state->getValue('total_amount')*100;
    $currency = "cad";
    $tax_percent = SrapTweaks::checkProvinceTax($user_info['province']);
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
    $tax_rates = \Stripe\TaxRate::all(['limit' => 50]);
    $tax_rate_id = '';
    foreach ($tax_rates->data as $tax_rate) {
      if ($tax_rate->active && $tax_rate->state == $user_info['province']) {
        $tax_rate_id = $tax_rate->id;
        continue;
      }
    }
    $invoiceItem = \Stripe\InvoiceItem::create([
      "customer" 	=> $stripe_customer_id,
      "unit_amount" => $itemPrice,
      "currency" 	=> "cad",
      "description" => "Payment for Member Replacement Kit",
    ]);
    $invoice = \Stripe\Invoice::create([
      'customer' 	=> $stripe_customer_id,
      'default_tax_rates' => [
        $tax_rate_id
      ],
      'metadata' => [
        'user_id' => $user_id,
        'member_kit' => true,
        'recurring' => false
      ]
    ]);
    $invoice->pay();
    if($invoice->paid) {
      //order details
      $request_kit = db_insert('replacement_kit_payment')
        ->fields(array(
          'membership_number' => $membership_number,
          'first_name' =>$user_info['first_name'],
          'last_name' => $user_info['last_name'],
          'email' => $user_info['email'],
          'phone_number' => $user_info['phone_number'],
          'address_line_one' => $user_info['address_line_one'],
          'address_line_two' => $user_info['address_line_two'],
          'city' => $user_info['city'],
          'province' => $user_info['province'],
          'postal_code' => $user_info['postal_code'],
          'amount_paid' => $form_state->getValue('total_amount'),
        ))
        ->execute();
        \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', "members-request-success?id=".$invoice->number.'&receipt_url='.$invoice->hosted_invoice_url);
        $funeral_home_name = '';
        $funeral_home_phone = '';
        if($member_user->get('field_member_funeral_home')->count()) {
          $funeral_home_name = $member_user->get('field_member_funeral_home')->first()->get('entity')->getTarget()->getValue()->get('field_funeral_home_name')->value;
          $funeral_home_phone = $member_user->get('field_member_funeral_home')->first()->get('entity')->getTarget()->getValue()->get('field_phone')->value;
        }
        $message  = "The following member has requested an enrollment kit: <br />";
        $message .= "<br /><strong>Membership No:</strong> $membership_number";
        $message .= "<br /><strong>First Name:</strong> "      . $user_info['first_name'];
        $message .= "<br /><strong>Surname:</strong> "       . $user_info['last_name'];
        $message .= "<br /><strong>Phone Number:</strong> "    . $user_info['phone_number'];
        $message .= "<br /><strong>Address Line 1:</strong> "  . $user_info['address_line_one'];
        $message .= "<br /><strong>Address Line 2:</strong> "  . $user_info['address_line_two'];
        $message .= "<br /><strong>City:</strong> "            . $user_info['city'];
        $message .= "<br /><strong>Province:</strong> "        . $user_info['province'];
        $message .= "<br /><strong>Postal Code:</strong> "     . $user_info['postal_code'];
        $message .= "<br /><strong>Funeral Home:</strong> $funeral_home_name";
        $message .= "<br /><strong>Funeral Home Telephone:</strong> $funeral_home_phone <br />";

        if ($user_shipping_info) {
          $message .= "<br /><strong>Shipping Information:</strong><br />";
          $message .= "<br /><strong>First Name:</strong> "      . $user_shipping_info['shipping_first_name'];
          $message .= "<br /><strong>Surname:</strong> "       . $user_shipping_info['shipping_last_name'];
          $message .= "<br /><strong>Phone Number:</strong> "    . $user_shipping_info['shipping_phone_number'];
          $message .= "<br /><strong>Address Line 1:</strong> "  . $user_shipping_info['shipping_address_line_one'];
          $message .= "<br /><strong>Address Line 2:</strong> "  . $user_shipping_info['shipping_address_line_two'];
          $message .= "<br /><strong>City:</strong> "            . $user_shipping_info['shipping_city'];
          $message .= "<br /><strong>Province:</strong> "        . $user_shipping_info['shipping_province'];
          $message .= "<br /><strong>Postal Code:</strong> "     . $user_shipping_info['shipping_postal_code'];
        }

        $message .= "<br /><br />Online payment has been processed.";
        $user_lang_preference = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $user_info['email']]);
        $user_lang_preference = array_values($user_lang_preference)[0];
        $user_lang_preference = $user_lang_preference->get('field_language_preference')->value;
        SrapTweaks::srap_tweaks_send_mail('srap_member_enrollment_kit_request', t($message), null, $send_to = 'staff');
        if ($user_lang_preference == 'en') {
          SrapTweaks::srap_tweaks_send_mail('srap_member_enrollment_kit_request_confirmation', t('We have received your order and we are processing your request. Your new enrollment kit containing a wallet card and luggage tags should arrive in 4-6 weeks.<br><br>If you have questions about this order or the plan, please contact the funeral establishment or enroller where you purchased your membership.<br><br>Thank you for being a member!'), $user_info['email'], $send_to = 'member');
        } else {
          SrapTweaks::srap_tweaks_send_mail('srap_member_enrollment_kit_request_confirmation', t('Nous avons reçu votre commande et nous traitons votre demande. Votre nouvelle trousse d’adhésion, contenant une carte pour portefeuille et des étiquettes pour bagages, devrait arriver en 4 à 6 semaines.<br><br>
          Si vous avez des questions concernant cette commande ou le programme, veuillez contacter l’entreprise de services funéraires ou l’entreprise autorisée auprès de laquelle vous avez souscrit votre adhésion.<br><br>
          Merci d’être membre!<br><br>'), $user_info['email'], $send_to = 'member', 'fr');
        }
        $tempstore = \Drupal::service('tempstore.private')->get('user_confirmation_info');
        $tempstore->delete('user_confirmation_info');
    } else {
      $statusMsg = "Transaction has been failed";
    }
  }
}
