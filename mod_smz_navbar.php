<?php
/**
 * @package     smz_navabr
 * @copyright   Copyright (C) 2014 - 2016 Sergio Manzi. All rights reserved.
 * @license     GNU General Public License (GNU GPL) Version 3; See http://www.gnu.org/licenses/gpl.html
 *
 * Part of this code might be Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 */

// No direct access.
defined('_JEXEC') or die;

// Load Stylesheet.
JHtml::stylesheet('mod_smz_navbar/mod_smz_navbar.css', false, true, false);

// Include the helper functions.
require_once __DIR__ . '/helper.php';

// Get the menu items.
$list = ModSMZNavbarHelper::getList($params);

if (count($list))
{
	$base = ModSMZNavbarHelper::getBase($params);
	$active = ModSMZNavbarHelper::getActive($params);
	$active_id = $active->id;
	$path = $base->tree;
	$ulclass = '';
	$dropdown = false;
	$pills = false;
	$subcarets = $params->get('bsShowCaretsSub');

	switch ($params->get('navbarStyle', 'nav-pills-dropdown'))
	{
		case 'nav-pills-dropdown': // Navbar with pills and dropdowns
			$pills = true;
			$dropdown = true;
			$ulclass = ' nav-pills';
			break;
		case 'nav-pills': // Basic navbar with pills
			$pills = true;
			$ulclass = ' nav-pills';
			break;
		case 'nav-dropdown': // Navbar with dropdowns
			$dropdown = true;
			break;
		case 'nav': // Basic navbar
			break;
		default:
			break;
	}

	if ($dropdown && $params->get('openDropDownsOnHover'))
	{
		// Load the "Open Dropdowns on Hover" stylesheet
		JHtml::stylesheet('mod_smz_navbar/mod_smz_navbar_dropdown.css', false, true, false);
	}

	if ($params->get('fullNavbar'))
	{
		$nav_id = trim($params->get('div_id', ''));
		if ($nav_id != '')
		{
			$nav_id = " id='{$nav_id}'";
		}

		$navclass_sfx = htmlspecialchars(trim($params->get('navclass_sfx'), ''));
		if ($navclass_sfx != '')
		{
			$navclass_sfx = ' ' . $navclass_sfx;
		}

		if ($params->get('navbarVariation'))
		{
			$navclass_sfx = ' navbar-inverse' . $navclass_sfx;
		}

		echo "<nav{$nav_id} class='navbar{$navclass_sfx}'>";
		echo "<div class='navbar-inner'>";

		if ($params->get('useSitenameForBrand'))
		{
			$navBrand = JFactory::getConfig()->get( 'sitename' );
		}
		else
		{
			$navBrand = trim($params->get('navBrand', ''));
		}
		if ($navBrand)
		{
			echo "<a class='brand' href='/'>{$navBrand}</a>";
		}
		echo "<button type='button' class='btn btn-navbar' data-toggle=\"collapse\" data-target='#navbar{$module->id}'>";
		echo "<span class='icon-bar'></span>";
		echo "<span class='icon-bar'></span>";
		echo "<span class='icon-bar'></span>";
		echo "</button>";
		echo "<div class='nav-collapse collapse' id='navbar{$module->id}'>";
	}

	$ul_id = trim($params->get('ul_id'), '');
	if ($ul_id != '')
	{
		$ul_id = " id='{$ul_id}'";
	}


	// The Navigation bar
	echo "<ul{$ul_id} class='nav{$ulclass}'>";

	// The nav
	foreach ($list as $i => &$item)
	{
		if($item->hidden)
		{
			continue;
		}

		$class = '';
		if ($item->type != 'component')
		{
			$class = $item->type;
		}

		if ($item->deeper)
		{
			if ($dropdown)
			{
				if ($item->level == 1)
				{
					$class .= ' dropdown';
				}
				else
				{
					$class .= ' dropdown-submenu';
				}
			}
			else
			{
				$class .= ' deeper';
			}
		}

		if ($item->id == $active_id)
		{
			$class .= ' current';
		}

		if ($item->parent && ! $item->hiding)
		{
			$class .= ' parent';
		}

		if ($item->type == 'alias' &&	in_array($item->params->get('aliasoptions'),$path)	||	in_array($item->id, $path))
		{
			$class .= ' active';
		}

		// Handle boostrap-style separators
		if ($item->type == 'separator' && $params->get('separatorsStyle'))
		{
			if ($item->level == 1)
			{
				$class .= ' divider-vertical';
			}
			else
			{
				$class .= ' divider';
			}
		}

		$class = trim($class);
		if ($class !== '')
		{
			$class = " class='{$class}'";
		}
		echo "<li{$class}>";

		// Build up $text
		if ($item->menu_image)
		{
			$text = "<img src='{$item->menu_image}' alt='{$item->title}' />";
			if ($item->params->get('menu_text', 1))
			{
				$text = $text . "<span class='image-title'>{$item->title}</span>";
			}
		}
		else
		{ 
			$text = $item->title;
		}

		// Write the <li> for the specific menu item type (all the same beside separator...)
		switch ($item->type)
		{
			case 'separator':
				if ($params->get('separatorsStyle') == 0)
				{

					echo "<span class='separator'>{$text}</span>";
				}
				break;
//			case 'heading':
//			case 'component':
//			case 'url':
			default:
				// Add icon to text if required
				if ($params->get('showIcons') && $item->note)
				{
					$icon = '<i class="' . trim($item->note);
					if ($params->get('iconsStyle') == 0)
					{
						$icon .= ' inverted';
					}
					$icon .= '"></i> ';
					$text = $icon . $text;
				}

				// Add carets if required
				if ($dropdown && $item->deeper && $item->level == 1)
				{ 
					$text = $text . "<b class='caret'></b>" ;
				}

				// Build up $link
				if ($item->type == 'heading')
				{
					$link = '#';
				}
				else
				{
					$link = $item->flink;
				}

				// Build up $class
				$class = empty($item->anchor_css) ? '' : $item->anchor_css;

				// Build up $datatoggle for dropdowns
				$datatoggle = '';
				if ($dropdown && $item->deeper && !$item->hiding)
				{
					$class = trim('dropdown-toggle ' . $class);
					$datatoggle = " data-toggle='dropdown'";
				}

				// Finsish building up $class
				if ($class !== '')
				{
					$class = " class='{$class}'";
				}

				// Build up $title
				$title = empty($item->anchor_title) ? '' : ' title="' . $item->anchor_title . '"';

				// Build up $options
				switch ($item->browserNav)
				{
					case 1: // _blank
						$options = " target='_blank'";
						break;
					case 2: // Use JavaScript "window.open"
						$options = " onclick=\"window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');return false;\"";
						break;
					default:
						$options = '';
						break;
				}

				echo "<a href='{$link}'{$class}{$datatoggle}{$title}{$options}>{$text}</a>";
				break;
		}

		// Close with this item and prepare for next
		switch (true)
		{
			case $item->deeper && $dropdown :
				echo '<ul class="dropdown-menu">';
				break;
			case $item->deeper :
				echo '<ul>';
				break;
			case $item->shallower :
				echo '</li>';
				echo str_repeat('</ul></li>', $item->level_diff);
				break;
			default:
				echo '</li>';
		}

	}

	echo '</ul>';


	// The language selector
	if ($params->get('showLanguageSelector'))
	{
		$languages = ModSMZNavbarHelper::getLanguages($params);
		if (count($languages) > 1)
		{
			$languageSelectorMode = $params->get('languageSelectorMode');
			$class = "class='nav " . ($pills ? 'nav-pills ' : '') . "language-selector'";
			echo "<ul {$class}>";
				if ($languageSelectorMode == 'dropdown')
				{
					echo "<li class='dropdown parent'><a href='#' class='dropdown-togle' data-toggle='dropdown'>";
					echo JText::_('MOD_SMZ_NAVBAR_SELECT_LANGUAGE');
					echo "<b class='caret'></b></a>" ;
					echo "<ul class='dropdown-menu'>";
				}
				foreach ($languages as $language)
				{
					$class = 'lang-' . strtolower($language->sef);
					$class .= $language->active ? ' active' : '';
					$rtl = JLanguage::getInstance($language->lang_code)->isRTL() ? 'rtl' : 'ltr';
					$language_text = '';
					if ($params->get('languageSelectorStyle', 'flags') != 'names')
					{
						$language_text .= "<span class='language-flag'>";
						$language_text .= JHtml::image('mod_languages/' . $language->image . '.gif', $language->title_native, array('title' => $language->title_native), true);
						$language_text .= '</span>';
					}
					if ($params->get('languageSelectorStyle', 'flags') != 'flags')
					{
						$language_text .= "<span class='language-name'>" . ($params->get('languageSelectorNames') ? $language->title_native : strtoupper($language->sef)) . '</span>';
					}
					echo "<li class='{$class}' dir='{$rtl}'><a href='{$language->link}'>{$language_text}</a></li>";
				}
				if ($languageSelectorMode == 'dropdown')
				{
					echo '</ul></li>';
				}
			echo '</ul>';
		}
	}


	// Close down
	if ($params->get('fullNavbar'))
	{
		echo '</div></div></nav>';
	}
}
