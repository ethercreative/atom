<?php


namespace ether\atom\web\twig\tokenparsers;


use ether\atom\web\twig\nodes\AtomNode;
use Exception;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

class AtomTokenParser extends AbstractTokenParser
{

	/**
	 * Parses a token and returns a node.
	 *
	 * @param Token $token
	 *
	 * @return Node
	 * @throws SyntaxError
	 */
	public function parse (Token $token): Node
	{
		$parser = $this->parser;
		$lineNo = $token->getLine();
		$stream = $parser->getStream();
		$expressionParser = $parser->getExpressionParser();
		$nodes = [
			'value' => new Node(),
			'handle' => new Node(),
		];
		$attributes = [
			'isVariableName' => false,
			'data' => new ArrayExpression([], $lineNo),
		];

		// Ensure it's a valid atom tag
		if (!$stream->test(Token::PUNCTUATION_TYPE) && $stream->getCurrent()->getValue() === ':')
			throw new SyntaxError('Looks like you\'re missing a `:` after x!');

		$stream->next();

		// Get the atom name
		$isVariableName = $stream->getCurrent()->getValue() === '[';
		$handle = '';

		if ($isVariableName)
		{
			$stream->next();
			$strHandle = $this->_getDynamicName($stream);
			$handle = $expressionParser->parseExpression();
		}
		else
		{
			do {
				$handle .= $stream->getCurrent()->getValue();
				$stream->next();
			} while (
				!$stream->test(Token::PUNCTUATION_TYPE)
				&& !$stream->test(Token::BLOCK_END_TYPE)
			);

			$strHandle = $handle;
		}

		if (empty($handle))
			throw new SyntaxError('You must specify an atom name');

		if ($isVariableName)
			$stream->next();

		$attributes['isVariableName'] = $isVariableName;
		$attributes['handle'] = $handle;

		// Are any variables defined?
		if ($stream->test(Token::PUNCTUATION_TYPE))
		{
			$attributes['data'] = $expressionParser->parseHashExpression();
		}

		// Is this a tag pair?
		$capture = $this->_lookForClosing($stream, $strHandle);

		// Capture the contents
		if ($capture)
		{
			$stream->expect(Token::BLOCK_END_TYPE);
			$nodes['value'] = $parser->subparse([$this, 'decideBlockEnd'], true);

			$stream->expect(Token::PUNCTUATION_TYPE, ':');
			$stream->nextIf(Token::PUNCTUATION_TYPE, '[');
			foreach (preg_split('/(-)/', $strHandle, -1, PREG_SPLIT_DELIM_CAPTURE) as $word)
			{
				if ($word === '-') $stream->expect(Token::OPERATOR_TYPE, $word);
				else $stream->expect(Token::NAME_TYPE, $word);
			}
			$stream->nextIf(Token::PUNCTUATION_TYPE, ']');
		}

		// Close out the tag
		$stream->expect(Token::BLOCK_END_TYPE);

		return new AtomNode(
			$nodes,
			$attributes,
			$lineNo,
			$this->getTag()
		);
	}

	/**
	 * Gets the tag name associated with this token parser.
	 *
	 * @return string The tag name
	 */
	public function getTag (): string
	{
		return 'x';
	}

	/**
	 * @param Token $token
	 *
	 * @return bool
	 */
	public function decideBlockEnd (Token $token): bool
	{
		return $token->test('endx');
	}

	// Helpers
	// =========================================================================

	/**
	 * Check to see if there is a closing tag up ahead.
	 *
	 * @param TokenStream $stream
	 * @param string      $handle
	 *
	 * @return bool
	 * @throws SyntaxError
	 */
	private function _lookForClosing (TokenStream $stream, string $handle): bool
	{
		$count = 0;
		$openers = [];

		while (true)
		{
			try {
				$token = $stream->look(++$count);
			} catch (Exception $e) {
				// EOF
				// This is shitty by there's no nice way of finding out if we're
				// at the end of the stream when we're just looking :(
				return false;
			}

			// Is this a name token
			if ($token->getType() === Token::NAME_TYPE)
			{
				$value = $token->getValue();

				// If it's an opening atom tag
				if ($value === $this->getTag())
					$openers[] = $this->_getDynamicName($stream, $count + 2);

				// Only check end tokens
				if (!str_contains($value, 'end'))
					continue;

				// Is this an atom end token?
				if ($this->decideBlockEnd($token))
				{
					if ($stream->look($count + 1)->getValue() !== ':')
						throw new SyntaxError('Missing endx colon');

					$endName = $this->_getDynamicName($stream, $count + 2);

					// If it's not our end tag, ignore it
					if ($endName !== $handle)
						continue;

					// Get index of most recent matching opener
					$i = array_search($endName, array_reverse($openers, true), true);

					// If we have a matching opener, remove it from the list
					if ($i) array_splice($openers, $i, 1);

					// If we don't have a matching opener, that means
					// this closing tag is ours.
					return ($i === false && $endName === $handle);
				}
			}
		}
	}

	/**
	 * @param TokenStream $stream
	 * @param int         $count
	 *
	 * @return string
	 * @throws SyntaxError
	 */
	private function _getDynamicName (TokenStream $stream, $count = 0): string
	{
		$strHandle = '';

		if ($stream->look($count)->test(Token::PUNCTUATION_TYPE, ['[']))
			$count++;

		do {
			$strHandle .= $stream->look($count++)->getValue();
		} while (
			!$stream->look($count)->test(Token::PUNCTUATION_TYPE)
			&& !$stream->look($count)->test(Token::BLOCK_END_TYPE)
		);

		return $strHandle;
	}

}
