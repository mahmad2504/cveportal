<?php
namespace App;
class Ldap
{
	function __construct()
	{
		$ldaphost = "134.86.102.150";  // your ldap servers
		//attempt to connect
		$this->ldap_conn=ldap_connect($ldaphost);  // must be a valid LDAP server!
	}
	function FakeLogin($user,$password)
	{
		$data = new \StdClass();
		$data->user_name = $user;
        $data->user_displayname = $user;
        $data->user_email = $user.'@fake.com';
		$data->user_firstname = $user;
        $data->user_lastname = $user;
		return $data;
	}   
	function Login($user,$password)
	{
		return $this->FakeLogin($user,$password);
		
		$ldap_conn = $this->ldap_conn;
		$bindDN = $user.'@mgc.mentorg.com';
		//if connected, then bind
		if ($ldap_conn)
		{
			$bound = @ldap_bind($ldap_conn, $bindDN, $password);// or die('LDAP: '.ldap_error($ldap_conn)); // this is an "anonymous" bind, typically
			if (!$bound)
            {
                return null;
            }
            else
            {
				$appuserlogin = $user;
                $filter = "(sAMAccountName=$appuserlogin)";
                $baseDN = "DC=MGC,DC=Mentorg,DC=Com";
                
                $search = ldap_search($ldap_conn, $baseDN, $filter);
			
                $entry = ldap_first_entry($ldap_conn, $search);
                $attrs = ldap_get_attributes($ldap_conn, $entry);
				
				$data = new \StdClass();
				
				$data->user_name = $user;
                $data->user_displayname = $attrs["displayName"][0];
                $data->user_email = $attrs["mail"][0];
             
                
                $namearray = explode(',', $data->user_displayname);
                $data->user_firstname = trim($namearray[1]);
                $data->user_lastname = trim($namearray[0]);
                
                ldap_close($ldap_conn);
				return $data;
			} 
		}
		else
			return null;
	}
}