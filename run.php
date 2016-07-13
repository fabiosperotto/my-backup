<?php
require_once __DIR__ . '/vendor/autoload.php';
use Tool\Controller\Command as Command;
use Tool\FileParser\JsonFile as JsonFile;
use Tool\FileParser\XMLFile as XMLFile;

$backupdir = 'backup';
$file = new JsonFile(__DIR__ . '/'.$backupdir.'/backups.json');
$data = $file->getFileDecoded();
$xmlParser = new XMLFile(__DIR__ . '/'.$backupdir);
$xmlParser->createXMLConfiguration($file->getFileDecoded());

echo `php vendor/phpbu/phpbu/phpbu --configuration=$backupdir/configurationBase.xml --colors`;