<?php
/**
 * Created by PhpStorm.
 * User: raphael
 * Date: 11.11.18
 * Time: 13:26
 */

namespace Jungwild;
use GuzzleHttp\Client;

class ApiClient extends Client {

    private $auth_token;

    public function __construct($email, $password, $api_url = 'https://dev.jungwild.io/api/v1/')
    {



        $client = new Client();

        $login = $client->request('POST', $api_url . 'login', [
            'form_params' => [
                'email' => $email,
                'password' => $password
            ]
        ]);

        if($login->getStatusCode() == 200) {
            $data = json_decode($login->getBody()->getContents(), true);
            $this->token = $data['auth']['access_token'];

            parent::__construct([
                'base_uri' => $api_url,
                'http_errors' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->auth_token
                ]
            ]);

        }
        else {
            throw new \Exception('login fehlgeschlagen!');
        }
    }

    public function get($url) {
        $response = $this->request('GET', $url);

        return $this->responseData($response);
    }

    public function post($url, $data) {

        $reponse = $this->request('POST', $url, [
            'form_params' => $data
        ]);

        return $this->responseData($reponse);

    }

    public function responseData($response) {



        return json_decode($response->getBody()->getContents(),true);
    }

}