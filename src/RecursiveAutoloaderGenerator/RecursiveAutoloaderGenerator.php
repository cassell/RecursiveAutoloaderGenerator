<?php

namespace RecursiveAutoloaderGenerator;

class RecursiveAutoloaderGenerator
{
    private $path;
    private $ignoredDirectories;
    private $pathFormatFunction;
    private $returnFormatFunction;

    function __construct($path)
    {
        $this->ignoredDirectories = Array();
        $this->pathFormatFunction = function($path) { return $path; };
        $this->returnFormatFunction = function() { return "require_once(\$classes[\$class]);"; };
        $this->setPath($path);
    }

    function setPath($path)
    {
        $this->path = $path;
    }

    function ignoreDirectory($path)
    {
        if(is_dir($path))
        {
            $this->ignoredDirectories[] = $path;
        }
        else
        {
            throw new \Exception("Directory " . htmlentities($path) . " does not exist");
        }
    }

    function setPathFormatFunction($function)
    {
        if(is_callable($function))
        {
            $this->pathFormatFunction = $function;
        }
        else
        {
            throw new \Exception("You must pass a function to setPathFormatFunction");
        }
    }

    function setReturnFormatFunction($function)
    {
        if(is_callable($function))
        {
            $this->returnFormatFunction = $function;
        }
        else
        {
            throw new \Exception("You must pass a function to setPathFormatFunction");
        }
    }

    function output()
    {
        $dirIterator  = new \RecursiveDirectoryIterator($this->path);
        $filter = new RecursiveAutoloaderFilterIterator($dirIterator,$this->ignoredDirectories);
        $itr  = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);

        $classmap = array();

        foreach($itr as $fileInfo)
        {
            if($fileInfo->getExtension() == "php")
            {
                list($namespace,$classes) = $this->getClassesAndNamespaceFromFile($fileInfo->getPathname());

                if(count($classes) > 0)
                {
                    if($namespace == null)
                    {
                        $namespace = "";
                    }
                    else
                    {
                        $namespace .= '\\';
                    }

                    foreach($classes as $class)
                    {
                        if(array_key_exists($class, $classmap) && $classmap[$class] != $fileInfo->getPathname())
                        {
                            throw new \Exception("The class " . $class . " has already been declared in " . $classmap[$class] . " but is being re-declared in " . $fileInfo->getPathname());
                        }

                        $classmap[$namespace.$class] = $fileInfo->getPathname();
                    }
                }
            }
        }

        $autoloader = "<?php";
        $autoloader .= "\n";
        $autoloader .= "spl_autoload_register(function(\$class) {" . "\n";

        $autoloader .= "\t" . '$classes = array();' . "\n";

        foreach($classmap as $className => $filePath)
        {
            $autoloader .= "\t" . "\$classes['" . $className . "'] = '" . call_user_func($this->pathFormatFunction,$filePath) . "';" . "\n";
        }

        $autoloader .= "\n";
        $autoloader .= "\t" . 'if(array_key_exists($class, $classes))' . "\n";
        $autoloader .= "\t" . '{' . "\n";
        $autoloader .= "\t\t" . call_user_func($this->returnFormatFunction) . "\n";
        $autoloader .= "\t" . '}' . "\n";


        $autoloader .= '});' . "\n";
        $autoloader .= "\n";
        $autoloader .= "?>";

        return $autoloader;


    }


    private static function getClassesAndNamespaceFromFile($path)
    {
        $classes = array();
        $namespace = null;

        $php_file = file_get_contents($path);
        preg_match('/^namespace\s+(.*);$/m', $php_file, $matches);

        if(! empty($matches[1])) {
            $namespace = $matches[1];
        }
        $tokens = token_get_all($php_file);
        foreach($tokens as $i => $token)
        {
            if(is_array($token))
            {
                if(in_array($token[0],array(T_CLASS,T_INTERFACE,T_TRAIT)))
                {
                    $temp = $i + 1;
                    while($tokens[$temp][0] != T_STRING)
                    {
                        $temp++;
                    }

                    $classes[] = $tokens[$temp][1];
                }
            }
        }

        return array ($namespace,$classes);
    }


}

