<?php
namespace Opencart\Admin\Model\Catalog;
/**
 * Class Filter
 *
 * @package Opencart\Admin\Model\Catalog
 */
class Filter extends \Opencart\System\Engine\Model {
	public function addGroup($data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "filter_group` SET `sort_order` = '" . (int)$data['sort_order'] . "'");

		$filter_group_id = $this->db->getLastId();

		foreach ($data['filter_group_description'] as $language_id => $value) {
			$this->addDescription($filter_group_id, $language_id, $value);
		}

		if (isset($data['filter'])) {
			foreach ($data['filter'] as $filter) {
				$this->addFilter($filter_group_id, $filter);
			}
		}

		return $filter_group_id;
	}

	public function editGroup($filter_group_id, $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "filter_group` SET `sort_order` = '" . (int)$data['sort_order'] . "' WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");

		$this->deleteDescription($filter_group_id);

		foreach ($data['filter_group_description'] as $language_id => $value) {
			$this->addDescription($filter_group_id, $language_id, $value);
		}

		$this->deleteFilter($filter_group_id);

		if (isset($data['filter'])) {
			foreach ($data['filter'] as $filter) {
				$this->addFilter($filter_group_id, $filter);
			}
		}
	}

	public function deleteGroup(int $filter_group_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter_group` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");

		$this->deleteDescription($filter_group_id);
		$this->deleteFilter($filter_group_id);
	}

	/**
	 * Get Group
	 *
	 * @param int $filter_group_id
	 *
	 * @return array<string, mixed>
	 */
	public function getGroup(int $filter_group_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "filter_group` `fg` LEFT JOIN `" . DB_PREFIX . "filter_group_description` `fgd` ON (`fg`.`filter_group_id` = `fgd`.`filter_group_id`) WHERE `fg`.`filter_group_id` = '" . (int)$filter_group_id . "' AND `fgd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Get Groups
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getGroups(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "filter_group` `fg` LEFT JOIN `" . DB_PREFIX . "filter_group_description` `fgd` ON (`fg`.`filter_group_id` = `fgd`.`filter_group_id`) WHERE `fgd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";

		$sort_data = [
			'fgd.name',
			'fg.sort_order'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `fgd`.`name`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 *	Add Description
	 *
	 * @param int                  $filter_group_id primary key of the attribute record to be fetched
	 * @param int                  $language_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function addDescription(int $filter_group_id, int $language_id, $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "filter_group_description` SET `filter_group_id` = '" . (int)$filter_group_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($data['name']) . "'");
	}

	/**
	 *	Delete Description
	 *
	 * @param int $filter_group_id primary key of the filter record to be fetched
	 *
	 * @return void
	 */
	public function deleteDescription(int $filter_group_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter_group_description` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");
	}

	/**
	 * Get Group Descriptions
	 *
	 * @param int $filter_group_id
	 *
	 * @return array<int, array<string, string>>
	 */
	public function getDescriptions(int $filter_group_id): array {
		$filter_group_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "filter_group_description` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");

		foreach ($query->rows as $result) {
			$filter_group_data[$result['language_id']] = ['name' => $result['name']];
		}

		return $filter_group_data;
	}

	/**
	 * Add Filter
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function addFilter(int $filter_group_id, array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "filter` SET `filter_group_id` = '" . (int)$filter_group_id . "', `sort_order` = '" . (int)$data['sort_order'] . "'");

		$filter_id = $this->db->getLastId();

		foreach ($data['filter_description'] as $language_id => $filter_description) {
			$this->addFilterDescription($filter_id, $language_id, $filter_group_id, $filter_description);
		}

		return $filter_id;
	}

	public function editFilter(int $filter_id, int $filter_group_id, $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "filter` SET `filter_id` = '" . (int)$filter_id . "', `filter_group_id` = '" . (int)$filter_group_id . "', `sort_order` = '" . (int)$data['sort_order'] . "'");

		$this->deleteFilterDescription($filter_id);

		foreach ($data['filter_description'] as $language_id => $filter_description) {
			$this->addFilterDescription($filter_id, $language_id, $filter_group_id, $filter_description);
		}
	}

	/**
	 * Delete Filter
	 *
	 * @param int $filter_group_id
	 *
	 * @return void
	 */
	public function deleteFilter(int $filter_group_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");

		$this->deleteFilterDescription($filter_group_id);
	}

	/**
	 *	Add Description
	 *
	 * @param int                  $filter_group_id primary key of the attribute record to be fetched
	 * @param int                  $language_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function addFilterDescription(int $filter_id, int $language_id, int $filter_group_id, $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "filter_description` SET `filter_id` = '" . (int)$filter_id . "', `language_id` = '" . (int)$language_id . "', `filter_group_id` = '" . (int)$filter_group_id . "', `name` = '" . $this->db->escape($data['name']) . "'");
	}

	public function editFilterDescription(int $filter_id, int $language_id, int $filter_group_id, $data): void {

	}

	/**
	 *	Delete Description
	 *
	 * @param int $filter_group_id primary key of the filter record to be fetched
	 *
	 * @return void
	 */
	public function deleteFilterDescription(int $filter_group_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter_description` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");
	}

	/**
	 * Get Descriptions
	 *
	 * @param int $filter_group_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getFilterDescriptions(int $filter_group_id): array {
		$filter_data = [];

		$filter_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "filter` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");

		foreach ($filter_query->rows as $filter) {
			$filter_description_data = [];

			$filter_description_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "filter_description` WHERE `filter_id` = '" . (int)$filter['filter_id'] . "'");

			foreach ($filter_description_query->rows as $filter_description) {
				$filter_description_data[$filter_description['language_id']] = ['name' => $filter_description['name']];
			}

			$filter_data[] = [
				'filter_id'          => $filter['filter_id'],
				'filter_description' => $filter_description_data,
				'sort_order'         => $filter['sort_order']
			];
		}

		return $filter_data;
	}


	/**
	 * Edit Filter
	 *
	 * @param int                  $filter_group_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editFilter(int $filter_group_id, array $data): void {
		$this->editGroup($data);


		$this->deleteGroupDescription($filter_group_id);

		foreach ($data['filter_group_description'] as $language_id => $value) {
			$this->addGroupDescription($filter_group_id, $language_id, $value);
		}

		$this->deleteFilterByFIlterGroupId($filter_group_id);

		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");



		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter_description` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");

		if (isset($data['filter'])) {
			foreach ($data['filter'] as $filter) {


				if ($filter['filter_id']) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "filter` SET `filter_id` = '" . (int)$filter['filter_id'] . "', `filter_group_id` = '" . (int)$filter_group_id . "', `sort_order` = '" . (int)$filter['sort_order'] . "'");
				} else {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "filter` SET `filter_group_id` = '" . (int)$filter_group_id . "', `sort_order` = '" . (int)$filter['sort_order'] . "'");
				}

				$filter_id = $this->db->getLastId();

				foreach ($filter['filter_description'] as $language_id => $filter_description) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "filter_description` SET `filter_id` = '" . (int)$filter_id . "', `language_id` = '" . (int)$language_id . "', `filter_group_id` = '" . (int)$filter_group_id . "', `name` = '" . $this->db->escape($filter_description['name']) . "'");
				}
			}
		}
	}

	/**
	 * Delete Filter
	 *
	 * @param int $filter_group_id
	 *
	 * @return void
	 */
	public function deleteFilter(int $filter_group_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter_group` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter_group_description` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "filter_description` WHERE `filter_group_id` = '" . (int)$filter_group_id . "'");
	}

	/**
	 * Get Filter
	 *
	 * @param int $filter_id
	 *
	 * @return array<string, mixed>
	 */
	public function getFilter(int $filter_id): array {
		$query = $this->db->query("SELECT *, (SELECT `name` FROM `" . DB_PREFIX . "filter_group_description` `fgd` WHERE `f`.`filter_group_id` = `fgd`.`filter_group_id` AND `fgd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') AS `group` FROM `" . DB_PREFIX . "filter` `f` LEFT JOIN `" . DB_PREFIX . "filter_description` `fd` ON (`f`.`filter_id` = `fd`.`filter_id`) WHERE `f`.`filter_id` = '" . (int)$filter_id . "' AND `fd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Get Filters
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getFilters(array $data): array {
		$sql = "SELECT *, (SELECT name FROM `" . DB_PREFIX . "filter_group_description` `fgd` WHERE `f`.`filter_group_id` = `fgd`.`filter_group_id` AND `fgd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') AS `group` FROM `" . DB_PREFIX . "filter` `f` LEFT JOIN `" . DB_PREFIX . "filter_description` `fd` ON (`f`.`filter_id` = `fd`.`filter_id`) WHERE `fd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND LCASE(`fd`.`name`) LIKE '" . $this->db->escape(oc_strtolower($data['filter_name']) . '%') . "'";
		}

		$sql .= " ORDER BY `f`.`sort_order` ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}







	/**
	 * Get Total Groups
	 *
	 * @return int
	 */
	public function getTotalGroups(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "filter_group`");

		return (int)$query->row['total'];
	}
}
