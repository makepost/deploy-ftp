<?php

namespace Makepost\DeployFtp\Uri;

/**
 * Gets parts of a link.
 *
 * @see http://php.net/manual/en/function.ftp-connect.php
 */
class Uri
{
    public function __construct($str)
    {
        preg_match('/^([a-z]+):\/\/(.*?):(.*?)@(.*?)(\/.*)$/i', $str, $matches);

        $this->driver = $matches[1];
        $this->username = $matches[2];
        $this->password = $matches[3];
        $this->host = $matches[4];
        $this->path = $matches[5];
    }

    /**
     * `list($driver, $username, $password, $host, $path) = Uri::parse('...');`.
     */
    public static function parse($str)
    {
        $uri = new self($str);

        return array(
            $uri->driver,
            $uri->username,
            $uri->password,
            $uri->host,
            $uri->path,
        );
    }
}
