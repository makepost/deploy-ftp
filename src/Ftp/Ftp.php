<?php

namespace Makepost\DeployFtp\Ftp;

use Makepost\DeployFtp\Uri\Uri;

/**
 * Puts/gets/deletes files using FTP without renaming.
 */
class Ftp
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function delete($path)
    {
        ftp_delete($this->conn, $path);
    }

    public function get($localFile, $remoteFile = null)
    {
        if (null === $remoteFile) {
            $remoteFile = basename($localFile);
        }

        ftp_get($this->conn, $localFile, $remoteFile, FTP_BINARY);
    }

    public function put($localFile)
    {
        ftp_put($this->conn, basename($localFile), $localFile, FTP_BINARY);
    }

    public static function connect($uri)
    {
        list($driver, $username, $password, $host, $path) = Uri::parse($uri);

        $conn = ftp_connect($host);

        ftp_login($conn, $username, $password);
        ftp_chdir($conn, $path);

        return new self($conn);
    }
}
