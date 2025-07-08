<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Exception;

class GetProgrammeByIdBadRequestException extends BadRequestException
{
    /**
     * @var \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    private $error;
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;
    public function __construct(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error $error, \Psr\Http\Message\ResponseInterface $response)
    {
        parent::__construct('Felaktigt id.');
        $this->error = $error;
        $this->response = $response;
    }
    public function getError(): \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
    {
        return $this->error;
    }
    public function getResponse(): \Psr\Http\Message\ResponseInterface
    {
        return $this->response;
    }
}