<?php
/**
 * Created by PhpStorm.
 * User: raphael
 * Date: 11.11.18
 * Time: 13:26
 */

namespace Jungwild;
use GuzzleHttp\Client;

class HttpClient extends Client {

    private $auth_token;

    public function __construct()
    {
        parent::__construct([
            'http_errors' => false
        ]);

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

        return $response;
    }

}