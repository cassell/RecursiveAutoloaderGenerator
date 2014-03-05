<?php

namespace RecursiveAutoloaderGenerator;

class RecursiveAutoloaderFilterIterator extends \RecursiveFilterIterator {
	
	private $ignoredDirectories = array();

	function __construct(\RecursiveIterator $iterator,Array $ignoredDirectories)
	{
		$iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
		$this->ignoredDirectories = $ignoredDirectories;
		parent::__construct($iterator);
	}

	public function accept()
	{
		if($this->current()->isLink())
		{
			// skip symbolic links
			return false;
		}
		else if($this->current()->isFile() && $this->current()->getExtension() != "php")
		{
			// skip non php files
			return false;
		}
		else if($this->current()->isDir() && in_array($this->current()->getPathname(), $this->ignoredDirectories))
		{
			// skip directories passed
			return false;
		}
		else
		{
			return true;
		}
	}

	public function getChildren()
	{
		return new RecursiveAutoloaderFilterIterator($this->getInnerIterator()->getChildren(), $this->ignoredDirectories);
	}

}


