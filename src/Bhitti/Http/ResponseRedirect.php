<?php

declare(strict_types=1);

namespace Bhitti\Http;

use Bhitti\Session\Session;

final class ResponseRedirect extends Response
{
    public function __construct(
        string $url = '',
        int $status = 302,
        array $headers = []
    ) {
        parent::__construct('', $status);

        $this->headers = $headers;

        if ($url !== '') {
            $this->to($url);
        }
    }

    public function with(array $data): self
    {
        Session::set('flash', $data);

        return $this;
    }

    public function to(string $url, ?int $status = null): self
    {
        if ($status !== null) {
            $this->status = $status;
        }

        $this->headers['Location'] = $this->resolveUrl($url);

        return $this;
    }

    public function back(string $fallback = ''): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($referer !== '' && $this->isSameDomain($referer)) {
            $this->headers['Location'] = $referer;

            return $this;
        }

        return $this->to($fallback);
    }

    public function away(string $url): self
    {
        $this->headers['Location'] = $url;
        return $this;
    }

    private function resolveUrl(string $url): string
    {
        if (
            str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
        ) {
            return $url;
        }

        return rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
    }

    private function isSameDomain(string $url): bool
    {
        return parse_url($url, PHP_URL_HOST)
            === parse_url(BASE_URL, PHP_URL_HOST);
    }
}
