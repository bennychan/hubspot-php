<?php

namespace SevenShores\Hubspot\Http;

use GuzzleHttp\Client as GuzzleClient;
use SevenShores\Hubspot\Exceptions\BadRequest;
use SevenShores\Hubspot\Exceptions\InvalidArgument;

class Client
{
    /** @var string */
    public $key;

    /** @var bool */
    public $oauth;

    /** @var int */
    public $userId;

    /** @var \GuzzleHttp\Client */
    public $client;

    /** @var string */
    private $user_agent = "SevenShores_Hubspot_PHP/1.0.0-rc.1 (https://github.com/ryanwinchester/hubspot-php)";

    /**
     * Make it, baby.
     *
     * @param  array        $config Configuration array
     * @param  GuzzleClient $client The Http Client (Defaults to Guzzle)
     * @throws \SevenShores\Hubspot\Exceptions\InvalidArgument
     */
    function __construct($config = [], $client = null)
    {
        $this->key = isset($config["key"]) ? $config["key"] : getenv("HUBSPOT_SECRET");
        if (empty($this->key)) {
            throw new InvalidArgument("You must provide a Hubspot api key or token.");
        }

        if (isset($config['userId'])) {
            $this->userId = $config['userId'];
        }

        $this->oauth = isset($config["oauth"]) ? $config["oauth"] : false;
        $this->client = $client ?: new GuzzleClient();
    }

    /**
     * Send the request...
     *
     * @param  string $method The HTTP request verb
     * @param  string $endpoint The Hubspot API endpoint
     * @param  array $options An array of options to send with the request
     * @param  string $query_string A query string to send with the request
     * @return \SevenShores\Hubspot\Http\Response
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     */
    function request($method, $endpoint, $options = [], $query_string = null)
    {
        $url = $this->generateUrl($endpoint, $query_string);

        $options["headers"]["User-Agent"] = $this->user_agent;

        try {
            return new Response($this->client->request($method, $url, $options));
        } catch (\Exception $e) {
            throw new BadRequest($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Generate the full endpoint url, including query string.
     *
     * @param  string  $endpoint      The HubSpot API endpoint.
     * @param  string  $query_string  The query string to send to the endpoint.
     * @return string
     */
    protected function generateUrl($endpoint, $query_string = null)
    {
        $authType = $this->oauth ? "access_token" : "hapikey";

        $url = $endpoint."?".$authType."=".$this->key;
        if ($this->userId) {
            $url .= "&userId={$this->userId}";
        }

        return $url.$query_string;
    }
}
