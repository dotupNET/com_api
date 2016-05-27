<?php
/**
 * @package com_api
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API) 
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
*/

defined('_JEXEC') or die;
jimport('joomla.application.component.model');

abstract class ApiAuthentication extends JObject {

	protected	$auth_method		= null;
	protected	$domain_checking	= null;
	protected	$x_auth	= null;
	static		$auth_errors		= array();

	public function __construct($params) {

		parent::__construct();

		$app = JFactory::getApplication();
		$key = $app->input->get('key','','STRING');
		
		//used core code - resuest header method not found
		$headers = apache_request_headers();
		$this->x_auth = $headers['x-auth'];
		
		
		if(empty($key))
		{
			$key = $app->input->post->get('key','','STRING');
		}

		if(empty($key))
		{
			$this->set('auth_method',$params->get('auth_method','username'));
			$this->set('auth_method',$params->get('auth_method','password'));
			$this->set('auth_method', $params->get('auth_method', 'login'));
		}
		else if($this->x_auth)
		{
			$this->set('auth_method', $params->get('auth_method', 'session'));
		}
		else
		{
			$this->set('auth_method', $params->get('auth_method', 'key'));
		}
		$this->set('domain_checking', $params->get('domain_checking', 1));
  }

	abstract public function authenticate();

	public static function authenticateRequest() {
		$params			= JComponentHelper::getParams('com_api');
		$app = JFactory::getApplication();
		
		$key = $app->input->get('key','','STRING');
		
		//used core code
		$headers = apache_request_headers();
		$head_auth = $headers['x-auth'];
		
		if(empty($key))
		{
			$key = $app->input->post->get('key','','STRING'); 
		}

		if(!empty($key))
		{
			$method			= 'key';
		}
		else
		{
			$method			= 'login';
		}
		
		if($head_auth)
		{
			$method			= 'session';
		}

		$className 		= 'APIAuthentication'.ucwords($method);

		$auth_handler 	= new $className($params);

		$user_id		= $auth_handler->authenticate();

		if ($user_id === false) :
			self::setAuthError($auth_handler->getError());
			return false;
		else :
			$user	= JFactory::getUser($user_id);
			if (!$user->id) :
				self::setAuthError(JText::_("COM_API_USER_NOT_FOUND"));
				return false;
			endif;

			if ($user->block == 1) :
				self::setAuthError(JText::_("COM_API_BLOCKED_USER"));
				return false;
			endif;

			return $user;

		endif;

	}

	public static function setAuthError($msg) {
		self::$auth_errors[] = $msg;
		return true;
	}

	public static function getAuthError() {
		if (empty(self::$auth_errors)) :
			return false;
		endif;
		return array_pop(self::$auth_errors);
	}

}
