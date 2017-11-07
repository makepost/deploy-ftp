<?php

namespace Makepost\DeployFtp\File;

use Makepost\DeployFtp\Fetch\Fetch;
use Makepost\DeployFtp\Ftp\Ftp;

/**
 * Gets remote files using ftp and unzipper.
 */
class FileGet
{
    public $baseUri;
    public $publicHtml;
    public $db;
    public $unzipper;

    protected $ftp;

    public function run()
    {
        $this->getUnzipper();

        $this->ftp = Ftp::connect($this->publicHtml);
        $this->getPublicHtml();
    }

    protected function getUnzipper()
    {
        $file = fopen('unzipper.php', 'w');

        Fetch::fetch($this->unzipper, array(
            'file' => $file,
        ));
    }

    protected function getPublicHtml()
    {
        $ftp = $this->ftp;

        $ftp->put('unzipper.php');

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

        $ftp->get('unzipper.zip', $zipper);

        $ftp->delete($zipper);
        $ftp->delete('unzipper.php');

        shell_exec('unzip unzipper.zip');

        unlink('unzipper.php');
        unlink('unzipper.zip');
    }
}
