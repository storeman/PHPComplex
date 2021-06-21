<?php

/**
 *
 * Class for the management of Complex numbers
 *
 * @copyright  Copyright (c) 2013-2018 Mark Baker (https://github.com/MarkBaker/PHPComplex)
 * @license    https://opensource.org/licenses/MIT    MIT
 */
namespace Complex;

/**
 * Complex Number object.
 *
 * @package Complex
 *
 * @method float abs()
 * @method Complex acos()
 * @method Complex acosh()
 * @method Complex acot()
 * @method Complex acoth()
 * @method Complex acsc()
 * @method Complex acsch()
 * @method float argument()
 * @method Complex asec()
 * @method Complex asech()
 * @method Complex asin()
 * @method Complex asinh()
 * @method Complex atan()
 * @method Complex atanh()
 * @method Complex conjugate()
 * @method Complex cos()
 * @method Complex cosh()
 * @method Complex cot()
 * @method Complex coth()
 * @method Complex csc()
 * @method Complex csch()
 * @method Complex exp()
 * @method Complex inverse()
 * @method Complex ln()
 * @method Complex log2()
 * @method Complex log10()
 * @method Complex negative()
 * @method Complex pow(int|float $power)
 * @method float rho()
 * @method Complex sec()
 * @method Complex sech()
 * @method Complex sin()
 * @method Complex sinh()
 * @method Complex sqrt()
 * @method Complex tan()
 * @method Complex tanh()
 * @method float theta()
 * @method Complex add(...$complexValues)
 * @method Complex subtract(...$complexValues)
 * @method Complex multiply(...$complexValues)
 * @method Complex divideby(...$complexValues)
 * @method Complex divideinto(...$complexValues)
 */
class Complex
{
    /**
     * @constant    Euler's Number.
     */
    const EULER = 2.7182818284590452353602874713526624977572;

    /**
     * @constant    Regexp to split an input string into real and imaginary components and suffix
     */
    const NUMBER_SPLIT_REGEXP =
        '` ^
            (                                   # Real part
                [-+]?(\d+\.?\d*|\d*\.?\d+)          # Real value (integer or float)
                ([Ee][-+]?[0-2]?\d{1,3})?           # Optional real exponent for scientific format
            )
            (                                   # Imaginary part
                [-+]?(\d+\.?\d*|\d*\.?\d+)          # Imaginary value (integer or float)
                ([Ee][-+]?[0-2]?\d{1,3})?           # Optional imaginary exponent for scientific format
            )?
            (                                   # Imaginary part is optional
                ([-+]?)                             # Imaginary (implicit 1 or -1) only
                ([ij]?)                             # Imaginary i or j - depending on whether mathematical or engineering
            )
        $`uix';

    /**
     * @var    float    $realPart    The value of of this complex number on the real plane.
     */
    protected $realPart = 0.0;

    /**
     * @var    float    $imaginaryPart    The value of of this complex number on the imaginary plane.
     */
    protected $imaginaryPart = 0.0;

    /**
     * @var    string    $suffix    The suffix for this complex number (i or j).
     */
    protected $suffix;


    /**
     * Validates whether the argument is a valid complex number, converting scalar or array values if possible
     *
     * @param     mixed    $complexNumber   The value to parse
     * @return    array
     * @throws    Exception    If the argument isn't a Complex number or cannot be converted to one
     */
    private static function parseComplex($complexNumber)
    {
        // Test for real number, with no imaginary part
        if (is_numeric($complexNumber)) {
            return [$complexNumber, 0, null];
        }

        // Fix silly human errors
        $complexNumber = str_replace(
            ['+-', '-+', '++', '--'],
            ['-', '-', '+', '+'],
            $complexNumber
        );

        // Basic validation of string, to parse out real and imaginary parts, and any suffix
        $validComplex = preg_match(
            self::NUMBER_SPLIT_REGEXP,
            $complexNumber,
            $complexParts
        );

        if (!$validComplex) {
            // Neither real nor imaginary part, so test to see if we actually have a suffix
            $validComplex = preg_match('/^([\-\+]?)([ij])$/ui', $complexNumber, $complexParts);
            if (!$validComplex) {
                throw new Exception('Invalid complex number');
            }
            // We have a suffix, so set the real to 0, the imaginary to either 1 or -1 (as defined by the sign)
            $imaginary = 1;
            if ($complexParts[1] === '-') {
                $imaginary = 0 - $imaginary;
            }
            return [0, $imaginary, $complexParts[2]];
        }

        // If we don't have an imaginary part, identify whether it should be +1 or -1...
        if (($complexParts[4] === '') && ($complexParts[9] !== '')) {
            if ($complexParts[7] !== $complexParts[9]) {
                $complexParts[4] = 1;
                if ($complexParts[8] === '-') {
                    $complexParts[4] = -1;
                }
            } else {
                // ... or if we have only the real and no imaginary part
                //  (in which case our real should be the imaginary)
                $complexParts[4] = $complexParts[1];
                $complexParts[1] = 0;
            }
        }

        // Return real and imaginary parts and suffix as an array, and set a default suffix if user input lazily
        return [
            $complexParts[1],
            $complexParts[4],
            !empty($complexParts[9]) ? $complexParts[9] : 'i'
        ];
    }


    public function __construct($realPart = 0.0, $imaginaryPart = null, $suffix = 'i')
    {
        if ($imaginaryPart === null) {
            if (is_array($realPart)) {
                // We have an array of (potentially) real and imaginary parts, and any suffix
                list ($realPart, $imaginaryPart, $suffix) = array_values($realPart) + [0.0, 0.0, 'i'];
            } elseif ((is_string($realPart)) || (is_numeric($realPart))) {
                // We've been given a string to parse to extract the real and imaginary parts, and any suffix
                list($realPart, $imaginaryPart, $suffix) = self::parseComplex($realPart);
            }
        }

        if ($imaginaryPart != 0.0 && empty($suffix)) {
            $suffix = 'i';
        } elseif ($imaginaryPart == 0.0 && !empty($suffix)) {
            $suffix = '';
        }

        // Set parsed values in our properties
        $this->realPart = (float) $realPart;
        $this->imaginaryPart = (float) $imaginaryPart;
        $this->suffix = strtolower($suffix ?? '');
    }

    /**
     * Gets the real part of this complex number
     *
     * @return Float
     */
    public function getReal(): float
    {
        return $this->realPart;
    }

    /**
     * Gets the imaginary part of this complex number
     *
     * @return Float
     */
    public function getImaginary(): float
    {
        return $this->imaginaryPart;
    }

    /**
     * Gets the suffix of this complex number
     *
     * @return String
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Returns true if this is a real value, false if a complex value
     *
     * @return Bool
     */
    public function isReal(): bool
    {
        return $this->imaginaryPart == 0.0;
    }

    /**
     * Returns true if this is a complex value, false if a real value
     *
     * @return Bool
     */
    public function isComplex(): bool
    {
        return !$this->isReal();
    }

    public function format(): string
    {
        $str = "";
        if ($this->imaginaryPart != 0.0) {
            if (\abs($this->imaginaryPart) != 1.0) {
                $str .= $this->imaginaryPart . $this->suffix;
            } else {
                $str .= (($this->imaginaryPart < 0.0) ? '-' : '') . $this->suffix;
            }
        }
        if ($this->realPart != 0.0) {
            if (($str) && ($this->imaginaryPart > 0.0)) {
                $str = "+" . $str;
            }
            $str = $this->realPart . $str;
        }
        if (!$str) {
            $str = "0.0";
        }

        return $str;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Validates whether the argument is a valid complex number, converting scalar or array values if possible
     *
     * @param     mixed    $complex   The value to validate
     * @return    Complex
     * @throws    Exception    If the argument isn't a Complex number or cannot be converted to one
     */
    public static function validateComplexArgument($complex): Complex
    {
        if (is_scalar($complex) || is_array($complex)) {
            $complex = new Complex($complex);
        } elseif (!is_object($complex) || !($complex instanceof Complex)) {
            throw new Exception('Value is not a valid complex number');
        }

        return $complex;
    }

    /**
     * Returns the reverse of this complex number
     *
     * @return    Complex
     */
    public function reverse(): Complex
    {
        return new Complex(
            $this->imaginaryPart,
            $this->realPart,
            ($this->realPart == 0.0) ? null : $this->suffix
        );
    }

    public function invertImaginary(): Complex
    {
        return new Complex(
            $this->realPart,
            $this->imaginaryPart * -1,
            ($this->imaginaryPart == 0.0) ? null : $this->suffix
        );
    }

    public function invertReal(): Complex
    {
        return new Complex(
            $this->realPart * -1,
            $this->imaginaryPart,
            ($this->imaginaryPart == 0.0) ? null : $this->suffix
        );
    }

    protected static $functions = [
        'abs',
        'acos',
        'acosh',
        'acot',
        'acoth',
        'acsc',
        'acsch',
        'argument',
        'asec',
        'asech',
        'asin',
        'asinh',
        'atan',
        'atanh',
        'conjugate',
        'cos',
        'cosh',
        'cot',
        'coth',
        'csc',
        'csch',
        'exp',
        'inverse',
        'ln',
        'log2',
        'log10',
        'negative',
        'pow',
        'rho',
        'sec',
        'sech',
        'sin',
        'sinh',
        'sqrt',
        'tan',
        'tanh',
        'theta',
    ];

    protected static $operations = [
        'add',
        'subtract',
        'multiply',
        'divideby',
        'divideinto',
    ];

    /**
     * Returns the result of the function call or operation
     *
     * @return    Complex|float
     * @throws    Exception|\InvalidArgumentException
     */
    public function __call($functionName, $arguments)
    {
        $functionName = strtolower(str_replace('_', '', $functionName));
        $functionName = '_' . $functionName;
        if (method_exists(self::class, $functionName)) {
            return self::$functionName($this, ...$arguments);
        }
        throw new Exception('Complex Function or Operation does not exist');
    }
    public static function __callStatic($functionName, $arguments)
    {
        $functionName = strtolower(str_replace('_', '', $functionName));
        $functionName = '_' . $functionName;
        if (method_exists(self::class, $functionName)) {
            return self::$functionName(...$arguments);
        }
        throw new Exception('Complex Function or Operation does not exist');
    }

    protected static function _abs($complex): float
    {
        return rho($complex);
    }

    protected static function _acos($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        $square = clone $complex;
        $square = multiply($square, $complex);
        $invsqrt = new Complex(1.0);
        $invsqrt = subtract($invsqrt, $square);
        $invsqrt = sqrt($invsqrt);
        $adjust = new Complex(
            $complex->getReal() - $invsqrt->getImaginary(),
            $complex->getImaginary() + $invsqrt->getReal()
        );
        $log = ln($adjust);

        return new Complex(
            $log->getImaginary(),
            -1 * $log->getReal()
        );
    }

    protected static function _acosh($complex)
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal() && ($complex->getReal() > 1)) {
            return new Complex(\acosh($complex->getReal()));
        }

        $acosh = acos($complex)
            ->reverse();
        if ($acosh->getReal() < 0.0) {
            $acosh = $acosh->invertReal();
        }

        return $acosh;
    }

    protected static function _acot($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        return atan(inverse($complex));
    }

    protected static function _acoth($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        return atanh(inverse($complex));
    }

    protected static function _acsc($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return new Complex(INF);
        }

        return asin(inverse($complex));
    }

    protected static function _acsch($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return new Complex(INF);
        }

        return asinh(inverse($complex));
    }

    protected static function _add(...$complexValues): Complex
    {
        if (count($complexValues) < 2) {
            throw new \Exception('This function requires at least 2 arguments');
        }

        $base = array_shift($complexValues);
        $result = clone Complex::validateComplexArgument($base);

        foreach ($complexValues as $complex) {
            $complex = Complex::validateComplexArgument($complex);

            if ($result->isComplex() && $complex->isComplex() &&
                $result->getSuffix() !== $complex->getSuffix()) {
                throw new Exception('Suffix Mismatch');
            }

            $real = $result->getReal() + $complex->getReal();
            $imaginary = $result->getImaginary() + $complex->getImaginary();

            $result = new Complex(
                $real,
                $imaginary,
                ($imaginary == 0.0) ? null : max($result->getSuffix(), $complex->getSuffix())
            );
        }

        return $result;
    }

    protected static function _argument($complex): float
    {
        return theta($complex);
    }

    protected static function _asec($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return new Complex(INF);
        }

        return acos(inverse($complex));
    }

    protected static function _asech($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return new Complex(INF);
        }

        return acosh(inverse($complex));
    }

    protected static function _asin($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        $square = multiply($complex, $complex);
        $invsqrt = new Complex(1.0);
        $invsqrt = subtract($invsqrt, $square);
        $invsqrt = sqrt($invsqrt);
        $adjust = new Complex(
            $invsqrt->getReal() - $complex->getImaginary(),
            $invsqrt->getImaginary() + $complex->getReal()
        );
        $log = ln($adjust);

        return new Complex(
            $log->getImaginary(),
            -1 * $log->getReal()
        );
    }

    protected static function _asinh($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal() && ($complex->getReal() > 1)) {
            return new Complex(\asinh($complex->getReal()));
        }

        $asinh = clone $complex;
        $asinh = $asinh->reverse()
            ->invertReal();
        $asinh = asin($asinh);
        return $asinh->reverse()
            ->invertImaginary();
    }

    protected static function _atan($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal()) {
            return new Complex(\atan($complex->getReal()));
        }

        $t1Value = new Complex(-1 * $complex->getImaginary(), $complex->getReal());
        $uValue = new Complex(1, 0);

        $d1Value = clone $uValue;
        $d1Value = subtract($d1Value, $t1Value);
        $d2Value = add($t1Value, $uValue);
        $uResult = $d1Value->divideBy($d2Value);
        $uResult = ln($uResult);

        return new Complex(
            (($uResult->getImaginary() == M_PI) ? -M_PI : $uResult->getImaginary()) * -0.5,
            $uResult->getReal() * 0.5,
            $complex->getSuffix()
        );
    }

    protected static function _atanh($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal()) {
            $real = $complex->getReal();
            if ($real >= -1.0 && $real <= 1.0) {
                return new Complex(\atanh($real));
            } else {
                return new Complex(\atanh(1 / $real), (($real < 0.0) ? M_PI_2 : -1 * M_PI_2));
            }
        }

        $iComplex = clone $complex;
        $iComplex = $iComplex->invertImaginary()
            ->reverse();
        return atan($iComplex)
            ->invertReal()
            ->reverse();
    }

    protected static function _conjugate($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        return new Complex(
            $complex->getReal(),
            -1 * $complex->getImaginary(),
            $complex->getSuffix()
        );
    }

    protected static function _cos($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal()) {
            return new Complex(\cos($complex->getReal()));
        }

        return conjugate(
            new Complex(
                \cos($complex->getReal()) * \cosh($complex->getImaginary()),
                \sin($complex->getReal()) * \sinh($complex->getImaginary()),
                $complex->getSuffix()
            )
        );
    }

    protected static function _cosh($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal()) {
            return new Complex(\cosh($complex->getReal()));
        }

        return new Complex(
            \cosh($complex->getReal()) * \cos($complex->getImaginary()),
            \sinh($complex->getReal()) * \sin($complex->getImaginary()),
            $complex->getSuffix()
        );
    }

    protected static function _cot($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return new Complex(INF);
        }

        return inverse(tan($complex));
    }

    protected static function _coth($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);
        return inverse(tanh($complex));
    }

    protected static function _csc($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return new Complex(INF);
        }

        return inverse(sin($complex));
    }

    protected static function _csch($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return new Complex(INF);
        }

        return inverse(sinh($complex));
    }

    protected static function _divideby(...$complexValues): Complex
    {
        if (count($complexValues) < 2) {
            throw new \Exception('This function requires at least 2 arguments');
        }

        $base = array_shift($complexValues);
        $result = clone Complex::validateComplexArgument($base);

        foreach ($complexValues as $complex) {
            $complex = Complex::validateComplexArgument($complex);

            if ($result->isComplex() && $complex->isComplex() &&
                $result->getSuffix() !== $complex->getSuffix()) {
                throw new Exception('Suffix Mismatch');
            }
            if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
                throw new \InvalidArgumentException('Division by zero');
            }

            $delta1 = ($result->getReal() * $complex->getReal()) +
                ($result->getImaginary() * $complex->getImaginary());
            $delta2 = ($result->getImaginary() * $complex->getReal()) -
                ($result->getReal() * $complex->getImaginary());
            $delta3 = ($complex->getReal() * $complex->getReal()) +
                ($complex->getImaginary() * $complex->getImaginary());

            $real = $delta1 / $delta3;
            $imaginary = $delta2 / $delta3;

            $result = new Complex(
                $real,
                $imaginary,
                ($imaginary == 0.0) ? null : max($result->getSuffix(), $complex->getSuffix())
            );
        }

        return $result;
    }

    protected static function _divideinto(...$complexValues): Complex
    {
        if (count($complexValues) < 2) {
            throw new \Exception('This function requires at least 2 arguments');
        }

        $base = array_shift($complexValues);
        $result = clone Complex::validateComplexArgument($base);

        foreach ($complexValues as $complex) {
            $complex = Complex::validateComplexArgument($complex);

            if ($result->isComplex() && $complex->isComplex() &&
                $result->getSuffix() !== $complex->getSuffix()) {
                throw new Exception('Suffix Mismatch');
            }
            if ($result->getReal() == 0.0 && $result->getImaginary() == 0.0) {
                throw new \InvalidArgumentException('Division by zero');
            }

            $delta1 = ($complex->getReal() * $result->getReal()) +
                ($complex->getImaginary() * $result->getImaginary());
            $delta2 = ($complex->getImaginary() * $result->getReal()) -
                ($complex->getReal() * $result->getImaginary());
            $delta3 = ($result->getReal() * $result->getReal()) +
                ($result->getImaginary() * $result->getImaginary());

            $real = $delta1 / $delta3;
            $imaginary = $delta2 / $delta3;

            $result = new Complex(
                $real,
                $imaginary,
                ($imaginary == 0.0) ? null : max($result->getSuffix(), $complex->getSuffix())
            );
        }

        return $result;
    }

    protected static function _exp($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if (($complex->getReal() == 0.0) && (\abs($complex->getImaginary()) == M_PI)) {
            return new Complex(-1.0, 0.0);
        }

        $rho = \exp($complex->getReal());

        return new Complex(
            $rho * \cos($complex->getImaginary()),
            $rho * \sin($complex->getImaginary()),
            $complex->getSuffix()
        );
    }

    protected static function _inverse($complex): Complex
    {
        $complex = clone Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            throw new \InvalidArgumentException('Division by zero');
        }

        return $complex->divideInto(1.0);
    }

    protected static function _ln($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if (($complex->getReal() == 0.0) && ($complex->getImaginary() == 0.0)) {
            throw new \InvalidArgumentException();
        }

        return new Complex(
            \log(rho($complex)),
            theta($complex),
            $complex->getSuffix()
        );
    }

    protected static function _log10($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if (($complex->getReal() == 0.0) && ($complex->getImaginary() == 0.0)) {
            throw new \InvalidArgumentException();
        } elseif (($complex->getReal() > 0.0) && ($complex->getImaginary() == 0.0)) {
            return new Complex(\log10($complex->getReal()), 0.0, $complex->getSuffix());
        }

        return ln($complex)
            ->multiply(\log10(Complex::EULER));
    }

    protected static function _log2($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if (($complex->getReal() == 0.0) && ($complex->getImaginary() == 0.0)) {
            throw new \InvalidArgumentException();
        } elseif (($complex->getReal() > 0.0) && ($complex->getImaginary() == 0.0)) {
            return new Complex(\log($complex->getReal(), 2), 0.0, $complex->getSuffix());
        }

        return ln($complex)
            ->multiply(\log(Complex::EULER, 2));
    }

    protected static function _multiply(...$complexValues): Complex
    {
        if (count($complexValues) < 2) {
            throw new \Exception('This function requires at least 2 arguments');
        }

        $base = array_shift($complexValues);
        $result = clone Complex::validateComplexArgument($base);

        foreach ($complexValues as $complex) {
            $complex = Complex::validateComplexArgument($complex);

            if ($result->isComplex() && $complex->isComplex() &&
                $result->getSuffix() !== $complex->getSuffix()) {
                throw new Exception('Suffix Mismatch');
            }

            $real = ($result->getReal() * $complex->getReal()) -
                ($result->getImaginary() * $complex->getImaginary());
            $imaginary = ($result->getReal() * $complex->getImaginary()) +
                ($result->getImaginary() * $complex->getReal());

            $result = new Complex(
                $real,
                $imaginary,
                ($imaginary == 0.0) ? null : max($result->getSuffix(), $complex->getSuffix())
            );
        }

        return $result;
    }

    protected static function _negative($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        return new Complex(
            -1 * $complex->getReal(),
            -1 * $complex->getImaginary(),
            $complex->getSuffix()
        );
    }

    protected static function _pow($complex, $power): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if (!is_numeric($power)) {
            throw new Exception('Power argument must be a real number');
        }

        if ($complex->getImaginary() == 0.0 && $complex->getReal() >= 0.0) {
            return new Complex(\pow($complex->getReal(), $power));
        }

        $rValue = \sqrt(($complex->getReal() * $complex->getReal()) + ($complex->getImaginary() * $complex->getImaginary()));
        $rPower = \pow($rValue, $power);
        $theta = $complex->argument() * $power;
        if ($theta == 0) {
            return new Complex(1);
        }

        return new Complex($rPower * \cos($theta), $rPower * \sin($theta), $complex->getSuffix());
    }

    protected static function _rho($complex): float
    {
        $complex = Complex::validateComplexArgument($complex);

        return \sqrt(
            ($complex->getReal() * $complex->getReal()) +
            ($complex->getImaginary() * $complex->getImaginary())
        );
    }

    protected static function _sec($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        return inverse(cos($complex));
    }

    protected static function _sech($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        return inverse(cosh($complex));
    }

    protected static function _sin($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal()) {
            return new Complex(\sin($complex->getReal()));
        }

        return new Complex(
            \sin($complex->getReal()) * \cosh($complex->getImaginary()),
            \cos($complex->getReal()) * \sinh($complex->getImaginary()),
            $complex->getSuffix()
        );
    }

    protected static function _sinh($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal()) {
            return new Complex(\sinh($complex->getReal()));
        }

        return new Complex(
            \sinh($complex->getReal()) * \cos($complex->getImaginary()),
            \cosh($complex->getReal()) * \sin($complex->getImaginary()),
            $complex->getSuffix()
        );
    }

    protected static function _sqrt($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        $theta = theta($complex);
        $delta1 = \cos($theta / 2);
        $delta2 = \sin($theta / 2);
        $rho = \sqrt(rho($complex));

        return new Complex($delta1 * $rho, $delta2 * $rho, $complex->getSuffix());
    }

    protected static function _subtract(...$complexValues): Complex
    {
        if (count($complexValues) < 2) {
            throw new \Exception('This function requires at least 2 arguments');
        }

        $base = array_shift($complexValues);
        $result = clone Complex::validateComplexArgument($base);

        foreach ($complexValues as $complex) {
            $complex = Complex::validateComplexArgument($complex);

            if ($result->isComplex() && $complex->isComplex() &&
                $result->getSuffix() !== $complex->getSuffix()) {
                throw new Exception('Suffix Mismatch');
            }

            $real = $result->getReal() - $complex->getReal();
            $imaginary = $result->getImaginary() - $complex->getImaginary();

            $result = new Complex(
                $real,
                $imaginary,
                ($imaginary == 0.0) ? null : max($result->getSuffix(), $complex->getSuffix())
            );
        }

        return $result;
    }

    protected static function _tan($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->isReal()) {
            return new Complex(\tan($complex->getReal()));
        }

        $real = $complex->getReal();
        $imaginary = $complex->getImaginary();
        $divisor = 1 + \pow(\tan($real), 2) * \pow(\tanh($imaginary), 2);
        if ($divisor == 0.0) {
            throw new \InvalidArgumentException('Division by zero');
        }

        return new Complex(
            \pow(sech($imaginary)->getReal(), 2) * \tan($real) / $divisor,
            \pow(sec($real)->getReal(), 2) * \tanh($imaginary) / $divisor,
            $complex->getSuffix()
        );
    }

    protected static function _tanh($complex): Complex
    {
        $complex = Complex::validateComplexArgument($complex);
        $real = $complex->getReal();
        $imaginary = $complex->getImaginary();
        $divisor = \cos($imaginary) * \cos($imaginary) + \sinh($real) * \sinh($real);
        if ($divisor == 0.0) {
            throw new \InvalidArgumentException('Division by zero');
        }

        return new Complex(
            \sinh($real) * \cosh($real) / $divisor,
            0.5 * \sin(2 * $imaginary) / $divisor,
            $complex->getSuffix()
        );
    }

    protected static function _theta($complex): float
    {
        $complex = Complex::validateComplexArgument($complex);

        if ($complex->getReal() == 0.0) {
            if ($complex->isReal()) {
                return 0.0;
            } elseif ($complex->getImaginary() < 0.0) {
                return M_PI / -2;
            }
            return M_PI / 2;
        } elseif ($complex->getReal() > 0.0) {
            return \atan($complex->getImaginary() / $complex->getReal());
        } elseif ($complex->getImaginary() < 0.0) {
            return -(M_PI - \atan(\abs($complex->getImaginary()) / \abs($complex->getReal())));
        }

        return M_PI - \atan($complex->getImaginary() / \abs($complex->getReal()));
    }
}
