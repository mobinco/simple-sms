<?php

namespace SimpleSoftwareIO\SMS\Drivers;

use GuzzleHttp\Client;
use SimpleSoftwareIO\SMS\MakesRequests;
use SimpleSoftwareIO\SMS\OutgoingMessage;

class IPPanelSMS extends AbstractSMS implements DriverInterface
{
    use MakesRequests;

    /**
     * The API's URL.
     *
     * @var string
     */
    protected $apiBase = 'https://api2.ippanel.com/api/v1';

    /**
     * The API key.
     *
     * @var string
     */
    protected $api_key;

    /**
     * Create the IPPanel instance.
     * 
     * @param Client $client
     */
    public function __construct(Client $client, $api_key)
    {
        $this->client = $client;
        $this->api_key = $api_key;
    }

    /**
     * Sends a SMS message.
     *
     * @param \SimpleSoftwareIO\SMS\OutgoingMessage $message
     */
    public function send(OutgoingMessage $message)
    {
        $from = $message->getFrom();
        $composeMessage = $message->composeMessage();

        //Convert to callfire format.
        $numbers = (array) implode(',', $message->getTo());

        $data = [
            'sender'        => $from,
            'recipient'     => $numbers,
            'message'       => $composeMessage,
        ];

        //Parse Pattern
        $data = $this->parsePattern($data);
        $pattern = (isset($data['code']) ? '/sms/pattern/normal/send' : '');

        $this->buildCall($pattern ? $pattern : '/sms/send/webservice/single');
        $this->buildBody($data);

        $response = $this->postRequest();
        $body = $response->getBody();
        if ($this->hasError($body)) {
            $this->handleError($body);
        }

        return $response;
    }

    /**
     * Checks if the transaction has an error.
     *
     * @param $body
     *
     * @return bool
     */
    protected function hasError($body)
    {
        if ($this->hasAResponseMessage($body) && $this->getResponseCode($body) != '0') {
            $responseCode = $this->getResponseCode($body);

            return (int) $responseCode['status'] !== 0;
        }

        return false;
    }

    /**
     * Log the error message which ocurred.
     *
     * @param $body
     */
    protected function handleError($body)
    {
        $responseCode = $this->getResponseCode($body);
        $error = 'An error occurred. IPPanel status code: '.$responseCode['status'];
        if ($responseCode != '0') {
            $error = $this->getResponseData($body);
        }

        $this->throwNotSentException($error, $responseCode['status']);
    }

    /**
     * Check for a message in the response from IPPanel.
     *
     * @param $body
     */
    protected function hasAResponseMessage($body)
    {
        return
            is_array($body) &&
            array_key_exists('messages', $body) &&
            array_key_exists(0, $body['messages']);
    }

    /**
     * Get the response code in the response from IPPanel.
     *
     * @param $body
     */
    protected function getResponseCode($body)
    {
        return $body['messages'][0];
    }

    /**
     * Get the response data in the response from IPPanel.
     *
     * @param $body
     */
    protected function getResponseData($body)
    {
        return $body['messages'][1];
    }

    /**
     * Check if the message from IPPanel has a given property.
     *
     * @param $message
     * @param $property
     *
     * @return bool
     */
    protected function hasProperty($message, $property)
    {
        return array_key_exists($property, $message);
    }

    /**
     * Creates many IncomingMessage objects and sets all of the properties.
     *
     * @param $rawMessage
     *
     * @return mixed
     */
    protected function processReceive($rawMessage)
    {
        $incomingMessage = $this->createIncomingMessage();
        $incomingMessage->setRaw($rawMessage);
        // $incomingMessage->setFrom((string) $rawMessage->from);
        // $incomingMessage->setMessage((string) $rawMessage->body);
        // $incomingMessage->setId((string) $rawMessage->{'message-id'});
        // $incomingMessage->setTo((string) $rawMessage->to);

        return $incomingMessage;
    }

    /**
     * Checks the server for messages and returns their results.
     *
     * @param array $options
     *
     * @return array
     */
    public function checkMessages(array $options = [])
    {
        $this->buildCall('/sms/message/all');

        $this->buildBody($options);

        $rawMessages = json_decode($this->getRequest()->getBody()->getContents());

        return $this->makeMessages($rawMessages->items);
    }

    /**
     * Gets a single message by it's ID.
     *
     * @param string|int $messageId
     *
     * @return \SimpleSoftwareIO\SMS\IncomingMessage
     */
    public function getMessage($messageId)
    {
        $this->buildCall('/sms/message/show-recipient/message-id/'.$messageId);

        return $this->makeMessage(json_decode($this->getRequest()->getBody()->getContents()));
    }

    /**
     * Creates and sends a POST request to the requested URL.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function postRequest()
    {
        $body = $this->getBody();
    
        $response = $this->client->post($this->buildUrl(),
        [
            'json'      => $body,
            'headers'   => [
                'apikey'        => $this->api_key,
                'accept'        => 'application/json',
                'Content-type'  => 'application/json',
            ],
        ]);
        
        if ($response->getStatusCode() != 201 && $response->getStatusCode() != 200) {
            throw new \Exception('Unable to request from API. HTTP Error: '.$response->getStatusCode());
        }

        return $response;
    }

    /**
     * Receives an incoming message via REST call.
     *
     * @param mixed $raw
     *
     * @return \SimpleSoftwareIO\SMS\IncomingMessage
     */
    public function receive($raw)
    {
        $incomingMessage = $this->createIncomingMessage();
        $incomingMessage->setRaw($raw->get());
        $incomingMessage->setMessage($raw->get('text'));
        $incomingMessage->setFrom($raw->get('msisdn'));
        $incomingMessage->setId($raw->get('messageId'));
        $incomingMessage->setTo($raw->get('to'));

        return $incomingMessage;
    }

    /**
     * Parse Message Content for Pattern Code
     */
    public function parsePattern($data)
    {
        $message = $data['message'] ? : '';
        if (strtolower(substr( $message, 0, 4 )) != "pid=") {
            return $data;
        }

        $arrRet = [];
        //Split New Line
        $arrLine = preg_split('/\r\n|\n|\r/', $message);
        foreach ($arrLine as $line) {
            //Split by =, :
            $arrPrm = preg_split('/=/', $line, 2);
            if (count($arrPrm) == 2) {
                $arrRet[trim($arrPrm[0])] = $arrPrm[1];
            }
        }

        if (count($arrRet) > 0) {
            $data['code'] = $arrRet['pid'];
            $data['recipient'] = isset($data['recipient'][0]) ? $data['recipient'][0] : '';

            unset($data['message']);
            unset($data['recipient']);
            unset($arrRet['pid']);
            $data['variable'] = $arrRet;
        }

        return $data;
    }
}
