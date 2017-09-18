<?php

namespace TrustPay\Capture;

use TrustPay\Enums\CardTransactionType;
use TrustPay\HttpClient\Client;
use TrustPay\RequestAwareTrait;
use TrustPay\RequestInterface;
use TrustPay\Response;
use TrustPay\SignatureValidator;

class Request implements RequestInterface
{
    use RequestAwareTrait;

    /** @var Client */
    private $httpClient;

    /** @var integer */
    private $transactionId;

    /**
     * Request constructor.
     *
     * @param $accountId
     * @param $secret
     * @param $endpoint
     */
    public function __construct($accountId, $secret, $endpoint)
    {
        $this->setAccountId($accountId);
        $this->setSignatureValidator(new SignatureValidator($secret));
        $this->setEndpoint($endpoint);
        $this->httpClient = new Client($endpoint);
    }

    /**
     * @param $transactionId
     *
     * @return Response
     */
    public function capture($transactionId)
    {
        $this->transactionId = $transactionId;

        $requestUrl = $this->getUrl();

        $response = $this->httpClient->get($requestUrl);

        $response = $this->parseBackgroundResponse($response);
        $response->setRequestedUrl($requestUrl);

        return $response;
    }

    /**
     * @return string
     */
    protected function buildQuery()
    {
        $queryData = $this->getDefaultQueryData();

        $queryData = array_merge($queryData, $this->getCaptureQueryData());

        return http_build_query($queryData);
    }

    /**
     * @param $result
     *
     * @return Response
     */
    private function getResponse($result)
    {

    }

    /**
     * @return array
     */
    private function getCaptureQueryData()
    {
        $message = $this->signatureValidator->createMessage(
            $this->accountId,
            $this->amount,
            $this->currency,
            $this->reference,
            CardTransactionType::CAPTURE,
            $this->transactionId
        );

        $queryData = [
            'AID'     => $this->accountId,
            'AMT'     => $this->amount,
            'CUR'     => $this->currency,
            'REF'     => $this->reference,
            'SIG'     => $this->createStandardSignature(),
            'CTY'     => CardTransactionType::CAPTURE,
            'TID'     => $this->transactionId,
            'SIG2'    => $this->signatureValidator->computeSignature($message),
        ];

        $queryData = array_filter($queryData, function ($value) {
            return $value !== null;
        });

        return $queryData;
    }
}