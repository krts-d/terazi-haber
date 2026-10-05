<?php
declare(strict_types=1);

namespace Terazi;

/**
 * Downloads all feeds in parallel, using ETag / Last-Modified so unchanged
 * feeds cost the outlet (and you) almost nothing.
 */
final class FeedFetcher
{
    /**
     * @param array<string, array{feed:string}> $sources
     * @param array<string, array{etag:?string,last_modified:?string}> $state
     * @return array<string, array{status:int, body:?string, error:?string, etag:?string, last_modified:?string}>
     */
    public static function fetchAll(array $sources, array $state, int $timeout, string $userAgent): array
    {
        $results = [];
        $mh = curl_multi_init();
        $handles = [];

        foreach ($sources as $id => $s) {
            $feed = $s['feed'];
            if (!preg_match('#^https?://#i', $feed)) {
                $results[$id] = ['status' => 0, 'body' => null, 'error' => 'Feed URL must start with http:// or https://', 'etag' => null, 'last_modified' => null];
                continue;
            }

            $headers = ['Accept: application/rss+xml, application/atom+xml, application/rdf+xml, application/xml;q=0.9, text/xml;q=0.9, */*;q=0.5'];
            if (!empty($state[$id]['etag'])) {
                $headers[] = 'If-None-Match: ' . $state[$id]['etag'];
            }
            if (!empty($state[$id]['last_modified'])) {
                $headers[] = 'If-Modified-Since: ' . $state[$id]['last_modified'];
            }

            $ch = curl_init($feed);
            $respHeaders = [];
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_ENCODING => '',            // accept gzip/deflate/br
                CURLOPT_USERAGENT => $s['user_agent'] ?? $userAgent,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_MAXFILESIZE => 10 * 1024 * 1024,
                CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$respHeaders, $id) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $respHeaders[$id][strtolower(trim($parts[0]))] = trim($parts[1]);
                    }
                    return strlen($line);
                },
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$id] = $ch;
        }

        $codes = [];
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
            while ($info = curl_multi_info_read($mh)) {
                $codes[spl_object_id($info['handle'])] = $info['result'];
            }
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $id => $ch) {
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $res = $codes[spl_object_id($ch)] ?? CURLE_OK;
            $err = $res !== CURLE_OK ? curl_strerror($res) : curl_error($ch);
            $body = curl_multi_getcontent($ch);
            $h = $respHeaders[$id] ?? [];
            $results[$id] = [
                'status' => $code,
                'body' => ($code === 200 && is_string($body)) ? $body : null,
                'error' => $err !== '' ? $err : (($code >= 400 || $code === 0) ? "HTTP $code" : null),
                'etag' => $h['etag'] ?? null,
                'last_modified' => $h['last-modified'] ?? null,
            ];
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);

        return $results;
    }
}
