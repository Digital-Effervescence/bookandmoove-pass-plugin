<?php

require 'class-wp-list-table.php';


class DE_List_Table_Pass_Generated extends DE_List_Table
{
	private $currentNumPage = 1;
	private $nbItemsPerPage = 10;
	
	private $orderColumn = null;
	private $orderDirection = "ASC";
	
	private $nbPass;
	
	public $items;
	
	// Checks the current user's permissions
	/*public function ajax_user_can()
	{
		die( 'function WP_List_Table::ajax_user_can() must be over-ridden in a sub-class.' );
	}*/
	
	

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

		
		// echo "<pre>";
		// print_r($this->items);
		// echo "</pre>";
	}
	
	public function retrieveGeneratedPasses($numPage = 1)
	{
		global $wpdb;
		
		$tableDeBamPass = $wpdb->prefix ."debampass";
		$tableUsers = $wpdb->prefix ."users";
		$tablePosts = $wpdb->prefix ."posts";
		
		// On récupère le nombre de pass
		$queryCountGeneratedPasses = "";
		$queryCountGeneratedPasses .= "SELECT COUNT(id) ";
		$queryCountGeneratedPasses .= "FROM $tableDeBamPass";
		
		$this->nbPass = $wpdb->get_var($queryCountGeneratedPasses);
		
		// On définit les variables pour la pagination
		$min = ($numPage - 1) * $this->nbItemsPerPage;
		$max = $this->nbItemsPerPage;
		
		$querySelectGeneratedPasses = "";
		$querySelectGeneratedPasses .= "SELECT dbp.id, dbp.membership_plan, dbp.user_id, dbp.code, dbp.date_end_code_active, dbp.created_at, dbp.updated_at, u.ID as id_user, u.user_email, u.display_name, p.ID AS id_post, p.post_title, p.post_name ";
		$querySelectGeneratedPasses .= "FROM $tableDeBamPass dbp ";
		$querySelectGeneratedPasses .= "LEFT JOIN $tableUsers u ON dbp.user_id = u.ID ";
		$querySelectGeneratedPasses .= "LEFT JOIN $tablePosts p ON dbp.membership_plan = p.ID ";
		
		// On veut ordonner notre tableau sur une colonne
		if ($this->orderColumn !== null && ($this->orderDirection == "ASC" || $this->orderDirection == "DESC")) {
			switch ($this->orderColumn) {
				case 'code':
					$querySelectGeneratedPasses .= "ORDER BY dbp.code ";
					break;
				
				case 'membership_plan_name':
					$querySelectGeneratedPasses .= "ORDER BY p.post_title ";
					break;
				
				case 'date_end_code_active':
					$querySelectGeneratedPasses .= "ORDER BY dbp.date_end_code_active ";
					break;
				
				case 'updated_at':
					$querySelectGeneratedPasses .= "ORDER BY dbp.updated_at ";
					break;
				
				case 'created_at':
					$querySelectGeneratedPasses .= "ORDER BY dbp.created_at ";
					break;
				
				default: // Au cas où
					$querySelectGeneratedPasses .= "ORDER BY dbp.id ";
					break;
			}
			
			$querySelectGeneratedPasses .= $this->orderDirection ." "; // Sens
		}
		
		$querySelectGeneratedPasses .= "LIMIT %d, %d";
		
		// $this->items = $wpdb->query($wpdb->prepare($querySelectGeneratedPasses, array($min, $max)));
		return $wpdb->get_results($wpdb->prepare($querySelectGeneratedPasses, $min, $max));
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

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	public function get_columns()
	{
		$columns = array(
			'code' => __("Code", "debampass"),
			'user' => __("User", "debampass"),
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
			case 'code':
				return '<span class="column-code">'. $item->code .'</span>';
				break;
			
			case 'user':
				if ($item->user_id != null) {
					$userProfilPageUrl = get_edit_user_link($item->user_id);
					return '<a href="'. $userProfilPageUrl .'">'. $item->display_name ."<br />". $item->user_email .'</a>';
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
}
