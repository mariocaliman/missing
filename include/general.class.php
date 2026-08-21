<?php 
class General {

	private $mysqli;
	
	// connect to database
    function set_connection($mysqli) {
        $this->db =& $mysqli;
    }
	
	// get all categories
	function categories($order)
	{
	$sql = "SELECT * FROM categories ORDER BY $order";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
			}
			return $rows;
		}
	
	}
	
	// get single category by id
	function category($id)
	{
	$sql = "SELECT * FROM categories WHERE id='$id' LIMIT 1";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			$row = $query->fetch_assoc();
			return $row;
		}
	}
	
	// get top news query
	function news($period,$order,$number)
	{
	$time = time()-$period;
	$sql = "SELECT * FROM news WHERE published='1' AND datetime > $time ORDER BY $order LIMIT $number";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
			}
			return $rows;
		}
	}
	
	// get single article by id
	function article($id)
	{
	$sql = "SELECT * FROM news WHERE published='1' AND id='$id' LIMIT 1";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			$this->db->query("UPDATE news SET hits=hits+1 WHERE id='$id'");
			$row = $query->fetch_assoc();
			return $row;
		}
	}
	
	// get single source by id
	function source($id)
	{
	$sql = "SELECT * FROM sources WHERE id='$id' LIMIT 1";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			$row = $query->fetch_assoc();
			return $row;
		}
	}
	
	// get all links 
	function links($order)
	{
	$sql = "SELECT * FROM links ORDER BY $order";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
			}
			return $rows;
		}
	
	}
	
	// get related news 
	function related($id,$category_id,$title,$number) 
	{
		$sql = "SELECT * FROM news WHERE published='1' AND id!='$id' AND MATCH (title) AGAINST ('$title' IN BOOLEAN MODE) ORDER BY id DESC LIMIT $number";
		$query = $this->db->query($sql);
			if ($query->num_rows == 0) {
				$sql = "SELECT * FROM news WHERE published='1' AND id!='$id' AND category_id='$category_id' ORDER BY id DESC LIMIT $number";
				$query = $this->db->query($sql);
				while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
				}
				return $rows;
			} else {
				while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
				}
				return $rows;
			}
	}
	
	function pages($order)
	{
	$sql = "SELECT * FROM pages ORDER BY $order";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
			}
			return $rows;
		}
	}
	
	function page($id)
	{
	$sql = "SELECT * FROM pages WHERE id='$id' LIMIT 1";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			$row = $query->fetch_assoc();
			return $row;
		}
	}
	
	
	function get_options($set) {
	$options = array();
		try {
			$query = $this->db->query("SELECT * FROM options WHERE option_set='$set' ORDER BY id ASC");
			if ($query) {
				while ($row = $query->fetch_assoc()) {
					$options[$row["option_name"]] = $row["option_value"];
				}
			}
		} catch (mysqli_sql_exception $e) {
			return $options;
		}
	return $options;
	}
	
}
?>