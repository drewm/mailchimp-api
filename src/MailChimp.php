<?php

namespace DrewM\MailChimp;

/**
 * Super-simple, minimum abstraction MailChimp API v3 wrapper
 *
 * Uses curl if available, falls back to file_get_contents and HTTP stream.
 * This probably has more comments than code.
 *
 * @author Drew McLellan <drew.mclellan@gmail.com>
 * @version 2.0.5
 */
class MailChimp
{
    private $api_key;
    private $api_endpoint  = 'https://<dc>.api.mailchimp.com/3.0';
    
    /*  SSL Verification
        Read before disabling: 
        http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/
    */
    public  $verify_ssl    = true; 

    private $last_error    = false;
    private $last_response = array();

    /**
     * Create a new instance
     * @param string $api_key Your MailChimp API key
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        list(, $datacentre) = explode('-', $this->api_key);
        $this->api_endpoint = str_replace('<dc>', $datacentre, $this->api_endpoint);
    }

    /**
     * Convert an email address into a 'subscriber hash' for identifying the subscriber in a method URL
     * @param   string  $email  The subscriber's email address
     * @return  string          Hashed version of the input
     */
    public function subscriberHash($email)
    {
        return md5(strtolower($email));
    }

    /**
     * Get the last error returned by either the network transport, or by the API.
     * If something didn't work, this should contain the string describing the problem.
     * @return  Message describing the error
     */
    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * Get an array containing the HTTP headers and the body of the API response.
     * @return array  Assoc array with keys 'headers' and 'body'
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }
    
    public function delete($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    public function get($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    public function patch($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    public function post($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    public function put($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }

    /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $http_verb   The HTTP verb to use: get, post, put, patch, delete
     * @param  string $method       The API method to be called
     * @param  array  $args         Assoc array of parameters to be passed
     * @return array                Assoc array of decoded result
     */
    private function makeRequest($http_verb, $method, $args=array(), $timeout=10)
    {
        $url = $this->api_endpoint.'/'.$method;

        $json_data = json_encode($args);

        $this->last_error = false;
        $this->last_response = array('headers'=>null, 'body'=>null);

        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/vnd.api+json',
                                                        'Content-Type: application/vnd.api+json',
                                                        'Authorization: apikey '.$this->api_key));
            curl_setopt($ch, CURLOPT_USERAGENT, 'DrewM/MailChimp-API/3.0 (github.com/drewm/mailchimp-api)');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($ch, CURLOPT_ENCODING, '');

            switch($http_verb) {
                case 'post':
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data); 
                    break;

                case 'get':
                    $query = http_build_query($args);
                    curl_setopt($ch, CURLOPT_URL, $url.'?'.$query);
                    break;

                case 'delete':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;

                case 'patch':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data); 
                    break;
                
                case 'put':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data); 
                    break;
            }


            $this->last_response['body']    = curl_exec($ch);
            $this->last_response['headers'] = curl_getinfo($ch);
            
            if (!$this->last_response['body']) {
                $this->last_error = curl_error($ch);
            }
            
            curl_close($ch);
        } else {
            throw new \Exception("cURL support is required, but can't be found.");
        }

        if ($this->last_response['body']) {

            $d = json_decode($this->last_response['body'], true);
            
            if (isset($d['status']) && $d['status']!='200' && isset($d['detail'])) {
                $this->last_error = sprintf('%d: %s', $d['status'], $d['detail']);
            }
            
            return $d;
        }

        return false;
    }
}
