<?php

namespace Makepost\DeployFtp;

/**
 * Grabs a snapshot using ftp, adminer and unzipper.
 */
class Init
{
    public $storageDir;
    public $baseUri;
    public $publicHtml;
    public $db;
    public $adminer = 'https://github.com/vrana/adminer/releases/download/v4.3.1/adminer-4.3.1-mysql-en.php';
    public $unzipper = 'https://raw.githubusercontent.com/ndeet/unzipper/master/unzipper.php';

    protected $cookieJar;
    protected $ftp;

    public function run()
    {
        if (!file_exists($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }

        $this->getAdminer();
        $this->getUnzipper();

        $this->ftp = Ftp::connect($this->publicHtml);
        $this->getDb();
        $this->getPublicHtml();
    }

    private function getAdminer()
    {
        $file = fopen($this->storageDir . '/adminer.php', 'w');

        Fetch::fetch($this->adminer, array(
            'file' => $file,
        ));
    }

    private function getUnzipper()
    {
        $file = fopen($this->storageDir . '/unzipper.php', 'w');

        Fetch::fetch($this->unzipper, array(
            'file' => $file,
        ));
    }

    private function getPublicHtml()
    {
        $ftp = $this->ftp;

        $this->cookieJar = tempnam(sys_get_temp_dir(), 'sna');
        $ftp->put($this->storageDir . '/unzipper.php');

        $fields = array(
            'zippath' => '',
            'dozip' => 'Zip Archive',
        );

        $res = Fetch::fetch($this->baseUri . '/unzipper.php', array(
            'method' => 'POST',
            'fields' => $fields,
        ));

        preg_match('/(zipper-\d{4}-\d{2}-\d{2}--\d{2}-\d{2}.zip)/', $res->output, $matches);
        $zipper = $matches[1];

        $ftp->get($this->storageDir . '/' . $zipper);
        $ftp->delete($zipper);
        $ftp->delete('unzipper.php');
        unlink($this->cookieJar);
    }

    private function getDb()
    {
        $ftp = $this->ftp;

        $this->cookieJar = tempnam(sys_get_temp_dir(), 'sna');

        $ftp->put($this->storageDir . '/adminer.php');

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

        $file = fopen($this->storageDir . '/db-' . date('Y-m-d--H-i-s') . '.sql', 'w');

        Fetch::fetch($this->baseUri . '/adminer.php?' . $query, array(
            'method' => 'POST',
            'fields' => $fields,
            'file' => $file,
            'cookieJar' => $this->cookieJar,
        ));

        $ftp->delete('adminer.php');
        unlink($this->cookieJar);
    }
}
