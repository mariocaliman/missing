<?php 
class General {

	var $mysqli;

    function set_connection($mysqli) {
        $this->db =& $mysqli;
    }
	
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
	
	function sources()
	{
	$sql = "SELECT id,title FROM sources ORDER BY id ASC";
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
	
	function link($id)
	{
	$sql = "SELECT * FROM links WHERE id='$id' LIMIT 1";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			$row = $query->fetch_assoc();
			return $row;
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
	
	
	function news($id)
	{
	$sql = "SELECT * FROM news WHERE id='$id' LIMIT 1";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			$row = $query->fetch_assoc();
			return $row;
		}
	}
	
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
	
	function start_period() {
	$sql = "SELECT month,year FROM news ORDER BY id ASC LIMIT 1";
	$query = $this->db->query($sql);
		if ($query->num_rows == 0) {
			return 0;
		} else {
			$row = $query->fetch_assoc();
			return $row;
		}	
	}
	
	function statistics_news($day,$month,$year) {
	$sql = "SELECT day,month,year FROM news WHERE day='$day' AND month='$month' AND year='$year'";
	$query = $this->db->query($sql);
	return $query->num_rows;
	}
	
	function set_options($data,$set) {
	unset($data['save']);
	foreach ($data AS $key=>$value) {
	$check = $this->db->query("SELECT option_name FROM options WHERE option_name='$key'");
	$value = $this->db->real_escape_string(htmlspecialchars($value,ENT_QUOTES));	
	if ($check->num_rows == 0) {
	$excute = $this->db->query("INSERT INTO options (option_name,option_value,option_default,option_set) VALUES ('$key','$value','$value','$set')");	
	} else {
	$excute = $this->db->query("UPDATE options SET option_value='$value' WHERE option_name='$key'");		
	}
	if ($excute) {
	$message = notification('success','All Changes Saved.');
	} else {
	$message = notification('danger','Error Happened.');
	}
	}
	return $message;
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