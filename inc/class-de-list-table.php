<?php

require 'class-wp-list-table.php';


class DE_List_Table_Pass_Generated extends DE_List_Table
{
	private $currentNumPage = 1;
	private $nbItemsPerPage = 10;
	
	private $orderColumn = null;
	private $orderDirection = "ASC";
	
	// Recherche
	private $passStatus = null;
	
	private $membershipPlanId = null;
	
	private $expirationDateStart = null;
	private $expirationDateEnd = null;
	
	private $createdAtStart = null;
	private $createdAtEnd = null;
	
	private $updatedAtStart = null;
	private $updatedAtEnd = null;
	
	
	// Requêtes
	private $queryCountGeneratedPasses = null;
	private $querySelectGeneratedPasses = null;
	
	private $queryParamsCount;
	private $queryParams;
	
	
	private $nbPass;
	
	public $items;
	

	/**
	 * Prepares the list of items for displaying.
	 * @uses WP_List_Table::set_pagination_args()
	 */
	public function prepare_items()
	{
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$this->items = $this->retrieveGeneratedPasses($this->currentNumPage);
		
		$this->set_pagination_args(
			array(
				'total_items' => $this->nbPass,
				'per_page' => $this->nbItemsPerPage,
			)
		);
	}
	
	public function retrieveGeneratedPasses($numPage = 1)
	{
		global $wpdb;
		
		// Si les requêtes SQL ne sont pas initialisées -> on le fait
		if ($this->queryCountGeneratedPasses === null || $this->querySelectGeneratedPasses === null) {
			$this->initializeQueries();
		}
		
		$this->nbPass = $wpdb->get_var($wpdb->prepare($this->queryCountGeneratedPasses, $this->queryParamsCount));
		
		// On définit l'offset pour la pagination
		$min = ($numPage - 1) * $this->nbItemsPerPage;
		
		
		// On veut ordonner notre tableau sur une colonne
		if ($this->orderColumn !== null && ($this->orderDirection == "ASC" || $this->orderDirection == "DESC")) {
			switch ($this->orderColumn) {
				case 'code':
					$this->querySelectGeneratedPasses .= "ORDER BY dbp.code ";
					break;
				
				case 'membership_plan_name':
					$this->querySelectGeneratedPasses .= "ORDER BY p.post_title ";
					break;
				
				case 'date_end_code_active':
					$this->querySelectGeneratedPasses .= "ORDER BY dbp.date_end_code_active ";
					break;
				
				case 'updated_at':
					$this->querySelectGeneratedPasses .= "ORDER BY dbp.updated_at ";
					break;
				
				case 'created_at':
					$this->querySelectGeneratedPasses .= "ORDER BY dbp.created_at ";
					break;
				
				default: // Au cas où
					$this->querySelectGeneratedPasses .= "ORDER BY dbp.id ";
					break;
			}
			
			$this->querySelectGeneratedPasses .= $this->orderDirection ." "; // Ordre
		}
		
		$querySelectGeneratedPassesWithLimit = $this->querySelectGeneratedPasses ."LIMIT %d, %d";
		
		array_push($this->queryParams, $min, $this->nbItemsPerPage);
		
		return $wpdb->get_results($wpdb->prepare($querySelectGeneratedPassesWithLimit, $this->queryParams));
	}
	
	
	public function get_sortable_columns()
	{
		$sortableColumns = array(
			'code' => array('code', false),
			// 'user' => array('user', false),
			'membership_plan_name' => array('membership_plan_name', false),
			'date_end_code_active' => array('date_end_code_active', false),
			'updated_at' => array('updated_at', false),
			'created_at' => array('created_at', false),
		);
		
		return $sortableColumns;
	}
	
	public function get_bulk_actions()
	{
		$actions = array(
			'delete' => __("Delete", "debampass"),
		);
		
		return $actions;
	}
	
	
	public function initializeQueries()
	{
		global $wpdb;
		
		$tableDeBamPass = $wpdb->prefix ."debampass";
		$tableUsers = $wpdb->prefix ."users";
		$tablePosts = $wpdb->prefix ."posts";
		
		// On récupère le nombre de pass
		$this->queryCountGeneratedPasses = "";
		$this->queryCountGeneratedPasses .= "SELECT COUNT(id) ";
		$this->queryCountGeneratedPasses .= "FROM $tableDeBamPass ";
		$this->queryCountGeneratedPasses .= "WHERE %d";
		
		
		// Recherche
		$this->queryParamsCount = array(1);
		$this->queryParams = array();
		$querySelectGeneratedPassesSearch = "WHERE 1 ";
		
		// Statut des pass
		if ($this->passStatus !== null) {
			if ($this->passStatus == 1) { // Activés
				$this->queryCountGeneratedPasses .= " AND user_id IS NOT NULL AND updated_at IS NOT NULL ";
				$querySelectGeneratedPassesSearch .= "AND dbp.user_id IS NOT NULL AND dbp.updated_at IS NOT NULL ";
			} elseif ($this->passStatus == 0) { // Non activés
				$this->queryCountGeneratedPasses .= " AND user_id IS NULL AND updated_at IS NULL ";
				$querySelectGeneratedPassesSearch .= "AND dbp.user_id IS NULL AND dbp.updated_at IS NULL ";
			}
		}
		
		// Membership Plan
		if ($this->membershipPlanId !== null) {
			$this->queryCountGeneratedPasses .= " AND membership_plan = %d ";
			$querySelectGeneratedPassesSearch .= "AND dbp.membership_plan = %d ";
			array_push($this->queryParamsCount, $this->membershipPlanId);
			array_push($this->queryParams, $this->membershipPlanId);
		}
		
		// Date d'expiration
		if ($this->expirationDateStart !== null) { // Début
			$this->queryCountGeneratedPasses .= " AND date_end_code_active >= %s ";
			$querySelectGeneratedPassesSearch .= "AND dbp.date_end_code_active >= %s ";
			array_push($this->queryParamsCount, $this->expirationDateStart);
			array_push($this->queryParams, $this->expirationDateStart);
		}
		if ($this->expirationDateEnd !== null) { // Fin
			$this->queryCountGeneratedPasses .= " AND date_end_code_active <= %s ";
			$querySelectGeneratedPassesSearch .= "AND dbp.date_end_code_active <= %s ";
			array_push($this->queryParamsCount, $this->expirationDateEnd);
			array_push($this->queryParams, $this->expirationDateEnd);
		}
		
		// Date d'activation
		if ($this->updatedAtStart !== null) { // Début
			$this->queryCountGeneratedPasses .= " AND updated_at >= %s ";
			$querySelectGeneratedPassesSearch .= "AND dbp.updated_at >= %s ";
			array_push($this->queryParamsCount, $this->updatedAtStart);
			array_push($this->queryParams, $this->updatedAtStart);
		}
		if ($this->updatedAtEnd !== null) { // Fin
			$updatedAtEnd = DateTime::createFromFormat('Y-m-d', $this->updatedAtEnd);
			$updatedAtEnd->modify('+1 day');
			$updatedAtEnd->modify('-1 second');
			
			$this->queryCountGeneratedPasses .= " AND updated_at <= %s ";
			$querySelectGeneratedPassesSearch .= "AND dbp.updated_at <= %s ";
			array_push($this->queryParamsCount, $updatedAtEnd->format('Y-m-d'));
			array_push($this->queryParams, $updatedAtEnd->format('Y-m-d'));
		}
		
		// Date de création
		if ($this->createdAtStart !== null) { // Début
			$this->queryCountGeneratedPasses .= " AND created_at >= %s ";
			$querySelectGeneratedPassesSearch .= "AND dbp.created_at >= %s ";
			array_push($this->queryParamsCount, $this->createdAtStart);
			array_push($this->queryParams, $this->createdAtStart);
		}
		if ($this->createdAtEnd !== null) { // Fin
			$createdAtEnd = DateTime::createFromFormat('Y-m-d', $this->createdAtEnd);
			$createdAtEnd->modify('+1 day');
			$createdAtEnd->modify('-1 second');
			
			$this->queryCountGeneratedPasses .= " AND created_at <= %s ";
			$querySelectGeneratedPassesSearch .= "AND dbp.created_at <= %s ";
			array_push($this->queryParamsCount, $createdAtEnd->format('Y-m-d'));
			array_push($this->queryParams, $createdAtEnd->format('Y-m-d'));
		}
		
		
		
		$this->querySelectGeneratedPasses = "";
		$this->querySelectGeneratedPasses .= "SELECT dbp.id, dbp.membership_plan, dbp.user_id, dbp.order_id, dbp.code, dbp.date_end_code_active, dbp.created_at, dbp.updated_at, u.ID as id_user, u.user_email, u.display_name, p.ID AS id_post, p.post_title, p.post_name ";
		$this->querySelectGeneratedPasses .= "FROM $tableDeBamPass dbp ";
		$this->querySelectGeneratedPasses .= "LEFT JOIN $tableUsers u ON dbp.user_id = u.ID ";
		$this->querySelectGeneratedPasses .= "LEFT JOIN $tablePosts p ON dbp.membership_plan = p.ID ";
		
		
		// Recherche
		if ($querySelectGeneratedPassesSearch != "") {
			$this->querySelectGeneratedPasses .= $querySelectGeneratedPassesSearch;
		}
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	public function get_columns()
	{
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'code' => __("Code", "debampass"),
			'user' => __("User", "debampass"),
			'order' => __("Order", "debampass"),
			'membership_plan_name' => __("Membership Plan", "debampass"),
			'date_end_code_active' => __("Expiration date", "debampass"),
			'updated_at' => __("Activation date", "debampass"),
			'created_at' => __("Creation date", "debampass"),
		);
		
		return $columns;
	}
	
	public function column_default($item, $columnName)
	{
		switch ($columnName) {
			case 'user':
				if ($item->user_id != null) {
					$userProfilPageUrl = get_edit_user_link($item->user_id);
					return '<a href="'. $userProfilPageUrl .'">'. $item->display_name ."<br />". $item->user_email .'</a>';
				}
				break;
			
			case 'order':
				if ($item->order_id != null) {
					return '<a href="'. admin_url('post.php?post='. $item->order_id .'&action=edit') .'">#'. $item->order_id .'</a>';
				}
				break;
			
			case 'membership_plan_name':
				$membershipPlanEditPage = get_edit_post_link($item->membership_plan);
				return '<a href="'. $membershipPlanEditPage .'">'. $item->post_title .'</a>';
				break;
			
			case 'date_end_code_active':
				$date = new DateTime($item->$columnName);
				return $date->format('d/m/Y');
				break;
				
			case 'created_at':
			case 'updated_at':
				if ($item->$columnName != null) {
					$date = new DateTime($item->$columnName);
					return $date->format('d/m/Y H:i:s');
				}
				break;
			
			default:
				return $item->$columnName;
				break;
		}
	}
	
	// Gestion de la colonne 'Checkbox'
	public function column_cb($item)
	{
		$dateEndCodeActive = DateTime::createFromFormat('Y-m-d', $item->date_end_code_active);
		$currentDate = new DateTime();
		
		// Seulement si le pass a expiré est qu'il n'a pas été activé
		if ($this->isExpiredAndNoActive($item)) {
			return sprintf('<input type="checkbox" name="pass[]" value="%s" />', $item->id);
		}
	}
	
	// Gestion de la colonne 'Code'
	public function column_code($item)
	{
		$url = wp_unslash($_SERVER['REQUEST_URI']);
		$url = Tools::AddParameterToUrl($url, 'action', 'delete');
		$url = Tools::AddParameterToUrl($url, 'pass[]', $item->id);
		
		$actions = array(
			'delete' => '<a href="'. $url .'">'. __("Delete", "debampass") .'</a>',
		);
		
		if ($this->isExpiredAndNoActive($item)) { // Seulement si le pass a expiré est qu'il n'a pas été activé
			return sprintf('%1$s %2$s', '<span class="column-code">'. $item->code .'</span>', $this->row_actions($actions));
		} else {
			return sprintf('%1$s', '<span class="column-code">'. $item->code .'</span>');
		}
	}
	
	private function isExpiredAndNoActive($item)
	{
		$dateEndCodeActive = DateTime::createFromFormat('Y-m-d', $item->date_end_code_active);
		$currentDate = new DateTime();
		
		return trim($item->user_id) == "" && trim($item->updated_at) == "" && $dateEndCodeActive < $currentDate;
	}
	
	
	public function getQueryCountGeneratedPasses()
	{
		return $this->queryCountGeneratedPasses;
	}
	public function getQuerySelectGeneratedPasses()
	{
		return $this->querySelectGeneratedPasses;
	}
	
	public function getQueryParamsCount()
	{
		return $this->queryParamsCount;
	}
	public function getQueryParams()
	{
		return $this->queryParams;
	}
	
	
	public function setCurrentNumPage($numPage)
	{
		if ($numPage > 0) {
			$this->currentNumPage = $numPage;
		}
	}
	
	public function setOrder($column, $direction)
	{
		$this->orderColumn = $column;
		$this->orderDirection = strtoupper($direction);
	}
	
	public function getNbPass()
	{
		return $this->nbPass;
	}
	
	// Statut des pass
	public function setPassStatus($status)
	{
		$this->passStatus = $status;
	}
	
	// Membership Plan
	public function setMembershipPlan($membershipPlan)
	{
		$this->membershipPlanId = $membershipPlan;
	}
	
	// Date d'expiration
	public function setExpirationDateStart($expirationDateStart)
	{
		$this->expirationDateStart = $expirationDateStart;
	}
	public function setExpirationDateEnd($expirationDateEnd)
	{
		$this->expirationDateEnd = $expirationDateEnd;
	}
	
	// Date de création
	public function setCreatedAtStart($createdAtStart)
	{
		$this->createdAtStart = $createdAtStart;
	}
	public function setCreatedAtEnd($createdAtEnd)
	{
		$this->createdAtEnd = $createdAtEnd;
	}
	
	// Date de mise à jour
	public function setUpdatedAtStart($updatedAtStart)
	{
		$this->updatedAtStart = $updatedAtStart;
	}
	public function setUpdatedAtEnd($updatedAtEnd)
	{
		$this->updatedAtEnd = $updatedAtEnd;
	}
}
