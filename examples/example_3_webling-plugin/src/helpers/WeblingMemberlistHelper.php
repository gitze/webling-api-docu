<?php

class WeblingMemberlistHelper {

	/**
	 * @param $listconfig array list configuration
	 * @return array member ids
	 * @throws \Webling\Cache\CacheException
	 */
	public static function getMemberlistMemberIds($listconfig) {
		$apiCache = WeblingApiHelper::Instance()->cache();

		// collect memberIds
		$memberIds = array();
		if ($listconfig['type'] === 'ALL') {
			$data = $apiCache->getRoot("member");
			if (isset($data['objects']) && is_array($data['objects'])) {
				$memberIds = $data['objects'];
			}
		} elseif($listconfig['type'] === 'GROUPS') {
			if($listconfig['groups']){
				// filter groups
				$groupIds = unserialize($listconfig['groups']);
				if(is_array($groupIds) && count($groupIds)){
					foreach ($groupIds as $groupId){
						if(trim($groupId) != ''){
							$data = $apiCache->getObject("membergroup", intval($groupId));
							if($data) {
								if (isset($data["children"]['member']) && is_array($data["children"]['member'])){
									$memberIds = array_merge($memberIds, $data["children"]['member']);
								}
							} else {
								throw new Exception('Gruppe nicht gefunden: '.$groupId);
							}
						}
					}
					$memberIds = array_unique($memberIds);
				}
			}
		} elseif($listconfig['type'] === 'SAVEDSEARCH') {
			if ($listconfig['savedsearch']) {
				$memberIds = self::getSavedSearchMemberIds($listconfig);
			}
		}
		return $memberIds;
	}

	private static function getSavedSearchMemberIds($listconfig) {
		if ($listconfig) {
			$id = intval($listconfig['savedsearch']);
			// try loading from cache
			$cachestate = WeblingApiHelper::Instance()->cacheAdapter()->getCacheState();
			if ($cachestate && $listconfig['savedsearch_cache'] && $listconfig['savedsearch_cache_revision'] && $listconfig['savedsearch_cache_revision'] == $cachestate['revision']) {
				$data = json_decode($listconfig['savedsearch_cache'], true);
				if (is_array($data['objects'])) {
					return $data['objects'];
				}
			}
			// load from api
			$response = WeblingApiHelper::Instance()->client()->get('/template/'.$id.'/search');
			if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
				$data = $response->getData();
				if (is_array($data['objects'])) {
					if ($cachestate) {
						self::updateSavedSearchCache($listconfig['id'], $data, $cachestate['revision']);
					}
					return $data['objects'];
				}
			}
		}
		return array();
	}

	private static function updateSavedSearchCache($id, $data, $revision) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare("
				UPDATE {$wpdb->prefix}webling_memberlists
				SET 
				`savedsearch_cache` = %s,
				`savedsearch_cache_revision` = %d
				WHERE id = %d",
				json_encode($data),
				intval($revision),
				intval($id)
			)
		);
	}
}
