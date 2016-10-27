<?php

class Plugin_Parent extends Plugin
{
	function __call($method, $args)
	{
		$parent = $this->getParent();

		return array_get($parent, $method);
	}

	/**
	 * Retrieve the parent page
	 * @return Array Array containing the content of the parent page
	 */
	private function getParent() {
		$parent = URL::assemble(URL::popLastSegment(URL::getCurrent()));

		if (Taxonomy::isTaxonomyURL($parent)) {
			$parent = URL::popLastSegment($parent);
		}
		
		return Content::get($parent);
	}
}