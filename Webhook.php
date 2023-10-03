<?php

namespace SDKAssasWP\Asaas;

use SDKAssasWP\Asaas\Customer;
use SDKAssasWP\Asaas\Logger;

if (!defined('ABSPATH'))
{
   exit;
}

class Webhook
{
   private $data;
   private $Logger;
   private $keep_data;
   private $current_user;
   private $customer_id;

   const PREFIX_LOG                  = 'Asaas: ';
   const PAYMENT_CREATED             = 'PAYMENT_CREATED';
   const PAYMENT_UPDATED             = 'PAYMENT_UPDATED';
   const PAYMENT_CONFIRMED           = 'PAYMENT_CONFIRMED';
   const PAYMENT_RECEIVED            = 'PAYMENT_RECEIVED';
   const PAYMENT_OVERDUE             = 'PAYMENT_OVERDUE';
   const PAYMENT_REFUNDED            = 'PAYMENT_REFUNDED';
   const PAYMENT_DELETED             = 'PAYMENT_DELETED';
   const PAYMENT_RESTORED            = 'PAYMENT_RESTORED';
   const PERCENTAGE_CALCULUS_TYPE    = 'PERCENTAGE';
   const FIXED_CALCULUS_TYPE         = 'FIXED';
   const CREDIT_CARD_PAYMENT_TYPE    = 'CREDIT_CARD';
   const ERROR_DOCUMENT_INVALID      = 'User with empty or invalid document';
   const ERROR_CLIENT_NOT_FOUND      = 'No wordpress user matches customer id';
   const ERROR_CUSTOMER_ID_NOT_FOUND = 'Asaas customer id not found';

   public function __construct($data)
   {
      $this->data   = $data;
      $this->Logger = new Logger();
      $this->filter_data_keep();

      if (!empty($this->keep_data['customer']))
      {
         $this->customer_id = $this->keep_data['customer'];
      }
      else
      {
         throw new \Exception(self::ERROR_CUSTOMER_ID_NOT_FOUND);
      }

      $this->current_user = $this->get_user_id_by_customer_id();
   }

   public function process_event()
   {
      switch ($this->data->event)
      {
         case Webhook::PAYMENT_CONFIRMED:
            $this->on_payment_confirmed();
            break;

         case Webhook::PAYMENT_CREATED:
            $this->on_payment_created();
            break;

         case Webhook::PAYMENT_DELETED:
            $this->on_payment_deleted();
            break;

         case Webhook::PAYMENT_OVERDUE:
            $this->on_payment_overdue();
            break;

         case Webhook::PAYMENT_RECEIVED:
            $this->on_payment_received();
            break;

         case Webhook::PAYMENT_REFUNDED:
            $this->on_payment_refunded();
            break;

         case Webhook::PAYMENT_RESTORED:
            $this->on_payment_restored();
            break;

         case Webhook::PAYMENT_UPDATED:
            $this->on_payment_updated();
            break;

         default:
            die(sprintf(esc_html__('Untreated event: %s', 'SDKAssasWP'), $this->data->event));
      }
   }

   private function on_payment_confirmed()
   {
      $this->webhook_process_log('payment_confirmed');
   }

   private function on_payment_created()
   {
      $this->webhook_process_log('payment_created');
   }

   private function on_payment_deleted()
   {
      $this->webhook_process_log('payment_deleted');
   }

   private function on_payment_overdue()
   {
      $this->webhook_process_log('payment_overdue');
   }

   private function on_payment_received()
   {
      $this->webhook_process_log('payment_received');
   }

   private function on_payment_refunded()
   {
      $this->webhook_process_log('payment_refunded');
   }

   private function on_payment_restored()
   {
      $this->webhook_process_log('payment_restored');
   }

   private function on_payment_updated()
   {
      $this->webhook_process_log('payment_updated');
   }

   private function get_user_id_by_customer_id()
   {
      $users = get_users([
         'meta_key'   => 'asaas_customer_id',
         'meta_value' => $this->customer_id,
         'fields'     => 'ID',
         'number'     => 1,
      ]);

      if (!empty($users))
      {
         return $this->remote_customer_validate($users[0]);
      }

      $this->Logger->log_error('WEBHOOK ALERT', self::ERROR_CLIENT_NOT_FOUND);

      return null;
   }

   private function filter_data_keep()
   {
      $keys_to_keep = [
         'customer',
         'subscription',
         'installment',
         'dueDate',
         'originalDueDate',
         'value',
         'netValue',
         'billingType',
         'status',
         'confirmedDate',
         'paymentDate',
         'dateCreated',
         'paymentDate',
         'clientPaymentDate',
         'installmentNumber',
         'creditDate',
         'transactionReceiptUrl',
         'deleted',
         'anticipated',
         'lastInvoiceViewedDate',
         'refunds'
      ];

      if (is_string($this->data))
      {
         $this->data = json_decode($this->data, true);
      }

      $this->recursive_keep_keys($this->data, $keys_to_keep);
   }

   private function recursive_keep_keys($data, $keys_to_keep)
   {
      if (is_array($data) || is_object($data))
      {
         foreach ($data as $key => $value)
         {
            if (in_array($key, $keys_to_keep))
            {
               $this->keep_data[$key] = $value;
            }
            else if (is_array($value) || is_object($value))
            {
               $this->recursive_keep_keys($value, $keys_to_keep);
            }
         }
      }
   }

   private function remote_customer_validate($wp_user)
   {
      $user_id        = $wp_user->ID;
      $Customer       = new Customer();
      $asaas_customer = $Customer->get($this->customer_id);

      if ($this->match_user_to_asaas_data($user_id, $asaas_customer))
      {
         return $wp_user;
      }
      else
      {
         $this->Logger->log('WEBHOOK ALERT', self::ERROR_CLIENT_NOT_FOUND);
         return null;
      }
   }

   private function match_user_to_asaas_data($user_id, $asaas_data)
   {
      if (!is_numeric($user_id))
      {
         return false;
      }

      $wp_user = get_userdata($user_id);

      if ($wp_user)
      {
         $user_email           = $wp_user->user_email;
         $additional_emails    = explode(',', $asaas_data['additionalEmails']);
         $user_document        = get_user_meta($user_id, 'user_document', true);
         $asaas_customer_id    = $asaas_data['customer'];
         $asaas_customer_email = $asaas_data['email'];

         if (($user_email === $asaas_customer_email || in_array($user_email, $additional_emails)))
         {
            if ($user_document !== $asaas_data['cpfCnpj'])
            {
               $this->Logger->log_error('WEBHOOK ALERT', self::ERROR_DOCUMENT_INVALID . "[WPID:{$user_id} != ASAASID: {$asaas_customer_id}]");
            }

            return true;
         }
      }

      return false;
   }

   private function webhook_process_log($event_name)
   {
      $WPID = !empty($this->current_user) ? 'WPID: ' . $this->current_user . ' - ' : '';
      $message = "$event_name [{$WPID}CusomerID:{$this->customer_id}]";
      $this->Logger->log('WEBHOOK RESPONSE', $message);
   }
}
