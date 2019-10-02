<?php

namespace DrewM\MailChimp;

/**
 * An error handler for MailChimp.
 * Help MailChimp error classes, categorization
 * and determinate whether a failure is, eg soft/hard.
 *
 * Example:
 *
 * if ($code & ErrorHandler::ALREADY_EXISTS) {
 *    // return "ok";
 * } elseif ($code & ErrorHandler::SOFT_FAILURE) {
 *    // return "ok";
 * } elseif ($code & (ErrorHandler::NET_TIMEOUT | ErrorHandler::NET_NO_JSON)) {
 *    // return "retry",
 * }
 *
 * @author Raphaël Droz <raphael.droz@gmail.com>
 *
 */
class ErrorHandler
{
    // General return categories: OK or failure (By default, failures are most probably API rejections)
    const OK              = 1 <<  0;
    const FAILURE         = 1 <<  1;

    // "Network" failure "subcategory"
    const NET_FAILURE     = 1 <<  2;

    // Kind of failure "tag". Some network or API errors are judged "soft" or "temporary".
    // It's up to the callee to test SOFT_FAILURE congruence and make use of this information if desired.
    const SOFT_FAILURE    = 1 <<  3;

    // successes
    const INSERTED        = 1 <<  6;
    const UPDATED         = 1 <<  7;
    const DELETED         = 1 <<  8;

    // api rejections
    const INVALID_RES     = 1 << 10; // usually from a programmer error 
    const INVALID_API_KEY = 1 << 11; // idem
    const ALREADY_EXISTS  = 1 << 12;
    const COMPLIANCE      = 1 << 13;
    const FAKE            = 1 << 14;
    const INVALID_DOMAIN  = 1 << 15;
    const CAPPED          = 1 << 16;
    const API_OTHER       = 1 << 17;
    const INVALID_ADDRESS = 1 << 18; // invalid ADDRESS merge field

    // network failures
    const NET_REQ_PROC    = 1 << 20; // Neither permanent nor record-specific error. try again later.
    const NET_TIMEOUT     = 1 << 21; // curl: Operation timed out
    const NET_NO_JSON     = 1 << 22; // non-JSON response
    const NET_UNKNOWN     = 1 << 23;
    const UNKNOWN_ERROR   = 1 << 24;

    /**
     * A couple of shortcuts to directly return a specific error of a given category
     */
    private function ok(int $r, array $a)
    {
        return $this->log($r | self::OK, $a);
    }
    private function fail(int $r, array $a)
    {
        return $this->log($r | self::FAILURE, $a);
    }
    private function netfail(int $r, array $a)
    {
        return $this->fail($r | self::NET_FAILURE, $a);
    }

    /**
     * A list of well-known failures.
     * They are tested in order. Put more restrictive first
     * At the step where these are probed,
     *   response is certainly a *failure*, as such all below return codes must be also be OR-ed with self::FAILURE
     */
    const CODES = [
        '400' => [
            // See https://developer.mailchimp.com/documentation/mailchimp/guides/error-glossary/ for categories
            // 'BadRequest', // Your request could not be processed.
            // 'InvalidAction',
            // 'InvalidResource', // The resource submitted could not be validated.
            // 'JSONParseError',
            ['code'  => self::ALREADY_EXISTS,
             'tests' => [ 'title' => 'Member Exists',
                          'detail' => 'is already a list member']],

            ['code'  => self::COMPLIANCE,
             'tests' => ['title' => 'Member In Compliance State',
                         'detail' => 'is in a compliance state due to unsubscribe, bounce, or compliance review']],

            ['code'  => self::INVALID_API_KEY | self::SOFT_FAILURE,
             'tests' => ['title' => 'API Key Invalid']],

            ['code'  => self::FAKE,
             'tests' => ['title' => 'Invalid Resource',
                         'detail' => 'looks fake or invalid']],

            ['code'  => self::INVALID_ADDRESS,
             'tests' => ['title' => 'Invalid Resource',
                         'detail' => 'Your merge fields were invalid',
                         'errors' => 'Please enter a complete address']],

            ['code'  => self::INVALID_DOMAIN,
             'tests' => ['title' => 'Invalid Resource',
                         'detail' => 'domain portion of the email address is invalid']],

            ['code'  => self::CAPPED,
             'tests' => ['title' => 'Invalid Resource',
                         'detail' => 'has signed up to a lot of lists very recently']],

            ['code'  => self::INVALID_RES | self::SOFT_FAILURE,
             'tests' => ['title' => 'Invalid Resource',
                         'detail' => 'The resource submitted could not be validated']],
        ],
        /*
          '401' => [
              'APIKeyMissing',
              'APIKeyInvalid',
          ],
          '403' => [
              'Forbidden',
              'UserDisabled',
              'WrongDatacenter'
          ],
          '404' => [
              'ResourceNotFound'
          ]
          '405' => [
              'MethodNotAllowed'
          ],
          '414' => [
              'ResourceNestingTooDeep'
          ],
          '422' => [
              'InvalidMethodOverride'
          ],
          '429' => [
              'TooManyRequests'
          ],
          '500' => [
              'InternalServerError'
          ],
          '503' => [
              'ComplianceRelated'
          ]
        */
    ];

    /**
     * Simple categorization, exclusively used for logging.
     */
    const ERROR_CLASSES = [
        self::COMPLIANCE     => 'compliance-state',
        self::FAKE           => 'fake',
        self::INVALID_DOMAIN => 'invalid-domain',
        self::CAPPED         => 'capped'
    ];

    // Unused
    const MC_RETURN_CODES = [ ['code' => -100, 'name' => 'ValidationError'],
                              ['code' =>  -99, 'name' => 'List_RoleEmailMember'],
                              ['code' =>  212, 'name' => 'List_InvalidUnsubMember'],
                              ['code' =>  213, 'name' => 'List_InvalidBounceMember'],
                              ['code' =>  234, 'name' => 'List_ThrottledRecipient'] ];

    protected $logger;

    public function __construct(?\Psr\Log\LoggerInterface $logger)
    {
        if ($logger) {
            $this->logger = $logger;
        } else {
            $this->logger = new class
            {
                public function __call($name, $args)
                {
                    printf('[%s] %s' . PHP_EOL, strtoupper($name), $args[0]);
                }
            };
        }
    }

    /**
     * @param MailChimp $mailchimp     A MailChimp class.
     * @param array $json_response     The MailChimp response.
     * @param string $method           The method used in the request.
     * @param array $loginfo           Additional parameter(s) to pass as logging variables.
     *
     * Example:
     *
     * $response =  $mc->post($url, $some_data);
     * return $errorHandler->errno($mc, $response, 'POST', ['id' => $request_id])
     *
     */
    public function errno(MailChimp $mailchimp, array $json_response, string $method, array $loginfo = [])
    {
        $http_code = $mailchimp->getLastResponse()['headers']['http_code'];
        $body = $mailchimp->getLastResponse()['body'];
        $detail = $json_response['detail'] ?? '';
        // $logargs is just an array of arguments which improve error logging
        $logargs = compact('json_response', 'mailchimp') + $loginfo;

        /**
         * Handles anything from network to webserver failures through content-type problems.
         * Basically any case where no valid JSON is not returned with a 2xx code.
         */
        if (! $json_response) {
            // Not an error, just a response to DELETE
            if ($method === 'DELETE' && $http_code === 204) {
                return $this->ok(self::DELETED, $logargs);
            }

            if (strpos($body, 'An error occurred while processing your request') !== false) {
                return $this->netfail(self::NET_NO_JSON | self::SOFT_FAILURE | self::NET_REQ_PROC, $logargs);
            } elseif ($body) {
                return $this->netfail(self::NET_NO_JSON, $logargs);
            }

            if (strpos($mailchimp->getLastError(), 'Operation timed out') === 0) {
                return $this->netfail(self::NET_TIMEOUT | self::SOFT_FAILURE, $logargs);
            }
            return $this->netfail($mailchimp->getLastError() ? self::NET_FAILURE : self::NET_UNKNOWN, $logargs);
        }

        if (empty($json_response['status'])) {
            return $this->fail(self::UNKNOWN_ERROR, $logargs);
        }

        if (!empty($json_response['email_address'])) {
            if ($method === 'POST') {
                return $this->ok(self::INSERTED, $logargs);
            }
            if ($method === 'PUT') {
                return $this->ok(self::UPDATED, $logargs);
            }
            if ($method === 'PATCH') {
                return $this->ok(self::UPDATED, $logargs);
            }
        }

        /**
         * Attempt to "recognize" a MailChimp API error:
         *        "status" could very well be a "real" MailChimp status, like in "status": "unsubscribed"
         * (and "unsubscribed" != 200)
         * In order to detect an error, response must be:
         * 1. numeric
         * 2. code != 200
         * 3. "detail" key must be present
         */
        $isFailed = is_numeric($json_response['status'])
                  && $json_response['status'] != '200'
                  && isset($json_response['detail']);

        if (! $isFailed) {
            var_dump("No place for success in this codepath. api-wtf.", $json_response);
            return $this->fail(self::UNKNOWN_ERROR, $logargs);
        }

        foreach (self::CODES as $status_code => $errors) {
            // Note self::CODES we define JSON response "status" codes. Anyway it does not seem needed to test for them.
            foreach ($errors as $error) {
                extract($error); // defines $code and $tests
                if (!$tests) {
                    continue;
                }
                $match = true;
                foreach ($tests as $field => $val) {
                    if ($json_response[$field] === $val
                        || ($field === 'detail' && strpos($json_response['detail'], $val) !== false)
                        || ($field === 'errors' && strpos(json_encode($json_response['errors']), $val) !== false)) {
                        // pass
                    } else {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    return $this->fail($code, $logargs);
                }
            }
        }

        return $this->fail(self::API_OTHER, $logargs);
    }

    /**
     * Logging API errors into the database is wrong.
     * It's the role of our logger to perform well enough
     */
    public function log(int $code, array $logargs)
    {
        $logger = $this->logger;
        extract($logargs); // defines $id, $json_response, $mailchimp

        $last_request  = self::obfuscateRequest($mailchimp->getLastRequest());
        $last_response = self::obfuscateRequest($mailchimp->getLastResponse());

        $data = [
            'code'          => $code,
            'method'        => $last_request['method'],
            'substatus'     => $last_request['body']['status'] ?? '',
            'email'         => $last_request['body']['email_address'] ?? '',
            'http_code'     => $last_response['headers']['http_code'],
            'json_response' => json_encode($json_response),
            'mc_error'      => $mailchimp->getLastError(),
        ];

        $prefix = 'mailchimp-api {method}';
        if (!empty($id)) {
            $prefix .= ', id={id}';
            $data['id'] = $id;
        }

        if ($code & self::OK) {
            $logger->debug(self::interpolate($prefix . ': OK', $data));
            return $code;
        }

        if (!empty($id) && !empty($data['email'])) {
            $prefix .= '/{email}';
        }

        // Grab corresponding error class as string.
        $type = array_filter(self::ERROR_CLASSES, function ($string, $return_code) use ($code) {
            return $code & $return_code;
        }, ARRAY_FILTER_USE_BOTH);
        $type = array_shift($type);
        if ($type) {
            $prefix .= ' [{type}]';
            $data['type'] = $type;
        }
        // import `json_response` and request (like `method`) keys to be usable in the logs
        $data += $json_response + $last_request;
        $data['method'] = strtoupper($data['method']);

        if ($code & self::NET_FAILURE) {
            $prefix .= ': http={http_code} network error';
            if ($code & self::SOFT_FAILURE) {
                $prefix .= '. [Temporary] Reason: {mc_error}';
            }
        }
        if ($code & self::NET_NO_JSON) {
            $logger->error(self::interpolate($prefix . ': json={json_response}, body={body}', $data));
        } elseif (($r['status'] ?? null) == '400') {
            $logger->warning(self::interpolate($prefix . ': {substatus} other error: {response}', $data));
        }
        if ($code & self::ALREADY_EXISTS) {
            $data['detail'] = preg_replace('/Use PUT.*/', '', $data['detail']);
            $logger->debug(self::interpolate($prefix . ': {detail}', $data));
            return $code;
        } elseif ($code & self::INVALID_ADDRESS) {
            $logger->warning(self::interpolate($prefix . ': Invalid address: {body}', $data));
            return $code;
        }

        // Dump as much as possible to further improve and predict errors
        if ($code & self::FAILURE) {
            if ($code & self::SOFT_FAILURE) {
                $logger->debug(self::interpolate($prefix . ': soft-fail {code}', $data));
            } else {
                $logger->warning(self::interpolate($prefix . ': status={status},title={title},detail={detail}', $data));
            }
            if (!$type) {
                $logger->debug(self::interpolate('{request} ++ {response} -- {json}', ['request' => $last_request,
                                                                                       'response' => $last_response,
                                                                                       'json' => $json_response]));
            }
        }
        return $code;
    }

    /**
     * Monolog default LineFormatter appends the whole context at the end of the log line.
     * Even if elements are used as message placeholders. This (PSR-3) interpolation function
     * avoids this.
     */
    private static function interpolate(string $message, array $context = [])
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be casted to string
            if (!is_array($val) && !is_object($val) || method_exists($val, '__toString')) {
                $replace['{' . $key . '}'] = $val;
            } else {
                $replace['{' . $key . '}'] = json_encode($val);
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    public static function obfuscateRequest($request)
    {
        if (!isset($request['headers'])) {
            return $request;
        }

        if (isset($request['headers']['request_header'])) {
            $request['headers']['request_header'] = preg_replace(
                '/[0-9a-f]{32}(-us\d+)/',
                str_repeat('*', 32) . '\1',
                $request['headers']['request_header']
            );
        } elseif (is_string($request['headers'])) {
            $request['headers'] = preg_replace(
                '/apikey [0-9a-f]{32}(-us\d+)/',
                'apikey ' . str_repeat('*', 32) . '\1',
                $request['headers']
            );
        }

        return $request;
    }
}
