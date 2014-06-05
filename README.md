#RecursiveAutoloaderGenerator

Example Usage

	$al = new RecursiveAutoloaderGenerator\RecursiveAutoloaderGenerator(dirname(__DIR__) . '/inc');
	$al->ignoreDirectory(dirname(__DIR__) . '/inc/vendor');
	$al->setPathFormatFunction(function($path) { return str_replace(dirname(__DIR__) . "/inc", "", $path); });
	$al->setReturnFormatFunction(function() { return "require_once(__DIR__ . \$classes[\$class]);"; });

	file_put_contents(dirname(__DIR__) . '/inc/autoload.php', $al->output());
