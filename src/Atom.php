<?php

namespace ether\atom;

use Craft;
use craft\base\Plugin;
use ether\atom\web\twig\Extension;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Exception;

class Atom extends Plugin
{

	private static $_config = [];

	public function init ()
	{
		parent::init();

		self::$_config = require __DIR__ . '/config.php';
		if (file_exists(CRAFT_BASE_PATH . '/config/atom.php'))
		{
			self::$_config = array_merge(
				self::$_config,
				require CRAFT_BASE_PATH . '/config/atom.php'
			);
		}

		Craft::$app->getView()->registerTwigExtension(
			new Extension()
		);
	}

	/**
	 * Renders an atom
	 *
	 * @param string      $handle
	 * @param array       $variables
	 * @param string|null $children
	 *
	 * @throws Exception
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public static function renderAtom (
		string $handle,
		array $variables = [],
		string $children = null
	) : void {
		$view = Craft::$app->getView();

		$template = self::$_config['atoms'] . '/' . $handle;

		$variables['children'] = new Markup($children, 'utf8');

		if (!$view->doesTemplateExist($template))
		{
			Craft::error(
				"Error locating template: {$template}",
				__METHOD__
			);

			return;
		}

		echo $view->renderTemplate($template, $variables);
	}

}
