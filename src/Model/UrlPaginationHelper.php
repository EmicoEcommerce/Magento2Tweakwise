<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model;

class UrlPaginationHelper
{
    /**
     * @param string $url
     * @param int $page
     * @return string
     */
    public function appendPageParam(string $url, int $page): string
    {
        $urlParts = parse_url($url);
        if ($urlParts === false) {
            $separator = str_contains($url, '?') ? '&' : '?';
            return $url . $separator . http_build_query(['p' => $page]);
        }

        $query = [];
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $query);
        }

        $query['p'] = $page;

        return (isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '')
            . ($urlParts['host'] ?? '')
            . ($urlParts['path'] ?? '')
            . '?' . http_build_query($query);
    }
}
