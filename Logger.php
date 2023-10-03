<?php

namespace SDKAssasWP\Asaas;

if (!defined('ABSPATH'))
{
   exit;
}

class Logger
{
   private $log_file_path;
   private $log_error_file_path;
   private $timestamp;
   private $daystamp;
   private $asaas_log;
   private $asaas_error_log;

   public function __construct()
   {
      $this->daystamp  = $this->get_datetime('Y-m-d');
      $this->timestamp = $this->get_datetime('Y-m-d H:i:s');

      $this->log_file_path       = __DIR__ . "/logs/logs_{$this->daystamp}.log";
      $this->log_error_file_path = __DIR__ . "/logs/logs_error_{$this->daystamp}.log";

      $this->asaas_log       = defined('ASAAS_LOG') ? ASAAS_LOG : false;
      $this->asaas_error_log = defined('ASAAS_ERROR_LOG')  ? ASAAS_ERROR_LOG : false;

      if ($this->asaas_log === true || $this->asaas_error_log === true)
      {
         $this->create_logs_folder();
      }
   }

   public function log($hook, $message)
   {
      if ($this->asaas_log === true)
      {
         $message = $this->filter_data_log($message);

         $log_message = "[$this->timestamp] $hook $message\r\n\n";

         file_put_contents($this->log_file_path, $log_message, FILE_APPEND);
      }
   }

   public function log_error($hook, $message)
   {
      if ($this->asaas_log === true)
      {
         $message = $this->filter_data_log($message);

         $log_message = "[$this->timestamp] $hook $message\r\n\n";

         file_put_contents($this->log_error_file_path, $log_message, FILE_APPEND);
      }
   }

   private function get_datetime($date_format)
   {
      return function_exists('current_time') ? current_time($date_format) : date($date_format);
   }

   private function create_logs_folder()
   {
      try
      {
         $logs_folder = __DIR__ . '/logs';

         if (!is_dir($logs_folder))
         {
            mkdir($logs_folder, 0755, true);
         }
      }
      catch (\Exception $error)
      {
         throw new \Exception($error->getMessage());
      }
   }

   private function filter_data_log($data)
   {
      if (is_string($data))
      {
         $decoded_data = json_decode($data, true);

         if (json_last_error() === JSON_ERROR_NONE)
         {
            $data = $decoded_data;
         }
         else
         {
            return $data;
         }
      }

      $keys_to_remove = ['creditCard', 'creditCardHolderInfo', 'creditCardToken'];

      $data = $this->recursive_remove_keys($data, $keys_to_remove);

      return wp_json_encode($data);
   }

   private function recursive_remove_keys($array, $keys)
   {
      if (is_array($array))
      {
         foreach ($keys as $key)
         {
            unset($array[$key]);
         }

         foreach ($array as &$value)
         {
            $value = $this->recursive_remove_keys($value, $keys);
         }
      }

      return $array;
   }
}
