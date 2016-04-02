<?php
/**
 * @package     smz_navabr
 * @copyright   Copyright (C) 2014 - 2016 Sergio Manzi. All rights reserved.
 * @license     GNU General Public License (GNU GPL) Version 3; See http://www.gnu.org/licenses/gpl.html
 *
 * Part of this code might be Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_smz_navbar
 *
 */
class ModSMZNavbarHelper
{
	/**
	 * Get a list of the menu items.
	 *
	 * @param   \Joomla\Registry\Registry  &$params  The module options.
	 *
	 * @return  array
	 *
	 * @since   1.5
	 */
	public static function getList(&$params)
	{
		$app = JFactory::getApplication();
		$menu = $app->getMenu();

		// Get active menu item
		$base = self::getBase($params);
		$user = JFactory::getUser();
		$levels = $user->getAuthorisedViewLevels();
		asort($levels);

		$key = 'menu_items' . $params . implode(',', $levels) . '.' . $base->id;
		$cache = JFactory::getCache('mod_smz_navbar', '');

		if (!($items = $cache->get($key)))
		{
			$path    = $base->tree;
			$start   = (int) $params->get('startLevel');
			$end     = (int) $params->get('endLevel');
			$showAll = $params->get('showAllChildren');
			$items   = $menu->getItems('menutype', $params->get('menutype'));

			$lastitem = 0;

			if ($items)
			{
				foreach ($items as $i => $item)
				{
					// Start handle #hidden/#hiding items
					$items[$i]->hidden = false;
					$items[$i]->hiding = false;

					$parent = $menu->getItem($item->parent_id);

					// If the parent is hidden or hiding, set this item as hidden 
					if(isset($parent) && ($parent->hidden || $parent->hiding))
					{
						$items[$i]->hidden = true;
						continue;
					}

					$anchor_css = $item->params->get('menu-anchor_css', '');

					// If CSS class has #hidden, set the hiding property
					if (strpos($anchor_css, '#hidden') !== false)
					{
						$items[$i]->hidden = true;
					}

					// If CSS class has #hiding, set the hidinng property, remove #hiding from the CSS class and continue processing.
					if (strpos($anchor_css, '#hiding') !== false)
					{
						$items[$i]->hiding = true;
						$item->params->set('menu-anchor_css', str_replace('#hiding', '', $anchor_css));
					}
					// End handle #hidden/#hiding items

					if (($start && $start > $item->level)
						|| ($end && $item->level > $end)
						|| (!$showAll && $item->level > 1 && !in_array($item->parent_id, $path))
						|| ($start > 1 && !in_array($item->tree[$start - 2], $path)))
					{
						unset($items[$i]);
						continue;
					}

					$item->deeper     = false;
					$item->shallower  = false;
					$item->level_diff = 0;

					if (isset($items[$lastitem]))
					{
						$items[$lastitem]->deeper     = ($item->level > $items[$lastitem]->level);
						$items[$lastitem]->shallower  = ($item->level < $items[$lastitem]->level);
						$items[$lastitem]->level_diff = ($items[$lastitem]->level - $item->level);
					}

					$item->parent = (boolean) $menu->getItems('parent_id', (int) $item->id, true);

					$lastitem     = $i;
					$item->active = false;
					$item->flink  = $item->link;

					// Reverted back for CMS version 2.5.6
					switch ($item->type)
					{
						case 'separator':
						case 'heading':
							// No further action needed.
							continue;

						case 'url':
							if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false))
							{
								// If this is an internal Joomla link, ensure the Itemid is set.
								$item->flink = $item->link . '&Itemid=' . $item->id;
							}
							break;

						case 'alias':
							$item->flink = 'index.php?Itemid=' . $item->params->get('aliasoptions');
							break;

						default:
							$item->flink = 'index.php?Itemid=' . $item->id;
							break;
					}

					if (strcasecmp(substr($item->flink, 0, 4), 'http') && (strpos($item->flink, 'index.php?') !== false))
					{
						$item->flink = JRoute::_($item->flink, true, $item->params->get('secure'));
					}
					else
					{
						$item->flink = JRoute::_($item->flink);
					}

					// We prevent the double encoding because for some reason the $item is shared for menu modules and we get double encoding
					// when the cause of that is found the argument should be removed
					$item->title        = htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false);
					$item->anchor_css   = htmlspecialchars($item->params->get('menu-anchor_css', ''), ENT_COMPAT, 'UTF-8', false);
					$item->anchor_title = htmlspecialchars($item->params->get('menu-anchor_title', ''), ENT_COMPAT, 'UTF-8', false);
					$item->menu_image   = $item->params->get('menu_image', '') ?
						htmlspecialchars($item->params->get('menu_image', ''), ENT_COMPAT, 'UTF-8', false) : '';
				}

				if (isset($items[$lastitem]))
				{
					$items[$lastitem]->deeper     = (($start?$start:1) > $items[$lastitem]->level);
					$items[$lastitem]->shallower  = (($start?$start:1) < $items[$lastitem]->level);
					$items[$lastitem]->level_diff = ($items[$lastitem]->level - ($start?$start:1));
				}
			}

			$cache->store($items, $key);
		}
		return $items;
	}

	/**
	 * Get base menu item.
	 *
	 * @param   \Joomla\Registry\Registry  &$params  The module options.
	 *
	 * @return  object
	 *
	 * @since	3.0.2
	 */
	public static function getBase(&$params)
	{
		// Get base menu item from parameters
		if ($params->get('base'))
		{
			$base = JFactory::getApplication()->getMenu()->getItem($params->get('base'));
		}
		else
		{
			$base = false;
		}

		// Use active menu item if no base found
		if (!$base)
		{
			$base = self::getActive($params);
		}

		return $base;
	}

	/**
	 * Get active menu item.
	 *
	 * @param   \Joomla\Registry\Registry  &$params  The module options.
	 *
	 * @return  object
	 *
	 * @since	3.0.2
	 */
	public static function getActive(&$params)
	{
		$menu = JFactory::getApplication()->getMenu();
		$lang = JFactory::getLanguage();

		// Look for the home menu
		if (JLanguageMultilang::isEnabled())
		{
			$home = $menu->getDefault($lang->getTag());
		}
		else
		{
			$home  = $menu->getDefault();
		}

		return $menu->getActive() ? $menu->getActive() : $home;
	}

	/**
	 * Gets a list of available languages
	 *
	 * @param   \Joomla\Registry\Registry  &$params  module params
	 *
	 * @return  array
	 */
	public static function getLanguages(&$params)
	{
		JLoader::register('MultilangstatusHelper', JPATH_ADMINISTRATOR . '/components/com_languages/helpers/multilangstatus.php');

		// Setup data.
		$app = JFactory::getApplication();
		$languages = JLanguageHelper::getLanguages('lang_code');
		$home_pages = MultilangstatusHelper::getHomepages();
		$levels = JFactory::getUser()->getAuthorisedViewLevels();
		$site_langs = MultilangstatusHelper::getSitelangs();
		$current_lang = JFactory::getLanguage()->getTag();
		$associations = array();
		$cassociations = array();

		// Check language access, language is enabled, language folder exists, and language has an Home Page
		foreach ($languages as $lang_code => $language)
		{
			if (($language->access && !in_array($language->access, $levels))
				|| !array_key_exists($lang_code, $site_langs)
				|| !is_dir(JPATH_SITE . '/language/' . $lang_code)
				|| !isset($home_pages[$lang_code]))
			{
				unset($languages[$lang_code]);
			}
		}

		// Get active menu item and check if we are on an home page
		$menu = $app->getMenu();
		$active = $menu->getActive();

		// Load associations
		if (JLanguageAssociations::isEnabled())
		{
			// Load menu associations
			if ($active)
			{
				$associations = MenusHelper::getAssociations($active->id);
			}

			// Load component associations
			$option = $app->input->get('option');
			$class = ucfirst(str_ireplace('com_', '', $option)) . 'HelperAssociation';
			$cassoc_func = array($class, 'getAssociations');
			JLoader::register($class, JPath::clean(JPATH_COMPONENT_SITE . '/helpers/association.php'));
			if (class_exists($class) && is_callable($cassoc_func))
			{
				$cassociations = call_user_func($cassoc_func);
			}
		}

		// For each language...
		foreach ($languages as $i => &$language)
		{
			$language->active = false;
			switch (true)
			{
				// Current language link
				case ($i == $current_lang):
					$language->link = str_replace('&', '&amp;', JUri::getInstance()->toString(array('path', 'query')));
					$language->active = true;
					break;

				// Component association
				case (isset($cassociations[$i])):
					$language->link = JRoute::_($cassociations[$i]);
					break;

				// Menu items association
				// Heads up! "$item = $menu" here below is an assignment, *NOT* comparison
				case (isset($associations[$i]) && ($item = $menu->getItem($associations[$i]))):
					$language->link = JRoute::_($item->link . '&Itemid=' . $item->id);
					break;

				// No association found
				default:
					$language->link = JRoute::_('index.php?lang=' . $language->sef);
			}
		}
		return $languages;
	}
}
