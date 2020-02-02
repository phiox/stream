<?php
namespace Phiox\Decorator;

use Exception;
use Phiox\Decorator\Parser\TokenInterface;

/**
 * Stream parser class.
 * 
 * @licence nope.
 */
class Parser
{

    /**
     * @var string Syntax type name
     */
	protected $grammar;

    /**
     * @var array Rules, terminals / productions
     */
    protected $definitions;

    /**
     * @param string $name
     * @param array  $definitions
     */
    public function __construct($name, $definitions)
    {
        $this->grammar = $name;

        foreach ($definitions as $identifier => $definition) {
            $this->definitions[$identifier] = $definition;
        }
    }

    /**
     * @param string        $id
     * @param array         $rule
     * @param null|callable $action Transforms the result to a custom value
     */
    public function addDefinition($id, $rule, $action = null)
    {
    	$this->definitions[$id] = [$rule, $action];
    }

    /**
     * Parses the input string using the defined grammar.
     *
     * The return value is one of:
     *   `null`   there is no match
     *   `string` the consumed part of the string
     *   `mixed`  the value returned by the PEG actions defined in the grammar
     *
     * @param  string            $input
     * @return null|string|mixed
     * @throws Exception
     */
    public function parse($input)
    {
        $result = $this->parseDefinition($this->grammar, $input);

        if (!$result->isMatch()) {
            return null;
        }

        return $result->value();
    }

    /**
     * Parses using a definition.
     *
     * @param  string         $identifier
     * @param  string         $input
     * @param  int            $offset     Current position in the $input
     * @return TokenInterface
     * @throws Exception                  When the definition is invalid.
     */
    public function parseDefinition($identifier, $input, $offset = 0)
    {
        if (!isset($this->definitions[$identifier])) {
            throw new Exception('Unknown parsing rule: ' . $identifier);
        }

        $definition = $this->definitions[$identifier];

        try {
            $result = $this->parseOperator($definition->rule(), $input, $offset);

            if (!$result->isMatch()) {
                return $result;
            }

            return TokenInterface::match($result->length(), $definition->call($result->value()), $result->offset());
        } catch (Exception $e) {
            throw new Exception('Invalid rule: ' . $identifier, $e);
        }
    }

    /**
     * Parses using an operator.
     *
     * @param  array          $operator First element is the name, other elements values
     * @param  string         $input
     * @param  int            $offset   Current position in the input string
     * @return TokenInterface
     * @throws Exception                When the operator is not known
     */
    private function parseOperator($operator, $input, $offset)
    {
        $method = 'parse' . $operator[0];

        if (method_exists($this, $method)) {
            return $this->$method($operator, $input, $offset);
        }

        throw new Exception('Undefined: ' . $operator[0]);
    }

    /**
     * Parses a literal operator.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return mixed
     */
    private function parseLiteral($operator, $input, $offset)
    {
        if (substr($input, $offset, strlen($operator[1])) === $operator[1]) {
            return TokenInterface::match(strlen($operator[1]), $operator[1], $offset);
        }

        return TokenInterface::noMatch($offset);
    }

    /**
     * Parses a definition identifier.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return mixed|string|null
     * @throws Exception
     */
    private function parseIdentifier($operator, $input, $offset)
    {
        return $this->parse($operator[1], $input, $offset);
    }

    /**
     * Parses a repeat group.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return mixed
     * @throws Exception
     */
    private function parseRepeat($operator, $input, $offset)
    {
        $_offset = $offset;
        $childOperator = $operator[1];
        $min = $operator[2] ?? 0;
        $max = $operator[3] ?? INF;
        $matches = [];
        $matchLen = 0;
        $inputLen = strlen($input);

        $i = 0;
        while (++$i <= $max) {
            $result = $this->parseOperator($childOperator, $input, $offset);

            $offset = $result->newOffset();
            if (!$result->isMatch() || $offset > $inputLen) {
                if ($i <= $min) {
                    return TokenInterface::noMatch($_offset);
                }

                break;
            }
            $matches[] = $result->value();
            $matchLen += $result->length();
        }

        return TokenInterface::match($matchLen, $matches, $_offset);
    }

    /**
     * Parses a character class.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return mixed
     */
    private function parseCharacterClass($operator, $input, $offset)
    {
        $regex = '{^[' . $operator[1] . ']}';

        if (preg_match($regex, substr($input, $offset), $match)) {
            return TokenInterface::match(1, $match[0], $offset);
        }

        return TokenInterface::noMatch($offset);
    }

    /**
     * Parses a sequence.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return mixed
     * @throws Exception
     */
    private function parseSequence($operator, $input, $offset)
    {
        $_offset = $offset;
        $sequence = $operator[1];
        $matches = [];
        $matchLen = 0;

        foreach ($sequence as $operator) {
            $result = $this->parseOperator($operator, $input, $offset);

            if (!$result->isMatch()) {
                return TokenInterface::noMatch($_offset);
            }

            $offset = $result->newOffset();
            $matches[] = $result->value();
            $matchLen += $result->length();
        }

        return TokenInterface::match($matchLen, $matches, $_offset);
    }

    /**
     * Parses a prioritized choice / first match.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return TokenInterface
     * @throws Exception
     */
    private function parseChoice($operator, $input, $offset)
    {
        $operators = $operator[1];

        foreach ($operators as $operator) {
            $result = $this->parseOperator($operator, $input, $offset);

            if ($result->isMatch()) {
                return $result;
            }
        }

        return TokenInterface::noMatch($offset);
    }

    /**
     * Parses the any operator.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return mixed
     */
    private function parseAny($operator, $input, $offset)
    {
        if ((strlen($input) - $offset) >= 1) {
            return TokenInterface::match(1, substr($input, $offset, 1), $offset);
        }

        return TokenInterface::noMatch($offset);
    }

    /**
     * Parses the not precedent.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return mixed
     * @throws Exception
     */
    private function parseNot($operator, $input, $offset)
    {
        $result = $this->parseOperator($operator[1], $input, $offset);

        if ($result->isMatch()) {
            return TokenInterface::noMatch($offset);
        }

        return TokenInterface::match(0, null, $offset);
    }

    /**
     * Parses the and precedent.
     *
     * @param  $operator
     * @param  $input
     * @param  $offset
     * @return mixed
     * @throws Exception
     */
    private function parseAnd($operator, $input, $offset)
    {
        $result = $this->parseOperator($operator[1], $input, $offset);

        if ($result->isMatch()) {
            return TokenInterface::match(0, null, $offset);
        }

        return TokenInterface::noMatch($offset);
    }
}
