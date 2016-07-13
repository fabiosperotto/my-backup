<?php
namespace Tool\FileParser;
class XMLFile
{
	private $dirBackups;
	private $xmlConfiguration;

	/**
	 * Description
	 * @param string $dir caminho para diretorio de backups
	 */
	public function __construct($dir)
	{
		if(!is_dir($dir)) die('Folder for backups not found.');
		$this->dirBackups = $dir;		
	}

	/**
	 * Inicializa o cabecalho do futuro arquivo XML para a ferramenta PHPBU
	 */
	private function setHeaderXML()
	{
		$this->xmlConfiguration = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpbu xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:noNamespaceSchemaLocation="http://schema.phpbu.de/3.1/phpbu.xsd">
  <backups>    
  </backups>
</phpbu>
XML;
	}

	/**
	 * Gera arquivo XML para a ferramenta PHPBU com as configuracoes oriundas do .json
	 * @param array $data com as dados processados do .json de configuracao do banco de dados
	 */
	public function createXMLConfiguration($data)
	{

		if(!is_array($data)) die('Configuration data needs an array, verifiy json coniguration file structure\n\n');

		$this->setHeaderXML();
		$configurationXML = new \SimpleXMLElement($this->xmlConfiguration);

		foreach ($data['backups'] as $key=>$value) {
			

			$backup = $configurationXML->backups->addChild('backup');

			$source = $backup->addChild('source');
			$source->addAttribute('type', 'mysqldump');

			$option = $source->addChild('option');
			$option->addAttribute('name', 'host');
			$option->addAttribute('value', $value['host']);

			$option = $source->addChild('option');
			$option->addAttribute('name', 'databases');
			$option->addAttribute('value', $value['databases']);

			$option = $source->addChild('option');
			$option->addAttribute('name', 'user');
			$option->addAttribute('value', $value['user']);

			$option = $source->addChild('option');
			$option->addAttribute('name', 'password');
			$option->addAttribute('value', $value['password']);


			$target = $backup->addChild('target');
			$target->addAttribute('dirname', $key);
			$target->addAttribute('filename', 'mysqldump-%Y%m%d-%H%i.sql');
			$target->addAttribute('compress', '');


			$cleanup = $backup->addChild('cleanup');
			$cleanup->addAttribute('type', 'quantity');
			$option = $cleanup->addChild('option');
			$option->addAttribute('name', 'amount');
			$option->addAttribute('value', '2');
		}

		$configurationXML->asXML($this->dirBackups.'/configurationBase.xml');
	}
}