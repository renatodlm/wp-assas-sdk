<?php

namespace SDKAssasWP\Asaas;

use SDKAssasWP\Asaas\Subscription;
use SDKAssasWP\Asaas\Customer;
use SDKAssasWP\Asaas\Payment;

if (!defined('ABSPATH'))
{
   exit;
}

class Asaas
{
   public $subscription;
   public $customer;
   public $payment;

   public function __construct()
   {
      $this->subscription = new Subscription;
      $this->customer     = new Customer;
      $this->payment      = new Payment;
   }
}
