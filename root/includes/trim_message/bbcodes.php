<?php

/**
* This file contains a class, to manage the bbcodes of a given phpbb
* message_parser message.
*
* @author     Joas Schilling	<nickvergessen at gmx dot de>
* @package    trim_message
* @copyright  2011
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    1.0
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* phpbb_trim_message_bbcodes class
*/
class phpbb_trim_message_bbcodes
{
	/**
	* Variables
	*/
	private $message			= '';
	private $bbcode_uid			= '';
	private $bbcode_list		= array();
	private $array_size			= 0;

	public  $trim_position		= 0;

	/**
	* Constructor
	*
	* @param string	$message		parsed message you want to trim
	* @param string	$bbcode_uid		bbcode_uid of the post
	*/
	public function __construct($message, $bbcode_uid)
	{
		$this->message			= $message;
		$this->bbcode_uid		= $bbcode_uid;
		$this->array_size		= 0;
	}

	public function get_bbcodes()
	{
		$bbcode_end_length = utf8_strlen(':' . $this->bbcode_uid . ']');
		$quote_end_length = utf8_strlen('&quot;:' . $this->bbcode_uid . ']');

		$text_position = 0;
		$possible_bbcodes = explode('[', $this->message);
		$text_position = utf8_strlen($possible_bbcodes[0]);

		// Skip the first one.
		array_shift($possible_bbcodes);
		$num_possible_bbcodes	= sizeof($possible_bbcodes);
		$num_tested_bbcodes		= 0;
		$start_of_last_part		= 0;

		$allow_close_quote = false;

		foreach ($possible_bbcodes as $part)
		{
			$num_tested_bbcodes++;
			$exploded_parts = explode(':' . $this->bbcode_uid . ']', $part);
			$num_parts = sizeof($exploded_parts);


			/**
			* One element means we do not match an end before the next opening:
			* String: [quote="[bbcode:uid]foobar[/bbcode:uid]":uid]
			* Keys:    ^^^^^^^ = 0
			*/
			if ($num_parts == 1)
			{
				// 1 means, we are in [quote="":uid] and found another bbcode here.
				if (utf8_strpos($exploded_parts[0], 'quote=&quot;') === 0)
				{
					$open_end_quote = utf8_strpos($this->message, '&quot;:' . $this->bbcode_uid . ']', $text_position);
					if ($open_end_quote !== false)
					{
						$close_quote = utf8_strpos($this->message, '[/quote:' . $this->bbcode_uid . ']', $open_end_quote);
						if ($close_quote !== false)
						{
							$open_end_quote += $quote_end_length;
							$this->open_bbcode('quote', $text_position);
							$this->bbcode_action('quote', 'open_end', $open_end_quote);
							$text_position += utf8_strlen($exploded_parts[0]);

							// We allow the 3-keys special-case, when we have found a beginning before...
							$allow_close_quote = true;
						}
					}
				}
			}
			/**
			* Two element is hte normal case:
			* String: [bbcode:uid]foobar
			* Keys:    ^^^^^^ = 0 ^^^^^^ = 1
			* String: [/bbcode:uid]foobar
			* Keys:    ^^^^^^^ = 0 ^^^^^^ = 1
			*/
			elseif ($num_parts == 2)
			{
				// We matched it something ;)
				if ($exploded_parts[0][0] != '/')
				{
					$bbcode_tag = $exploded_parts[0];
					// Open BBCode-tag
					if (($equals = utf8_strpos($bbcode_tag, '=')) !== false)
					{
						$bbcode_tag = utf8_substr($bbcode_tag, 0, $equals);
					}
					$this->open_bbcode($bbcode_tag, $text_position);
					$text_position += utf8_strlen($exploded_parts[0]) + $bbcode_end_length;
					$this->bbcode_action($bbcode_tag, 'open_end', $text_position);
					$text_position += utf8_strlen($exploded_parts[1]);
				}
				else
				{
					// Close BBCode-tag
					$bbcode_tag = utf8_substr($exploded_parts[0], 1);

					$this->bbcode_action($bbcode_tag, 'close_start', $text_position);
					$text_position += utf8_strlen($exploded_parts[0]) + $bbcode_end_length;
					$this->bbcode_action($bbcode_tag, 'close_end', $text_position);
					$text_position += utf8_strlen($exploded_parts[1]);
				}
			}
			/**
			* Three elements means are closing the opening-quote and the BBCode from inside:
			* String: [quote="[bbcode:uid]foo[/bbcode:uid]bar":uid]quotehere
			* Keys:                           ^^^^^^^ = 0 ^^^^ = 1 ^^^^^^^^^ = 2
			*/
			elseif ($num_parts == 3)
			{
				if (($exploded_parts[0][0] == '/') && (utf8_substr($exploded_parts[1], -6) == '&quot;') && $allow_close_quote)
				{
					$bbcode_tag = utf8_substr($exploded_parts[0], 1);

					$this->bbcode_action($bbcode_tag, 'close_start', $text_position);
					$text_position += utf8_strlen($exploded_parts[0]) + $bbcode_end_length;
					$this->bbcode_action($bbcode_tag, 'close_end', $text_position);
					$text_position += utf8_strlen($exploded_parts[1]) + $bbcode_end_length;
					$text_position += utf8_strlen($exploded_parts[2]);

					$allow_close_quote = false;
				}
			}

			// Increase by one for the [ we explode on.
			$text_position++;
		}
	}

	/**
	* Add a bbcode to the bbcode-list
	*
	* @param	string	$tag			BBCode-tag, Exp: code
	* @param	int		$open_start		start-position of the bbcode-open-tag
	*									(Exp: >[<code]) in the message
	*/
	private function open_bbcode($tag, $open_start)
	{
		$this->bbcode_list[] = array(
			'bbcode_tag'	=> $tag,
			'open_start'	=> $open_start,
			'open_end'		=> 0,
			'close_start'	=> 0,
			'close_end'		=> 0,
		);
		$this->array_size++;
	}

	/**
	* Add position to a listed bbcode
	*
	* @param	string	$tag		BBCode-tag, Exp: code
	* @param	string	$part		part can be one of the following:
	*								i)   open_end	=> [code>]<[/code]
	*								ii)  close_open	=> [code]>[</code]
	*								iii) close_end	=> [code][/code>]<
	* @param	int		$position	start-position of the bbcode-open-tag
	*/
	private function bbcode_action($tag, $part, $position)
	{
		for ($i = 1; $i <= $this->array_size; $i++)
		{
			if ($this->bbcode_list[$this->array_size - $i]['bbcode_tag'] == $tag)
			{
				if (!$this->bbcode_list[$this->array_size - $i][$part])
				{
					$this->bbcode_list[$this->array_size - $i][$part] = $position;
					return;
				}
			}
		}
	}

	/**
	* Removes all BBcodes after a given position
	*
	* @param	int	$position	position where we trim the message
	*
	* @return	int	Returns the new trim-position, so we do not cut inside of
	*				a bbcode-tag. Exp: [co{cut}de]
	*						Returns: >x<
	*/
	public function remove_bbcodes_after($position)
	{
		$this->trim_position		= $position;

		for ($i = 1; $i <= $this->array_size; $i++)
		{
			if ($this->bbcode_list[$this->array_size - $i]['open_start'] >= $position)
			{
				unset($this->bbcode_list[$this->array_size - $i]);
			}
			else
			{
				if (($this->bbcode_list[$this->array_size - $i]['open_start'] < $position) &&
				 ($this->bbcode_list[$this->array_size - $i]['open_end'] >= $position) &&
				 ($this->trim_position > $this->bbcode_list[$this->array_size - $i]['open_start']))
				{
					$this->trim_position		= $this->bbcode_list[$this->array_size - $i]['open_start'];
				}
				else if (($this->bbcode_list[$this->array_size - $i]['close_start'] < $position) &&
				 ($this->bbcode_list[$this->array_size - $i]['close_end'] >= $position) &&
				 ($this->trim_position > $this->bbcode_list[$this->array_size - $i]['close_start']))
				{
					$this->trim_position		= $this->bbcode_list[$this->array_size - $i]['close_start'];
				}
			}
		}

		$this->array_size = sizeof($this->bbcode_list);
	}

	/**
	* Returns an array with BBCodes that need to be closed, after the position.
	*/
	public function get_open_bbcodes_after($position)
	{
		$bbcodes = array();
		for ($i = 1; $i <= $this->array_size; $i++)
		{
			if (($this->bbcode_list[$this->array_size - $i]['open_start'] < $position) &&
				 ($this->bbcode_list[$this->array_size - $i]['close_start'] >= $position))
			{
				$bbcodes[] = $this->bbcode_list[$this->array_size - $i]['bbcode_tag'];
			}
		}
		return $bbcodes;
	}
}
