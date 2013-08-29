<?php
/*!
 * \file	class.reportpager.php
 * \brief	This file provides Report decorator that caculates the total of 
 * 			records regarding to the given criteria. and makes paging according to
 * 			given values in config file and $_GET valriables
 *
 * \version	0.3
 * \author	Mohamad H. ARAB
 * \date	9, May 2008
 *
 * 11, Aug 2008
 * 		* bug in apply method causing infinit loop solved.
 *
 * 26, Jun 2012
 *              * Recreate Peport Pager
 */

//! class ReportCount
/*! \class ReportCount
 *
 * This class is decorator for Report class that get the total count of records
 * according to given criteria.
 */

namespace SQLMonster;

use \Config as Config;

class ReportPager extends ReportDecorator{
	public function __construct (IReport &$report, $offset=null, $limit=null) {
		parent::__construct ($report);
		$this->offset = $offset;
		$this->limit = $limit;
	}

	public function __get ($key) {
		switch ($key) {
		case 'TotalRecords':
			return $this->_report->getCount ();
			break;
		case 'TotalPages':
			return $this->pages();
			break;
		case 'CurrentPage':
			return $this->current();
			break;
		case 'PreviousPageUri':
			return $this->uri(-1);
			break;
		case 'NextPageUri':
			return $this->uri(1);
			break;
		case 'FirstPageUri':
			return $this->uri(-$this->CurrentPage);
			break;
		case 'LastPageUri':
			return $this->uri($this->TotalPages);
			break;
		default:
			break;
		}
	}

	static function info($total, $page=1, $rows=null)
	{
		$rows = is_null($rows) ? 20 : (int)$rows;

		if ($rows == 0) {
			throw new exception("\$rows is zero.");
		}
		$pages = intval(ceil($total/$rows));

		// Set current page and offset
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		if ($page <= 0 || $page > $pages) {
			$page = $pages;
		}
		$offset = ($page-1)*$rows;

		// Specify pls(start) and ple(end) of pages list in pagination
		$plw = 5; // pagination_list_width
		if ($pages <= 2*$plw+1) {
			$pls=1; $ple=$pages;
		} else {
			$pls = $page-$plw < 1 ? 1 : $page-$plw;
			$ple = $pages-$plw < $page ? $pages : $page+$plw;
		}
		return array(
			'totalRows'  => $total,
			'currentPage'=> $page,
			'lastPage'   => $pages,
			'listStart'  => $pls,
			'listEnd'    => $ple,
			'offset'     => $offset,
			'limit'      => $rows,
			'limitList'  => explode(',', Config::instance ()->page_rows_list)
		);
	}

	static function uri ($uri = null) {
		if (is_null($uri)) $uri = $_SERVER['REQUEST_URI'];
		$uri = strpos($uri, '?') !== false ? $uri = preg_replace('/^(.*[&|\?])(?:page=\d*&?)(.*?)$/', '${1}${3}${2}', $uri) : $uri.'?';
		$uriEnd = substr($uri, -1);
		$uri .= ($uriEnd == '?' || $uriEnd == '&' ? '' : '&').'page=';
		return $uri;
	}

	public function apply ($query) {
		$query = preg_replace (parent::QueryRegex, 
			"\\1\\3\\5\\7\\9 LIMIT $this->offset, $this->limit" , $query);

		return $query;
	}
}
