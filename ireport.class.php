<?php
/*!
 * \file	class.ireport.php
 * \brief	an Interface for Report and ReportDecorator classes.
 *
 * \version	0.7
 * \author	Mohamad H. ARAB
 * \date	28, APR 2008
 *
 * Change log:
 * 		+ 29, APR 2008:
 * 			+ All data requests is now based on queries.
 * 			+ show function replaced by generate
 * 		+ 11 Sep, 2009:
 * 			* getCount added.
 */
namespace SQLMonster;

//! IReport interface 
/*! 
 * \class IReport
 *
 * a common interface in Report and ReportDecorator classes. 
 */
Interface IReport {
	public function generate ($query = null);
	public function &getList();
	public function setList (&$list);

	public function getQuery ();
	public function setQuery ($query);

	public function getCount ($query = null, $cache = true);
	public function setPrimaryIndex ($index);
}
?>
