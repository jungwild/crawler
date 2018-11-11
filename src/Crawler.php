<?php
namespace Jungwild;

use Dotenv\Dotenv;

define('RESPONSE_TYPE_DOMCRAWLER', 1);
define('RESPONSE_TYPE_HTML', 2);
define('RESPONSE_TYPE_JSON', 3);

class Crawler {

    public $api;
    public $client;
    public $domcrawler;
    private $dotenv;

    public function __construct()
    {
        $this->dotenv = new Dotenv(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $this->dotenv->load();

        $this->api = new ApiClient(getenv('API_USER'), getenv('API_PASS'), getenv('API_URL'));
        $this->client = new HttpClient();
        $this->domcrawler = new DomCrawler();
    }

    public function info($text) {

        echo $text . "\n";

    }

    public function error($text) {

        echo 'FEHLER => ' . $text . "\n";

    }

    public function seperateStreetNumber($street_number) {

        if ( preg_match('/([^\d]+)\s?(.+)/i', $street_number, $result) )
        {
            if(count($result) >= 3) {
                return [
                    'street' => $result[1],
                    'number' => $result[2]
                ];
            }
        }

        return false;

    }

    public function get($url, $response_type = RESPONSE_TYPE_DOMCRAWLER) {

        $response = $this->client->get($url);
        if($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {

            return $this->responseDomCrawler($response);

        }

        return false;

    }

    public function post($url, $data, $response_type = RESPONSE_TYPE_DOMCRAWLER) {

        $response = $this->client->post($url, $data);
        if($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {

            return $this->response($response, $response_type);

        }

        return false;

        return $this->responseData($reponse);

    }

    private function response($client_response, $response_type) {

        switch ($response_type) {
            case RESPONSE_TYPE_HTML:
                return $this->responseHtml($client_response);
                break;

            case RESPONSE_TYPE_JSON:
                return $this->responseJson($client_response);
                break;

            case RESPONSE_TYPE_DOMCRAWLER:
                return $this->responseDomCrawler($client_response);
                break;

            default:
                return $this->responseDomCrawler($client_response);
                break;
        }

        return false;
    }

    private function responseHtml($response) {

        $html = $response->getBody()->getContents();

        if(!empty($html)) {

            return $html;

        }

        return false;

    }

    private function responseJson($response) {

        $json = $response->getBody()->getContents();

        if(!empty($json)) {

            return json_decode($json, true);

        }

        return false;

    }

    private function responseDomCrawler($response) {

        $html = $response->getBody()->getContents();

        if(!empty($html)) {

            return new DomCrawler($html);

        }

        return false;

    }



}