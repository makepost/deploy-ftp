<?php

namespace Makepost\DeployFtp;

/**
 * Gets a page or posts a form over HTTP.
 */
class Fetch
{
    public static function fetch($link, $props = array())
    {
        $cookieJar = empty($props['cookieJar']) ? null : $props['cookieJar'];
        $fields = empty($props['fields']) ? array() : $props['fields'];
        $file = empty($props['file']) ? null : $props['file'];
        $method = empty($props['method']) ? 'GET' : $props['method'];

        $ch = curl_init($link);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        if ($file) {
            curl_setopt($ch, CURLOPT_FILE, $file);
        }

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return (object) array(
            'output' => $output,
            'info' => $info,
        );
    }
}
