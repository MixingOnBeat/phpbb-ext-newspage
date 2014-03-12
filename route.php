<?php

/**
 *
 * @package NV Newspage Extension
 * @copyright (c) 2014 nickvergessen
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace nickvergessen\newspage;

class route
{
	/**
	 * Controller helper object
	 * @var \phpbb\controller\helper
	 */
	protected $helper;

	protected $page;
	protected $category;
	protected $archive_year;
	protected $archive_month;

	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper		$helper		Controller helper object
	 * @param \phpbb\config\config			$config		Config object
	 */
	public function __construct(\phpbb\controller\helper $helper, \phpbb\config\config $config)
	{
		$this->helper = $helper;
		$this->config = $config;
	}

	/**
	 * @param mixed $archive_month
	 * @return $this
	 */
	public function set_archive_month($archive_month)
	{
		if ($this->config['news_archive_show'])
		{
			$this->archive_month = sprintf('%02d', (int) $archive_month);
		}
		return $this;
	}

	/**
	 * @param mixed $archive_year
	 * @return $this
	 */
	public function set_archive_year($archive_year)
	{
		if ($this->config['news_archive_show'])
		{
			$this->archive_year = $archive_year;
		}
		return $this;
	}

	/**
	 * @param mixed $category
	 * @return $this
	 */
	public function set_category($category)
	{
		if ($this->config['news_cat_show'])
		{
			$this->category = $category;
		}
		return $this;
	}

	/**
	 * @param mixed $page
	 * @return $this
	 */
	public function set_page($page)
	{
		$this->page = $page;
		return $this;
	}

	/**
	 * Generate the pagination for the news list
	 *
	 * @param	mixed	$force_category		Overwrites the category, false for disabled, integer otherwise
	 * @param	mixed	$force_archive		Overwrites the archive, false for disabled, string otherwise
	 * @param	mixed	$force_page			Overwrites the page, false for disabled, string otherwise
	 * @return		string		Full URL with append_sid performed on it
	 */
	public function get_url($force_category = false, $force_archive = false, $force_page = false)
	{
		return $this->helper->route(
			$this->get_route($force_category, $force_archive, $force_page),
			$this->get_params($force_category, $force_archive, $force_page)
		);
	}

	/**
	 * Returns the name of the route we should use
	 *
	 * @param	mixed	$force_category		Overwrites the category, false for disabled, integer otherwise
	 * @param	mixed	$force_archive		Overwrites the archive, false for disabled, string otherwise
	 * @param	mixed	$force_page			Overwrites the page, false for disabled, string otherwise
	 * @return		string
	 */
	public function get_route($force_category = false, $force_archive = false, $force_page = false)
	{
		$route = 'newspage';
		if ($this->config['news_cat_show'] && ($force_category || $this->category))
		{
			$route .= '_category';
		}
		if ($this->config['news_archive_show'] && ($force_archive || ($this->archive_year && $this->archive_month)))
		{
			$route .= '_archive';
		}
		if ($force_page)
		{
			$route .= '_page';
		}

		return $route . '_controller';
	}

	/**
	 * Returns the list of parameters of the route we should use
	 *
	 * @param	mixed	$force_category		Overwrites the category, false for disabled, integer otherwise
	 * @param	mixed	$force_archive		Overwrites the archive, false for disabled, string otherwise
	 * @param	mixed	$force_page			Overwrites the page, false for disabled, string otherwise
	 * @return		array
	 */
	public function get_params($force_category = false, $force_archive = false, $force_page = false)
	{
		$params = array();
		if ($this->config['news_cat_show'] && $force_category)
		{
			$params['forum_id'] = $force_category;
		}
		else if ($this->config['news_cat_show'] && $this->category)
		{
			$params['forum_id'] = $this->category;
		}
		if ($this->config['news_archive_show'] && $force_archive)
		{
			list($year, $month) = explode('/', $force_archive, 2);
			$params['year'] = $year;
			$params['month'] = $month;
		}
		else if ($this->config['news_archive_show'] && $this->archive_year && $this->archive_month)
		{
			$params['year'] = $this->archive_year;
			$params['month'] = $this->archive_month;
		}
		if ($force_page)
		{
			$params['page'] = $force_page;
		}

		return $params;
	}
} 