<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Auth;

use function abs;
use function hash_equals;
use function hash_hmac;
use function sprintf;
use function substr;
use function time;

class AuthContext
{
    private const SIGNATURE_VERSION = 'v0';
    private const SIGNATURE_PREFIX = self::SIGNATURE_VERSION . '=';
    private const HASHING_ALGO = 'sha256';

    private string $signature;
    private int $timestamp;
    private string $bodyContent;
    private int $maxClockSkew;

    /**
     * @param string $signature
     * @param int $timestamp
     * @param string $bodyContent
     * @param int $maxClockSkew
     */
    public function __construct(string $signature, int $timestamp, string $bodyContent, int $maxClockSkew = 60 * 5)
    {
        $this->signature = $signature;
        $this->timestamp = $timestamp;
        $this->bodyContent = $bodyContent;
        $this->maxClockSkew = $maxClockSkew;
    }

    public function validate(string $signingKey): void
    {
        if (abs(time() - $this->timestamp) > $this->maxClockSkew) {
            throw new AuthException('Timestamp is too old or too new.');
        }

        if (substr($this->signature, 0, 3) !== self::SIGNATURE_PREFIX) {
            throw new AuthException('Missing or unsupported signature version');
        }

        $stringToSign = sprintf('%s:%d:%s', self::SIGNATURE_VERSION, $this->timestamp, $this->bodyContent);
        $expectedSignature = self::SIGNATURE_PREFIX . hash_hmac(self::HASHING_ALGO, $stringToSign, $signingKey);

        if (!hash_equals($this->signature, $expectedSignature)) {
            throw new AuthException('Signature (v0) failed validation');
        }
    }
}
