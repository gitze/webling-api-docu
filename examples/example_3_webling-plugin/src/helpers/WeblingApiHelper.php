<?php

use Webling\CacheAdapters\WordpressCacheAdapter;
use Webling\Cache\Cache;

/**
 * Singleton class
 *
 */
class WeblingApiHelper {

	private $client;

	private $cache;

	private $cacheAdapter;

	public static $invisibleFields = ['file'];

	public static $immutableFields = ['autoincrement'];

	/**
	 * Call this method to get singleton
	 *
	 * @return WeblingApiHelper
	 */
	public static function Instance() {
		static $inst = null;
		if ($inst === null) {
			$inst = new WeblingApiHelper();
		}
		return $inst;
	}

	/**
	 * Private constructor so nobody else can instance it
	 *
	 */
	private function __construct() {
		$options = get_option('webling-options', array());
		if (!isset($options['host'])) {
			$options['host'] = '';
		}
		if (!isset($options['apikey'])) {
			$options['apikey'] = '';
		}
		$this->client = new \Webling\API\Client($options['host'], $options['apikey'], array('useragent' => $this->getUserAgent()));
		$this->cacheAdapter = new WordpressCacheAdapter();
		$this->cache = new Cache($this->client, $this->cacheAdapter);
	}

	/**
	 * @return \Webling\API\Client
	 */
	public function client() {
		return $this->client;
	}

	/**
	 * @return Cache
	 */
	public function cache() {
		return $this->cache;
	}

	/**
	 * @return WordpressCacheAdapter
	 */
	public function cacheAdapter() {
		return $this->cacheAdapter;
	}

	public function clearCache() {
		$this->cache->clearCache();
	}

	public function hasMemberReadAccess() {
		// check if /membergroup has root objects
		$rootmembergroup = $this->cache->getRoot('membergroup');
		if (isset($rootmembergroup['roots']) && is_array($rootmembergroup['roots']) && count($rootmembergroup['roots']) > 0) {
			return true;
		}
		return false;
	}

	public function hasMemberWriteAccess() {
		// check if any of the membergroups is writeable
		$tree = $this->getMembergroupTree();
		$writeable_count = 0;
		array_walk_recursive($tree, function($value, $key) use (&$writeable_count) {
			if ($key == 'writeable' && $value === true) {
				$writeable_count++;
			}
		});
		if ($writeable_count > 0) {
			return true;
		}
		return false;
	}

	public function getMembergroupTree() {
		$rootmembergroup = $this->cache->getRoot('membergroup');
		$roots = array();
		if (is_array($rootmembergroup['roots'])) {
			// cache objects
			$this->cache->getObjects('membergroup', $rootmembergroup['roots']);

			// build tree
			foreach ($rootmembergroup['roots'] as $rootGroupId) {
				$roots[$rootGroupId] = array(
					'title' => $this->getObjectLabel('membergroup', $rootGroupId),
					'writeable' => $this->objectIsWriteable('membergroup', $rootGroupId),
					'childs' => $this->recursiveMembergroupChilds($rootGroupId)
				);
			}
		}
		return $roots;
	}

	public function getSavedSearches() {
		// these will always load without caching
		$response = $this->client->get('/template?filter=templatetype%3D%22memberSearch%22&order=title%20asc');
		$savedsearches = array();
		if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
			$templateIds = $response->getData();
			if (is_array($templateIds['objects'])) {
				// cache objects
				$this->cache->getObjects('template', $templateIds['objects']);
				foreach ($templateIds['objects'] as $id) {
					$savedsearches[$id] = $this->getObjectLabel('template', $id);
				}
			}
		}
		return $savedsearches;
	}

	public function getMemberFields() {
		$definitions = $this->cache->getRoot('definition');
		$properties = array();
		if (isset($definitions['member']['properties'])) {
			foreach ($definitions['member']['properties'] as $propertyName => $propertyConf) {
				// compatibility for older api versions
				if (isset($propertyConf['title'])) {
					$propertyName = $propertyConf['title'];
				}

				$properties[$propertyConf['id']] = $propertyName;
			}
		}
		return $properties;
	}

	public function getVisibleMemberFields() {
		$definitions = $this->cache->getRoot('definition');
		$properties = array();
		if (isset($definitions['member']['properties'])) {
			foreach ($definitions['member']['properties'] as $propertyName => $propertyConf) {
				if (!in_array($propertyConf['datatype'], self::$invisibleFields)) {
					// compatibility for older api versions
					if (isset($propertyConf['title'])) {
						$propertyName = $propertyConf['title'];
					}

					$properties[$propertyConf['id']] = $propertyName;
				}
			}
		}
		return $properties;
	}

	public function getMutableMemberFields() {
		$definitions = $this->cache->getRoot('definition');
		$properties = array();
		if (isset($definitions['member']['properties'])) {
			foreach ($definitions['member']['properties'] as $propertyName => $propertyConf) {
				if (!in_array($propertyConf['datatype'], self::$immutableFields)) {
					// compatibility for older api versions
					if (isset($propertyConf['title'])) {
						$propertyName = $propertyConf['title'];
					}

					$properties[$propertyConf['id']] = $propertyName;
				}
			}
		}
		return $properties;
	}

	public function getMemberFieldDefinitionsById() {
		$definitions = $this->cache->getRoot('definition');
		$properties = array();
		if (isset($definitions['member']['properties'])) {
			foreach ($definitions['member']['properties'] as $propertyConf) {
				$properties[$propertyConf['id']] = $propertyConf;
			}
		}
		return $properties;
	}

	public function getMemberFieldDefinitionsByTitle() {
		$definitions = $this->cache->getRoot('definition');
		$properties = array();
		if (isset($definitions['member']['properties'])) {
			foreach ($definitions['member']['properties'] as $propertyConf) {
				$properties[$propertyConf['title']] = $propertyConf;
			}
		}
		return $properties;
	}

	public function getMemberProperties() {
		$definitions = $this->cache->getRoot('definition');
		$properties = array();
		if (isset($definitions['member']['properties'])) {
			foreach ($definitions['member']['properties'] as $propertyName => $propertyConf) {
				// compatibility for older api versions
				if (isset($propertyConf['title'])) {
					$propertyName = $propertyConf['title'];
				}
				$properties[$propertyName] = $propertyConf;
			}
		}
		return $properties;
	}

	public function getMemberTitleFields() {
		$definitions = $this->cache->getRoot('definition');
		$properties = array();
		if (isset($definitions['member']['label'])) {
			foreach ($definitions['member']['label'] as $propertyName) {
				$properties[] = $propertyName;
			}
		}
		return $properties;
	}

	public function isValidMemberFieldValue($fieldname, $value) {
		$properties = self::Instance()->getMemberProperties();
		if (isset($properties[$fieldname])) {
			// compatibility for older api versions
			if (in_array($value, $properties[$fieldname]['values'])) {
				return true;
			}

			foreach ($properties[$fieldname]['values'] as $val) {
				if (isset($val['value']) && $val['value'] == $value) {
					return true;
				}
			}
		}
		return false;
	}

	private function recursiveMembergroupChilds($objectId) {
		$childs = array();

		$obj = $this->cache->getObject('membergroup', $objectId);
		if (isset($obj['children']['membergroup'])) {
			// cache objects
			$this->cache->getObjects('membergroup', $obj['children']['membergroup']);

			// build tree
			foreach ($obj['children']['membergroup'] as $childId) {
				$childs[$childId] = array(
					'title' => $this->getObjectLabel('membergroup', $childId),
					'writeable' => $this->objectIsWriteable('membergroup', $childId),
					'childs' => $this->recursiveMembergroupChilds($childId)
				);
			}
		}
		return $childs;
	}

	public function getObjectLabel($type, $objectId) {
		$obj = $this->cache->getObject($type, $objectId);
		if (isset($obj['properties']['title'])) {
			return $obj['properties']['title'];
		}
		return $type.'_'.$objectId;
	}

	public function objectIsWriteable($type, $objectId) {
		$obj = $this->cache->getObject($type, $objectId);
		if (isset($obj['readonly'])) {
			return !$obj['readonly'];
		}
		return false;
	}

	public function getUserAgent() {
		return 'Webling-WP-Plugin/' . WEBLING_DB_VERSION;
	}
}
