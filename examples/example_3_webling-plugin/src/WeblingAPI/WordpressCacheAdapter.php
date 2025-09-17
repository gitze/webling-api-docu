<?php

namespace Webling\CacheAdapters;

use Webling\Cache\CacheException;

class WordpressCacheAdapter implements ICacheAdapter {

	public static $TABLE_NAME = 'webling_cache';
	public static $CACHE_DIR = '/uploads/webling';

	protected $options;

	function __construct($options = []) {
		global $wpdb;

		if (!is_array($options)) {
			throw new CacheException('Invalid options');
		}

		if (!get_class($wpdb) || !method_exists($wpdb, 'get_results')) {
			throw new CacheException('Not in Wordpress Context!');
		}

		$this->options = $options;
	}

	private function getCacheDir() {
		return WP_CONTENT_DIR . self::$CACHE_DIR;
	}

	private function createCacheDir() {
		$directory = $this->getCacheDir();
		if (!file_exists($directory)) {
			$success = mkdir($directory, 0755, true);
			if (!$success) {
				throw new CacheException('Could not create cache directory: '. $directory);
			}
		}
	}

	public function clearCache() {
		global $wpdb;
		$wpdb->get_row("TRUNCATE {$wpdb->prefix}" . self::$TABLE_NAME);
		$this->deleteAllRoots();
		$this->deleteCacheState();
		$wpdb->query("UPDATE {$wpdb->prefix}webling_memberlists SET savedsearch_cache = NULL, savedsearch_cache_revision = 0");
		array_map(array($this, 'deleteFile'), glob($this->getCacheDir().'/bin_*'));
	}

	public function setCacheState($data) {
		update_option('webling-cache-state', $data);
	}

	public function getCacheState() {
		return get_option('webling-cache-state');
	}

	public function deleteCacheState() {
		update_option('webling-cache-state', null);
	}

	public function getObject($id) {
		global $wpdb;

		if (!$id) {
			return null;
		}
		$id = intval($id);
		$entry = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}" . self::$TABLE_NAME . ' WHERE id = '.$id, 'ARRAY_A');

		if ($entry) {
			return json_decode($entry['data'], true);
		} else {
			return null;
		}
	}

	public function setObject($id, $data) {
		global $wpdb;

		if (!$id) {
			return;
		}
		$id = intval($id);
		$type = esc_sql($data['type']);
		$data = esc_sql(json_encode($data));

		if (strlen($type) > 0 && strlen($data) > 4) {
			$sql = "INSERT INTO {$wpdb->prefix}" . self::$TABLE_NAME
				. " (id, type, data) VALUES('".$id. "', '".$type. "', '".$data. "')"
				. " ON DUPLICATE KEY UPDATE id = '".$id."'";
			$wpdb->query($sql);
		}
	}

	public function deleteObject($id) {
		global $wpdb;

		$id = intval($id);
		$wpdb->query("DELETE FROM {$wpdb->prefix}" . self::$TABLE_NAME . " WHERE id = ".$id);
	}


	public function getObjectBinary($id, $url, $options = []) {
		$id = intval($id);
		$file = $this->getCacheDir().'/bin_'.$id.'_'.md5(strtolower($url));
		if (file_exists($file)) {

			// handle different image sizes
			if (isset($options['height']) || isset($options['width'])) {
				$width = isset($options['width']) && $options['width'] ? intval($options['width']) : null;
				$height = isset($options['height']) && $options['height'] ? intval($options['height']) : null;
				$filename_args = '';
				if ($width) {
					$filename_args .= '_w' . $width;
				}
				if ($height) {
					$filename_args .= '_h' . $height;
				}
				try {
					// generate resized file if needed
					if (!file_exists($file.$filename_args)) {
						$image = wp_get_image_editor($file);
						if (!is_wp_error($image)) {
							$image->resize($width, $height, true);
							$saved = $image->save($file.$filename_args);
							if (!is_wp_error($saved)) {
								// save image without file extension so that we do not have to store the extension
								rename($saved['path'], $file.$filename_args);
							}
						}
					}
					if (file_exists($file.$filename_args)) {
						return file_get_contents($file.$filename_args);
					}
				} catch (\Throwable $t) {
					// send original file as backup if resize fails
				}
			}
			// return original file
			return file_get_contents($file);
		} else {
			return null;
		}
	}

	public function setObjectBinary($id, $url, $data) {
		// make sure the cache directory exists
		$this->createCacheDir();

		$id = intval($id);
		$file = $this->getCacheDir().'/bin_'.$id.'_' . md5(strtolower($url));
		file_put_contents($file, $data);
	}

	public function deleteObjectBinaries($id) {
		$id = intval($id);
		array_map(array($this, 'deleteFile'), glob($this->getCacheDir().'/bin_'.$id.'_*'));
	}

	public function getRoot($type) {
		$type = preg_replace('/[^a-z]/i', '', strtolower($type));
		$optionname = 'webling-cache-root-'.$type;
		$optionvalue = get_option($optionname, null);
		if ($optionvalue !== null) {
			return json_decode($optionvalue, true);
		}
		return null;
	}

	public function setRoot($type, $data) {
		$type = preg_replace('/[^a-z]/i', '', strtolower($type));
		$optionname = 'webling-cache-root-'.$type;
		update_option($optionname, json_encode($data), false);
	}

	public function deleteAllRoots() {
		global $wpdb;

		$sql = "SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE 'webling-cache-root-%'";
		$options = $wpdb->get_results($sql, 'ARRAY_A');
		foreach ($options as $option) {
			delete_option($option['option_name']);
		}
	}

	public function deleteRoot($type) {
		global $wpdb;

		$sql = "SELECT option_name FROM {$wpdb->prefix}options WHERE option_name = 'webling-cache-root-".$type."'";
		$options = $wpdb->get_results($sql, 'ARRAY_A');
		foreach ($options as $option) {
			delete_option($option['option_name']);
		}
	}

	private function deleteFile($filename) {
		if (file_exists($filename)) {
			@unlink($filename);
			if (file_exists($filename)) {
				throw new CacheException('Could not delete cache file: ' . $filename);
			}
		}
	}
}
