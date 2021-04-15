<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Http;

use SlackPhp\Framework\Auth\{AppCredentials, AuthContext, AuthException};
use SlackPhp\Framework\Env;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class AuthMiddleware implements MiddlewareInterface
{
    private const HEADER_SIGNATURE = 'X-Slack-Signature';
    private const HEADER_TIMESTAMP = 'X-Slack-Request-Timestamp';

    private AppCredentials $appCredentials;

    public function __construct(AppCredentials $appCredentials)
    {
        $this->appCredentials = $appCredentials;
        if (!$this->appCredentials->supportsHttpAuth()) {
            throw new AuthException('No signing key provided', 401);
        }
    }

    /**
     * Authenticates the incoming request from Slack by validating the signature.
     *
     * Currently, there is only one implementation: v0. In the future, there could be multiple. The Signature version
     * is included in the signature header, which is formatted like this: `X-Slack-Signature: {version}={signature}`
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Authentication can be disabled via env var `SLACKPHP_SKIP_AUTH=1` (for testing purposes only).
        if (Env::getSkipAuth()) {
            return $handler->handle($request);
        }

        // Validate the signature.
        $this->getAuthContext($request)->validate($this->appCredentials->getSigningKey());

        return $handler->handle($request);
    }

    /**
     * Creates an authentication context in which a signature can be validated for the request.
     *
     * @param ServerRequestInterface $request
     * @return AuthContext
     */
    private function getAuthContext(ServerRequestInterface $request): AuthContext
    {
        if (!$request->hasHeader(self::HEADER_TIMESTAMP) || !$request->hasHeader(self::HEADER_SIGNATURE)) {
            throw new AuthException('Missing required headers for authentication');
        }

        return new AuthContext(
            $request->getHeaderLine(self::HEADER_SIGNATURE),
            (int) $request->getHeaderLine(self::HEADER_TIMESTAMP),
            Util::readRequestBody($request),
            Env::getMaxClockSkew()
        );
    }
}
