<?php

require __DIR__ . '/../vendor/autoload.php';

$paths = [__DIR__ . '/../classes/src/functions/*', __DIR__ . '/../classes/src/operations/*'];

$methods = [];
$functions = [];
foreach($paths as $path) {
    foreach (glob($path) as $filePath) {
        $reflector = new \PhangoReflection\ReflectionFile($filePath);
        foreach ($reflector->getNamespaces()[0]->getFunctions() as $function) {
            $source = $function->getSourceCode();

            preg_match('#function\s+(\w+)\s*\(#', $source, $match);

            $source = preg_replace('#(function\s+)(\w+\()#', '$1_$2', $source);

            $methods[$function->getShortName()] = '    protected static ' . $source;
            $functions[] = explode($reflector->getLineEnding(), $function->getSourceCode())[0]
                            . $reflector->getLineEnding() . '    {'
                            . $reflector->getLineEnding()
                            . '        return Complex::' . $match[1] . '(...func_get_args());'
                            . $reflector->getLineEnding() . '    }';
        }

        unset($reflector);
        @unlink($filePath);
    }
    @rmdir(dirname($path));
}

ksort($methods);

$mainFile = __DIR__ . '/../classes/src/Complex.php';
$reflector = new \PhangoReflection\ReflectionFile($mainFile);
$callMethod = $reflector->getNamespaces()[0]->getClasses()['Complex']->getMethod('__call');
$lines = explode($reflector->getLineEnding(), $callMethod->getSourceCode());
$replace = [
    '        $functionName = \'_\' . $functionName;',
    '        if (method_exists(self::class, $functionName)) {',
    '            return self::$functionName($this, ...$arguments);',
    '        }',
];
array_splice($lines, 3, -2, $replace);

$lines = array_merge($lines, [explode(PHP_EOL, <<<'PHP'
    public static function __callStatic($functionName, $arguments)
    {
        $functionName = strtolower(str_replace('_', '', $functionName));
        $functionName = '_' . $functionName;
        if (method_exists(self::class, $functionName)) {
            return self::$functionName(...$arguments);
        }
        throw new Exception('Complex Function or Operation does not exist');
    }
PHP
)]);

$newCode =  implode($reflector->getLineEnding(), $lines)
    . PHP_EOL . PHP_EOL
    . implode(PHP_EOL . PHP_EOL, $methods);

file_put_contents($mainFile, str_replace($callMethod->getSourceCode(), $newCode, file_get_contents($mainFile)));

$functionFile = dirname($mainFile) . '/functions.php';
file_put_contents($functionFile, implode(PHP_EOL . PHP_EOL, $functions));