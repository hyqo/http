<?php

namespace Hyqo\Http;

class Response
{
    public ResponseHeaders $headers;

    public function __construct(?HttpCode $code = null, protected ?string $content = null)
    {
        $this->headers = (new ResponseHeaders)->setCode($code ?? HttpCode::OK);
    }

    public static function create(?HttpCode $code = null): self
    {
        return new self($code);
    }

    public function setCode(HttpCode $code): Response
    {
        $this->headers->setCode($code);

        return $this;
    }

    public function setContent(string $content): Response
    {
        $this->content = $content;

        return $this;
    }

    public function setHeader(string $name, string $value): Response
    {
        $this->headers->set($name, $value);

        return $this;
    }

    public function setContentType(string $mediaType, ?string $charset = null): Response
    {
        $this->headers->contentType->setMediaType($mediaType);

        if (null !== $charset) {
            $this->headers->contentType->setCharset($charset);
        }

        return $this;
    }

    public function sendAsAttachment(string $filename, string $mimeType): void
    {
        $this->headers->contentDisposition->setAttachment($filename);
        $this->headers->set('Content-Length', strlen($this->content));
        $this->setContentType($mimeType);
        $this->send();
    }

    public function send(): void
    {
        foreach ($this->headers->all() as $header) {
            header($header);
        }

        echo $this->content;
    }
}
