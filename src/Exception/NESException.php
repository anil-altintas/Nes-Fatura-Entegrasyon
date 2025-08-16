<?php

namespace NES\Exception;

class NESException extends \Exception
{
    private $errorCode;
    private $errorDetails;
    private $apiResponse;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $errorCode = null,
        ?array $errorDetails = null,
        ?array $apiResponse = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errorDetails = $errorDetails;
        $this->apiResponse = $apiResponse;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }

    public function getApiResponse(): ?array
    {
        return $this->apiResponse;
    }

    public static function fromApiResponse(array $response, string $message = "API Hatası"): self
    {
        $errorCode = $response['error_code'] ?? $response['code'] ?? null;
        $errorDetails = $response['error_details'] ?? $response['details'] ?? null;
        
        return new self(
            $message,
            0,
            null,
            $errorCode,
            $errorDetails,
            $response
        );
    }

    public static function authenticationFailed(string $message = "Kimlik doğrulama başarısız"): self
    {
        return new self($message, 401, null, 'AUTH_FAILED');
    }

    public static function invalidRequest(string $message = "Geçersiz istek"): self
    {
        return new self($message, 400, null, 'INVALID_REQUEST');
    }

    public static function serverError(string $message = "Sunucu hatası"): self
    {
        return new self($message, 500, null, 'SERVER_ERROR');
    }
}
