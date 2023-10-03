<?php

namespace SDKAssasWP\Asaas;

if (!defined('ABSPATH'))
{
   exit;
}

class Customer
{
   private $connection;
   private $validate;

   const ERROR_INVALID_CUSTOMER = 'Invalid customer';
   const ERROR_INVALID_EMAIL = 'Invalid email parameter';

   public function __construct()
   {
      $this->connection = new Connection();
      $this->validate   = new Validate();
   }

   public function create($params)
   {
      if (!$this->validate::customer($params))
      {
         throw new \Exception(self::ERROR_INVALID_CUSTOMER);
      }

      try
      {
         $url = '/customers';
         return $this->connection->post($url, $params);
      }
      catch (\Throwable $error)
      {
         throw new \Exception($error->getMessage());
      }
   }

   public function get($id)
   {
      try
      {
         $url = "/customers/{$id}";
         return $this->connection->get($url);
      }
      catch (\Throwable $error)
      {
         throw new \Exception($error->getMessage());
      }
   }

   public function update($id, $params)
   {
      if (!$this->validate::customer($params))
      {
         throw new \Exception(self::ERROR_INVALID_CUSTOMER);
      }

      try
      {
         $url = "/customers/{$id}";
         return $this->connection->post($url, $params);
      }
      catch (\Throwable $error)
      {
         throw new \Exception($error->getMessage());
      }
   }

   public function find($email, $cpfCnpj)
   {
      if (!is_email($email))
      {
         throw new \Exception(self::ERROR_INVALID_EMAIL);
      }

      $cpfCnpj = preg_replace('/[^0-9]/', '', $cpfCnpj);

      try
      {
         $url = "/customers?email={$email}&cpfCnpj={$cpfCnpj}&limit=1";
         return $this->connection->get($url);
      }
      catch (\Throwable $error)
      {
         throw new \Exception($error->getMessage());
      }
   }
}
