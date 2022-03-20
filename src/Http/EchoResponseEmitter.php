<?php

declare(strict_types=1);

namespace SlackPhp\Framework\Http;

use Psr\Http\Message\ResponseInterface;

use function header;
use function headers_sent;
use function ob_get_length;
use function ob_get_level;
use function sprintf;
use function ucwords;

/**
 * HTTP-related helper functions.
 *
 * This code was borrowed and modified from the the Laminas framework.
 *
 * @see https://github.com/laminas/laminas-httphandlerrunner
 */
class EchoResponseEmitter implements ResponseEmitter
{
    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     *
     * @param ResponseInterface $response
     * @throws HttpException if emitting the response fails.
     */
    public function emit(ResponseInterface $response): void
    {
        $this->assertNoPreviousOutput();
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        echo $response->getBody();
    }

    /**
     * Checks to see if content has previously been sent.
     *
     * If either headers have been sent or the output buffer contains content,
     * raises an exception.
     *
     * @throws HttpException if headers have already been sent.
     * @throws HttpException if output is present in the output buffer.
     */
    private function assertNoPreviousOutput(): void
    {
        if (headers_sent()) {
            throw new HttpException('HTTP Error: Headers already sent');
        }

        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw new HttpException('HTTP Error: Output buffer is not empty');
        }
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is available, it, too, is emitted.
     *
     * It is important to mention that this method should be called after
     * `emitHeaders()` in order to prevent PHP from changing the status code of
     * the emitted response.
     *
     * @param ResponseInterface $response
     */
    private function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        $statusCode   = $response->getStatusCode();

        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        ), true, $statusCode);
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     *
     * @param ResponseInterface $response
     */
    private function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name  = ucwords($header, '-');
            $first = $name === 'Set-Cookie' ? false : true;
            foreach ($values as $value) {
                header(sprintf(
                    '%s: %s',
                    $name,
                    $value
                ), $first, $statusCode);
                $first = false;
            }
        }
    }
}
