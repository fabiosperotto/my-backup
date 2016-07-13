<?php
namespace Tool\FileParser;
class JsonFile
{
	private $fileDecoded;

	/**
	 * Construtor JsonFile
	 * @param string $filepath caminho completo ao arquivo .json com configuracoes do backup
	 */
	public function __construct($filepath)
	{

		$this->checkFile($filepath);		
		$string = file_get_contents($filepath);
		if(!$string){
			die("Error processing Json File, check data format\n\n");
		}					
		$this->fileDecoded = json_decode($string,true);
	}

	public function getFileDecoded()
	{
		return $this->fileDecoded;
	}


	/**
	 * Valida arquivo de acesso
	 * @param string $path com caminho completo para o arquivo de configuracao de backups
	 * @return boolean
	 */
	private function checkFile($path)
	{
		if(!is_file($path)){
			die("Backup configuration file not found\n\n");

		}

		if($this->fileDecoded){
			die("Verify configuration files\n\n");
		}

		return true;
	}
}