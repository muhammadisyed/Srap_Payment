<?php

/**
 * @file
 * Contains \Drupal\srap_payment\Controller\PaymentController.
 */
namespace Drupal\srap_payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\srap_tweaks\SrapTweaks;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Subscription;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentControllerSuccess extends ControllerBase {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(AccountInterface $account, EntityTypeManager $entity_type_manager, LanguageManager $language_manager, Connection $database) {
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('database')
    );
  }

  public function content() {
    $order_id = $_GET['id'];
    $receipt_url = $_GET['receipt_url'];
    return [
      '#theme'  => 'success_template',
      '#order_title' => t('Thank you for your order'),
      '#order_number' => t('Order number is: @order_id', ['@order_id' => $order_id]),
      '#confirmation_email' => t('You will receive an email confirmation shortly.'),
      '#order_replacement_button_text' => t('Back to Order a Replacement Member Kit'),
      '#print_receipt' => t('Print Receipt'),
      '#receipt_url'=> $receipt_url,
      '#lang_code' => $this->languageManager->getCurrentLanguage()->getId()
    ];
  }
  public function stripeResponseHandler(Request $request) {
    $stripe_response = json_decode($request->getContent());
    if ($stripe_response->type == 'invoice.payment_succeeded' && $stripe_response->data->object->status == 'paid' && $stripe_response->data->object->metadata->recurring == "false") {
      if ($stripe_response->data->object->metadata->user_id) {
        $member_id = $stripe_response->data->object->metadata->user_id;
        $member = $this->entityTypeManager->getStorage('user')->load($member_id);
        if (!$member) { return new JsonResponse(['message'=>'success']); }
        SrapTweaks::setMembershipNumber($member_id);
        $member->set('field_membership_date', date('Y-m-d', time()));
        $member->save();
        if ($stripe_response->data->object->metadata->member_kit == "false") {
          SrapTweaks::generatePDF($member->id(), $member->get('field_language_preference')->value);
        }
        \Drupal::logger('srap_payment')->notice(t('Full payment processed for member: @id', ['@id'=>$member_id]));
      }
    }
    return new JsonResponse(['message'=>'success']);
  }

  public function stripeRecurringResponseHandler(Request $request) {
    $stripe_response = json_decode($request->getContent());
    if ($stripe_response->type == 'customer.subscription.created' && $stripe_response->data->object->status == 'active') {
      if ($stripe_response->data->object->metadata->user_id && $stripe_response->data->object->metadata->recurring) {
        $member_id = $stripe_response->data->object->metadata->user_id;
        if (self::checkSubscription($member_id, $stripe_response->data->object->customer)) {
          return new JsonResponse(['message'=>'success']);
        }
        if (self::checkSubscription($member_id, $stripe_response->data->object->customer) == 12) {
          $subscription = Subscription::retrieve($stripe_response->data->object->id);
          $subscription->cancel();
          return new JsonResponse(['message'=>'success']);
        }
        self::addSubscription($member_id, $stripe_response->data->object->customer, $stripe_response->data->object->id);
        SrapTweaks::setMembershipNumber($member_id,true);
        $member = $this->entityTypeManager->getStorage('user')->load($member_id);
        if (!$member) { return new JsonResponse(['message'=>'success']); }
        $member->set('field_membership_date', date('Y-m-d', time()));
        $member->save();
        SrapTweaks::generatePDF($member->id(), $member->get('field_language_preference')->value);
        \Drupal::logger('srap_payment')->notice(t('Recurring payment processed for member: @id', ['@id'=>$member_id]));
      }
    }
    return new JsonResponse(['message'=>'success']);
  }
  public function checkSubscription($user_id, $stripe_cus_id) {
    $results = $this->database->select('srap_payment_stripe_subscriptions', 's')
         ->fields('s')
         ->condition('user_id', $user_id, '=')
         ->condition('stripe_customer_id', $stripe_cus_id, '=')
         ->execute();
    $results = $results->fetchAll();
    return count($results);
  }
  public function addSubscription($user_id, $stripe_cus_id, $stripe_sub_id) {
    $data = [
      'user_id' => $user_id,
      'stripe_customer_id' => $stripe_cus_id,
      'stripe_subscription_id' => $stripe_sub_id,
      'created' => date('Y-m-d H:i:s', time())
    ];
    $create_subscription = $this->database->insert('srap_payment_stripe_subscriptions')
                          ->fields($data)
                          ->execute();
    return true;
  }
}
