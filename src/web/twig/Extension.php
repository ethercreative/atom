<?php

namespace ether\atom\web\twig;

use ether\atom\web\twig\tokenparsers\AtomTokenParser;
use Twig\Extension\AbstractExtension;

class Extension extends AbstractExtension
{

	public function getTokenParsers (): array
	{
		return [
			new AtomTokenParser(),
		];
	}

}
