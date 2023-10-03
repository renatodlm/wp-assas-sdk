<?php

namespace SDKAssasWP\Asaas;

if (!defined('ABSPATH')) exit;

class Connection
{
   protected $api_key;
   protected $base_url;
   protected $api_version;

   const ERROR_REQUEST_FAILED   = 'Request failed: ';
   const ERROR_INTEGRATION_INFO = 'Asaas integration information is not filled in';

   public function __construct()
   {
      if (!empty(ASAAS_APIKEY) && !empty(ASAAS_AMBIENT) && !empty(ASAAS_API_VERSION))
      {
         $this->api_key     = ASAAS_APIKEY;
         $this->base_url    = ASAAS_AMBIENT;
         $this->api_version = ASAAS_API_VERSION;
      }
      else
      {
         throw new \Exception(self::ERROR_INTEGRATION_INFO);
      }
   }

   private function get_api_url()
   {
      return $this->base_url . $this->api_version;
   }

   public function get($url)
   {
      try
      {
         $response = wp_remote_get($this->get_api_url() . $url, [
            'headers' => [
               'Content-Type' => 'application/json',
               'access_token' => $this->api_key
            ]
         ]);

         if (is_wp_error($response))
         {
            throw new \Exception(self::ERROR_REQUEST_FAILED . $response->get_error_message());
         }

         $result = wp_remote_retrieve_body($response);
         return json_decode($result);
      }
      catch (\Throwable $error)
      {
         throw new \Exception($error->getMessage());
      }
   }

   public function post($url, $params)
   {
      try
      {
         $response = wp_remote_post($this->get_api_url() . $url, [
            'headers' => [
               'Content-Type' => 'application/json',
               'access_token' => $this->api_key
            ],
            'body' => json_encode($params)
         ]);

         if (is_wp_error($response))
         {
            throw new \Exception(self::ERROR_REQUEST_FAILED . $response->get_error_message());
         }

         return [
            'code'     => wp_remote_retrieve_response_code($response),
            'response' => wp_remote_retrieve_body($response)
         ];
      }
      catch (\Throwable $error)
      {
         throw new \Exception($error->getMessage());
      }
   }
}
