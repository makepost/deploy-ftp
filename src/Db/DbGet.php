<?php

namespace Makepost\DeployFtp\Db;

use Makepost\DeployFtp\Fetch\Fetch;
use Makepost\DeployFtp\Ftp\Ftp;
use Makepost\DeployFtp\Uri\Uri;

/**
 * Gets remote db using ftp and adminer.
 */
class DbGet
{
    public $baseUri;
    public $publicHtml;
    public $db;
    public $adminer;

    protected $cookieJar;
    protected $ftp;

    public function run()
    {
        $this->getAdminer();

        $this->ftp = Ftp::connect($this->publicHtml);
        $this->getDb();
    }

    protected function getAdminer()
    {
        $file = fopen('adminer.php', 'w');

        Fetch::fetch($this->adminer, array(
            'file' => $file,
        ));
    }

    protected function getDb()
    {
        $ftp = $this->ftp;

        $this->cookieJar = tempnam(sys_get_temp_dir(), 'sna');

        $ftp->put('adminer.php');

        list($driver, $username, $password, $host, $db) = Uri::parse($this->db);
        $db = ltrim($db, '/');

        $fields = array(
            'auth[driver]' => 'server',
            'auth[server]' => $host,
            'auth[username]' => $username,
            'auth[password]' => $password,
            'auth[db]' => $db,
        );

        Fetch::fetch($this->baseUri . '/adminer.php', array(
            'method' => 'POST',
            'fields' => $fields,
            'cookieJar' => $this->cookieJar,
        ));

        $query = http_build_query(array(
            'server' => $host,
            'username' => $username,
            'dump' => '',
        ));

        $res = Fetch::fetch($this->baseUri . '/adminer.php?' . $query, array(
            'cookieJar' => $this->cookieJar,
        ));

        preg_match('/name="token" value="([^"]+)"/', $res->output, $matches);
        $token = $matches[1];

        $query = http_build_query(array(
            'server' => $host,
            'username' => $username,
            'dump' => '',
        ));

        $fields = array(
            'output' => 'text',
            'format' => 'sql',
            'db_style' => '',
            'routines' => '1',
            'events' => '1',
            'table_style' => 'DROP+CREATE',
            'triggers' => '1',
            'data_style' => 'INSERT',
            'token' => $token,
            'databases[]' => $db,
        );

        $file = fopen('adminer.sql', 'w');

        Fetch::fetch($this->baseUri . '/adminer.php?' . $query, array(
            'method' => 'POST',
            'fields' => $fields,
            'file' => $file,
            'cookieJar' => $this->cookieJar,
        ));

        $ftp->delete('adminer.php');

        unlink('adminer.php');
        unlink($this->cookieJar);
    }
}
