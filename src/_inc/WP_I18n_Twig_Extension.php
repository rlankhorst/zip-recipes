<?php
/**
 * Created by PhpStorm.
 * User: gezimhome
 * Date: 2016-06-05
 * Time: 11:27 AM
 */

namespace ZRDN;

class TransNode extends \Twig_Node
{
	public function __construct($string, $domain, $lineno = 0, $tag = null)
	{
		parent::__construct(array(), array('string' => $string, 'domain' => $domain), $lineno, $tag);
	}

	/**
	 * Compiles the node to PHP.
	 *
	 * @param \Twig_Compiler $compiler A Twig_Compiler instance
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler->addDebugInfo($this);

		//$vars = $this->getNode('vars');
		$domain = $this->getAttribute('domain');
		$string = $this->getAttribute('string');
		$compiler
			->write("echo __('$string','$domain')")
			->raw("\n;");
	}
}

/**
 * Token Parser for the 'trans' tag.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TransTokenParser extends \Twig_TokenParser
{
	/**
	 * Parses a token and returns a node.
	 *
	 * @param \Twig_Token $token A Twig_Token instance
	 *
	 * @return \Twig_Node A Twig_Node instance
	 *
	 * @throws \Twig_Error_Syntax
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		$string = $stream->expect(\Twig_Token::STRING_TYPE)->getValue();
		$delim = $stream->expect(\Twig_Token::NAME_TYPE);
		$domain = $stream->expect(\Twig_Token::STRING_TYPE)->getValue();

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new TransNode($string, $domain, $lineno, $this->getTag());
	}

	/**
	 * Gets the tag name associated with this token parser.
	 *
	 * @return string The tag name
	 */
	public function getTag()
	{
		return 'trans';
	}
}


class WP_I18n_Twig_Extension extends \Twig_Extension {
	public function getTokenParsers()
	{
		return array(new TransTokenParser());
	}

	public function getName()
	{
		return 'WP_I18n_Twig_Extension';
	}
}

