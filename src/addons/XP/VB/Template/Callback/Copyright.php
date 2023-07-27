<?php

namespace XP\VB\Template\Callback;

class Copyright
{
	/**
	 * @return string
	 */
	public static function getCopyrightText()
	{
		/** @var \XF\App $app */
		$app = \XF::app();
		
		$branding = $app->offsetExists('xp_branding') ? $app->xp_branding : [];
		
		if (!count($branding) OR !is_array($branding))
		{
			// We had nothing left, another Xen-Pro mod would have done it
			return '';
		}
		
		$brandingVariables = [
			'utm_source' 		=> str_replace('www.', '', htmlspecialchars(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'CLI')),
			'utm_content' 		=> 'footer',
		];
		
		// Create this long string
		$html = '<div class="xp-copyright">
			Parts of this site powered by <a class="u-concealed" rel="nofollow noopener" href="https://xen-pro.com/store/?utm_source=' . $brandingVariables['utm_source'] . '&utm_campaign=site&utm_medium=footer&utm_content=' . $brandingVariables['utm_content'] . '" target="_blank">XenForo add-ons by Dadparvar&#8482;</a>
			&copy;2011-' . date('Y') . ' <a class="u-concealed" rel="nofollow noopener" href="https://xen-pro.com/?utm_source=' . $brandingVariables['utm_source'] . '&utm_campaign=site&utm_medium=footer&utm_content=' . $brandingVariables['utm_content'] . '" target="_blank">Xen-Pro</a>
			(<a class="u-concealed" rel="nofollow noopener" href="https://xen-pro.com/store/details/?products=' . implode(',', $branding) . '&utm_source=' . $brandingVariables['utm_source'] . '&utm_campaign=product&utm_medium=footer&utm_content=' . $brandingVariables['utm_content'] . '" target="_blank">Details</a>)
		</div>';
		
		// Make sure we null this out
		$app->xp_branding = [];
		
		return $html;
	}
}