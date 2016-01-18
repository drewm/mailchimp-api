<?php

namespace DrewM\MailChimp;

/**
 * Super-simple, minimum abstraction MailChimp API v3 wrapper
 * MailChimp API v3: http://developer.mailchimp.com
 * This wrapper: https://github.com/drewm/mailchimp-api
 *
 * @author Drew McLellan <drew.mclellan@gmail.com>
 * @version 2.0.8
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

    private $last_error    = '';
    private $last_response = array();

    /**
     * Create a new instance
     * @param string $api_key Your MailChimp API key
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;

        list(, $datacentre)  = explode('-', $this->api_key);
        $this->api_endpoint  = str_replace('<dc>', $datacentre, $this->api_endpoint);

        $this->last_response = array('headers'=>null, 'body'=>null);
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
     * @return  array|false  describing the error
     */
    public function getLastError()
    {
        if ($this->last_error) return $this->last_error;
        return false;
    }

    /**
     * Get an array containing the HTTP headers and the body of the API response.
     * @return array  Assoc array with keys 'headers' and 'body'
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }
    
    /**
     * Make an HTTP DELETE request - for deleting data
     * @param   string        URL of the API request method
     * @param   array         Assoc array of arguments (if any)
     * @param   int           Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function delete($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    /**
     * Make an HTTP GET request - for retrieving data
     * @param   string        URL of the API request method
     * @param   array         Assoc array of arguments (usually your data)
     * @param   int           Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function get($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    /**
     * Make an HTTP PATCH request - for performing partial updates
     * @param   string        URL of the API request method
     * @param   array         Assoc array of arguments (usually your data)
     * @param   int           Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function patch($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    /**
     * Make an HTTP POST request - for creating and updating items
     * @param   string        URL of the API request method
     * @param   array         Assoc array of arguments (usually your data)
     * @param   int           Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function post($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    /**
     * Make an HTTP PUT request - for creating new items
     * @param   string        URL of the API request method
     * @param   array         Assoc array of arguments (usually your data)
     * @param   int           Timeout limit for request in seconds
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function put($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }

    /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $http_verb   The HTTP verb to use: get, post, put, patch, delete
     * @param  string $method       The API method to be called
     * @param  array  $args         Assoc array of parameters to be passed
     * @return array|false          Assoc array of decoded result
     */
    private function makeRequest($http_verb, $method, $args=array(), $timeout=10)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception("cURL support is required, but can't be found.");
        }

        $url = $this->api_endpoint.'/'.$method;

        $this->last_error    = '';
        $response            = array('headers'=>null, 'body'=>null);
        $this->last_response = $response;

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
                $this->attachRequestPayload($ch, $args);
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
                $this->attachRequestPayload($ch, $args);
                break;
            
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestPayload($ch, $args);
                break;
        }

        $response['body']    = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);
        
        if ($response['body'] === false) {
            $this->last_error = curl_error($ch);
        }
        
        curl_close($ch);

        return $this->formatResponse($response);
    }

    /**
     * Encode the data and attach it to the request
     * @param   resource    cURL session handle, used by reference
     * @param   array       Assoc array of data to attach
     */
    private function attachRequestPayload(&$ch, $data)
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
    }

    private function formatResponse($response)
    {
        $this->last_response = $response;

        if (!empty($response['body'])) {

            $d = json_decode($response['body'], true);
            
            if (isset($d['status']) && $d['status']!='200' && isset($d['detail'])) {
                $this->last_error = sprintf('%d: %s', $d['status'], $d['detail']);
            }
            
            return $d;
        }

        return false;
    }
}
