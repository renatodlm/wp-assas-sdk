<?php

namespace SDKAssasWP\Asaas;

if (!defined('ABSPATH'))
{
   exit;
}

class Payment
{
   private $connection;
   private $validate;

   const ERROR_ID_REQUIRED    = 'ID is required';
   const ERROR_INVALID_PARAMS = 'Invalid subscription params';

   public function __construct()
   {
      $this->connection = new Connection();
      $this->validate   = new Validate();
   }

   public function create($params)
   {
      if (!$this->validate::payment_params($params))
      {
         throw new \Exception(self::ERROR_INVALID_PARAMS);
      }

      try
      {
         $url = '/payments';
         return $this->connection->post($url, $params);
      }
      catch (\Throwable $error)
      {
         throw new \Exception($error->getMessage());
      }
   }

   public function get($id)
   {
      if (empty($id))
      {
         throw new \Exception(self::ERROR_ID_REQUIRED);
      }

      try
      {
         $url = "/payments/{$id}";
         return $this->connection->get($url);
      }
      catch (\Throwable $error)
      {
         throw new \Exception($error->getMessage());
      }
   }
}
