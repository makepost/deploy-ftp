<?php

namespace Makepost\DeployFtp\Db;

use Makepost\DeployFtp\Fetch\Fetch;
use Makepost\DeployFtp\Ftp\Ftp;
use Makepost\DeployFtp\Uri\Uri;

/**
 * Puts remote db using ftp and adminer.
 */
class DbPut
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
        $this->putDb();
    }

    protected function getAdminer()
    {
        $file = fopen('adminer.php', 'w');

        Fetch::fetch($this->adminer, array(
            'file' => $file,
        ));
    }

    protected function putDb()
    {
        $ftp = $this->ftp;

        $this->cookieJar = tempnam(sys_get_temp_dir(), 'sna');

        $ftp->put('adminer.php');
        $ftp->put('adminer.sql');

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
            'db' => $db,
            'import' => '',
        ));

        $res = Fetch::fetch($this->baseUri . '/adminer.php?' . $query, array(
            'cookieJar' => $this->cookieJar,
        ));

        preg_match('/name="token" value="([^"]+)"/', $res->output, $matches);
        $token = $matches[1];

        $query = http_build_query(array(
            'server' => $host,
            'username' => $username,
            'db' => $db,
            'import' => '',
        ));

        $fields = array(
            'webfile' => 'Run file',
            'error_stops' => '1',
            'only_errors' => '1',
            'token' => $token,
        );

        Fetch::fetch($this->baseUri . '/adminer.php?' . $query, array(
            'method' => 'POST',
            'fields' => $fields,
            'cookieJar' => $this->cookieJar,
        ));

        $ftp->delete('adminer.php');
        $ftp->delete('adminer.sql');

        unlink('adminer.php');
        unlink($this->cookieJar);
    }
}
