<?php

namespace SDKAssasWP\Asaas;

if (!defined('ABSPATH'))
{
   exit;
}

class Validate
{
   public static function customer($params)
   {
      return !(empty($params['name']) || empty($params['cpfCnpj']) || empty($params['email']));
   }

   public static function payment_params($params, $subscription = false)
   {
      $required_fields = [
         'customer', 'description', 'billingType', 'value'
      ];

      if ($subscription)
      {
         $subscription_fields = ['nextDueDate', 'cycle'];

         $required_fields = array_merge($required_fields, $subscription_fields);
      }

      foreach ($required_fields as $field)
      {
         if (empty($params[$field]))
         {
            return false;
         }
      }

      if (!is_numeric($params['value']) || $params['value'] <= 0)
      {
         return false;
      }

      if (empty($params['creditCardToken']))
      {
         $credit_card_fields = [
            'holderName', 'number', 'expiryMonth', 'expiryYear', 'ccv'
         ];

         foreach ($credit_card_fields as $field)
         {
            if (empty($params['creditCard'][$field]))
            {
               return false;
            }
         }
      }

      return true;
   }
}
