<?php


namespace DrewM\MailChimp;


/**
 * Class OAuth
 * This class allows one to use the oauth authentication of Mailchimp
 * @package DrewM\MailChimp
 */
class OAuth
{

    /**
     * Get Mailchimp Authentication url
     *
     * @param $client_id
     * @param $redirect_uri
     * @return string
     */
    public static function getAuthUrl($client_id, $redirect_uri){
        $encoded_uri = urldecode($redirect_uri);
        $authUrl = "https://login.mailchimp.com/oauth2/authorize";
        $authUrl .= "?client_id=" . $client_id;
        $authUrl .= "&redirect_uri=" . $encoded_uri;
        $authUrl .= "&response_type=code";
        return $authUrl;
    }

    /**
     * Get a user access token from the code retrieved with getUrl
     *
     * @param $code
     * @param $client_id
     * @param $client_secret
     * @param $redirect_uri
     * @return string
     */
    public static function getAccessToken($code, $client_id, $client_secret, $redirect_uri)
    {
        $encoded_uri = urldecode($redirect_uri);
        $oauth_string = "grant_type=authorization_code";
        $oauth_string .= "&client_id=" . $client_id;
        $oauth_string .= "&client_secret=" . $client_secret;
        $oauth_string .= "&redirect_uri=" . $encoded_uri;
        $oauth_string .= "&code=" . $code;

        return self::exchange($oauth_string);
    }

    /**
     * Internal function that makes call to Mailchimp API to get an access token
     *
     * @param $oauth_string
     * @return string
     * @throws \Exception
     */
    private static function exchange($oauth_string)
    {
        $ch = curl_init('https://login.mailchimp.com/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $oauth_string);
        $return = curl_exec($ch);
        if (!is_null(json_decode($return))) {
            $return = json_decode($return);
        }
        curl_close($ch);
        if (!$return->access_token) {
            throw new \Exception(
                'MailChimp did not return an access token',
                $return
            );
        }
        $headers = array('Authorization: OAuth ' . $return->access_token);
        $ch = curl_init("https://login.mailchimp.com/oauth2/metadata/");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $account = curl_exec($ch);
        if (!is_null(json_decode($account))) {
            $account = json_decode($account);
        }
        curl_close($ch);
        if (!$account->dc) {
            throw new \Exception(
                'Unable to retrieve account meta-data',
                $account
            );
        }
        return $return->access_token . "-" . $account->dc;
    }
}