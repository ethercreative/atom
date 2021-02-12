<?php

namespace ether\atom\web\twig\nodes;

use ether\atom\Atom;
use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Node\NodeCaptureInterface;

class AtomNode extends Node implements NodeCaptureInterface
{

	public function compile (Compiler $compiler)
	{
		$isVariableName = $this->getAttribute('isVariableName');
		$handle = $this->getAttribute('handle');
		/** @var ArrayExpression $data */
		$data = $this->getAttribute('data');
		$value = $this->getNode('value');

		$compiler
			->addDebugInfo($this)
			->write('ob_start();' . PHP_EOL)
			->subcompile($value)
			->write(Atom::class . '::renderAtom(');

		if ($isVariableName)
			$compiler->subcompile($handle);
		else
			$compiler->write('\'' . $handle . '\'');

		$compiler
			->write(', ')
			->raw($data->compile($compiler) . ', ')
			->raw('ob_get_clean());' . PHP_EOL);
	}

}
