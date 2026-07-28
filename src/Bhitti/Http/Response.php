<?php

declare(strict_types=1);

namespace Bhitti\Http;

class Response
{
    protected int $status = 200;
    protected array $headers = [];
    protected string $content = '';

    public function __construct(string $content = '', int $status = 200)
    {
        $this->content = $content;
        $this->status = $status;
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function headers(array $headers): static
    {
        foreach ($headers as $key => $value) {
            $this->header($key, $value);
        }

        return $this;
    }

    public function status(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function json(mixed $data, int $status = 200): static
    {
        $this->content = json_encode($data, JSON_THROW_ON_ERROR);
        $this->status = $status;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';

        return $this;
    }

    public function html(string $content, int $status = 200): static
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';

        return $this;
    }

    public function redirect(string $url = '', int $status = 302, array $headers = []): ResponseRedirect
    {
        return new ResponseRedirect($url, $status, $headers);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }

        echo $this->content;
    }
}
