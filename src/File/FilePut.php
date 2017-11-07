<?php

namespace Makepost\DeployFtp\File;

use Makepost\DeployFtp\Fetch\Fetch;
use Makepost\DeployFtp\Ftp\Ftp;

/**
 * Puts remote files using ftp and unzipper.
 */
class FilePut
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
        $this->putPublicHtml();
    }

    protected function getUnzipper()
    {
        $file = fopen('unzipper.php', 'w');

        Fetch::fetch($this->unzipper, array(
            'file' => $file,
        ));
    }

    protected function putPublicHtml()
    {
        shell_exec('git archive --format=zip HEAD > unzipper.zip');
        shell_exec('zip -d unzipper.zip adminer.sql');

        $ftp = $this->ftp;
        $ftp->put('unzipper.zip');
        $ftp->put('unzipper.php');

        $fields = array(
            'zipfile' => 'unzipper.zip',
            'extpath' => '',
            'dounzip' => 'Unzip Archive',
        );

        Fetch::fetch($this->baseUri . '/unzipper.php', array(
            'method' => 'POST',
            'fields' => $fields,
        ));

        $ftp->delete('unzipper.zip');
        $ftp->delete('unzipper.php');

        unlink('unzipper.php');
        unlink('unzipper.zip');
    }
}
