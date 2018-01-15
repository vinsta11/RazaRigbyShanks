<?php
/**
 * MySQLQueries.php
 *
 * This class provides functions to perform operations on the
 */

class MySqlQueries {

    public function __construct() { }

    public function __destruct() { }

    public static function check_session_var()
    {
		/*
		 *	@var m_query_string Concatenation is used to combine several aspects of an SQL statement.
		 *  
		 *
		 */
		
        $m_query_string  = "SELECT session_var_name ";
        $m_query_string .= "FROM session ";
        $m_query_string .= "WHERE session_id = :local_session_id ";
        $m_query_string .= "AND session_var_name = :session_var_name ";
        $m_query_string .= "LIMIT 1";
        return $m_query_string;
    }

    public static function create_session_var()
    {
        $m_query_string  = "INSERT INTO session ";
        $m_query_string .= "SET session_id = :local_session_id, ";
        $m_query_string .= "session_var_name = :session_var_name, ";
        $m_query_string .= "session_value = :session_var_value ";
        return $m_query_string;
    }

    public static function set_session_var()
    {
        $m_query_string  = "UPDATE session ";
        $m_query_string .= "SET session_value = :session_var_value ";
        $m_query_string .= "WHERE session_id = :local_session_id ";
        $m_query_string .= "AND session_var_name = :session_var_name";
        return $m_query_string;
    }

    public static function get_session_var()
    {
        $m_query_string  = "SELECT session_value ";
        $m_query_string .= "FROM session ";
        $m_query_string .= "WHERE session_id = :local_session_id ";
        $m_query_string .= "AND session_var_name = :session_var_name";
        return $m_query_string;
    }
}