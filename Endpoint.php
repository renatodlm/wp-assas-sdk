<?php

namespace SDKAssasWP\Asaas;

use SDKAssasWP\Asaas\Logger;

if (!defined('ABSPATH'))
{
   exit;
}

class Endpoint
{
   private $query_var    = 'asaas-webhook';
   private $url_endpoint = 'asaas-webhook';
   protected $query;
   private $Logger;
   protected static $instance = null;
   private $webhook_access_token;

   const CREDIT_CARD                  = 'CREDIT_CARD';
   const ERROR_TOKEN_EMPTY            = 'Access token is empty.';
   const ERROR_TOKEN_INVALID          = 'Invalid Token';
   const ERROR_DATA_EMPTY             = 'Data is empty.';
   const ERROR_CONTENT_TYPE_NOT_ALLOW = 'Content-Type not accepted';

   public function __construct()
   {
      if (!empty(ASAAS_WEBHOOK_ACCESS_TOKEN))
      {
         $this->webhook_access_token = ASAAS_WEBHOOK_ACCESS_TOKEN;
      }
      else
      {
         throw new \Exception(self::ERROR_TOKEN_EMPTY);
      }

      $this->Logger = new Logger();
      $this->query  = "index.php?{$this->query_var}=1";

      add_action('template_redirect', [$this, 'process_webhook']);
      add_filter('query_vars', [$this, 'query_vars']);

      $this->custom_rewrite_basic();
   }

   public function custom_rewrite_basic()
   {
      add_rewrite_rule("{$this->url_endpoint}/?$", $this->query, 'top');
      flush_rewrite_rules();
   }

   public function query_vars($qvars)
   {
      $qvars[] = $this->query_var;
      return $qvars;
   }

   public function process_webhook()
   {
      if ('1' === get_query_var($this->query_var))
      {
         try
         {
            $raw_data = file_get_contents('php://input');
            $this->validate_data($raw_data);
            $data = json_decode($raw_data);

            $this->validate_event($data->event);
            $this->validate_billing_type($data->payment->billingType);

            $this->validate_token();
            $this->validate_content();

            $this->Logger->log('WEBHOOK REQUEST ', $raw_data);

            $webhook = new Webhook($data);
            $webhook->process_event();

            $this->response(200, 'Webhook has been processed with success');
         }
         catch (\Exception $error)
         {
            $this->response(500, $error->getMessage());
         }
      }
   }

   private function validate_data($data)
   {
      if (empty($data))
      {
         throw new \Exception(self::ERROR_DATA_EMPTY);
      }
   }

   private function validate_event($event)
   {
      $accepted_events = [
         Webhook::PAYMENT_CONFIRMED,
         Webhook::PAYMENT_CREATED,
         Webhook::PAYMENT_DELETED,
         Webhook::PAYMENT_OVERDUE,
         Webhook::PAYMENT_RECEIVED,
         Webhook::PAYMENT_REFUNDED,
         Webhook::PAYMENT_RESTORED,
         Webhook::PAYMENT_UPDATED,
      ];

      if (false === array_search($event, $accepted_events, true))
      {
         throw new \Exception(sprintf(__('Event %s wasn\'t registered.', 'SDKAssasWP'), $event));
      }
   }

   private function validate_token()
   {
      $access_token = isset($_SERVER['HTTP_ASAAS_ACCESS_TOKEN']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ASAAS_ACCESS_TOKEN'])) : '';

      if (empty($access_token))
      {
         $query_string = $_SERVER['QUERY_STRING'];
         parse_str($query_string, $query_params);

         $access_token = $query_params['HTTP_ASAAS_ACCESS_TOKEN'];
      }

      if ($this->webhook_access_token !== $access_token && html_entity_decode($this->webhook_access_token) !== $access_token)
      {
         throw new \Exception(self::ERROR_TOKEN_INVALID);
      }
   }

   private function validate_content()
   {
      $content_type = isset($_SERVER['CONTENT_TYPE']) ? sanitize_text_field(wp_unslash($_SERVER['CONTENT_TYPE'])) : '';
      if ('application/json' !== $content_type)
      {
         throw new \Exception(self::ERROR_CONTENT_TYPE_NOT_ALLOW);
      }
   }

   public function get_url()
   {
      $query = $this->query;
      if ('' !== get_option('permalink_structure', ''))
      {
         $query = $this->url_endpoint;
      }

      return home_url('/' . $query);
   }

   protected function response($code, $message)
   {
      if ($code >= 400)
      {
         $this->Logger->log_error('WEBHOOK RESPONSE ' . $code, $message);
      }
      else
      {
         $this->Logger->log('WEBHOOK RESPONSE ' . $code, $message);
      }

      status_header($code);
      die(wp_kses($message, []));
   }

   private function validate_billing_type($billing_type)
   {
      $accepted_billing_type = [self::CREDIT_CARD];
      if (false === array_search($billing_type, $accepted_billing_type, true))
      {
         throw new \Exception(sprintf(__('Billing type %s wasn\'t registered.', 'SDKAssasWP'), $billing_type));
      }
   }
}
