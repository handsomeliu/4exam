<?php

//
/*
	数据库的 curd 操作 based db school info
	
	数据库对应项结构：

	//学校
	//学校代码_id
	//学校最新全球排名
	//学校最新全国排名
	//学校社会热度
	//学校招生名额
	//学校考试内容
	
	//地域	
	//地域代码_id
	
	//学院
	//学院代码_id
	//学院最新全球排名
	//学院最新全国排名
	//学院最新全校排名
	//学院社会热度
	//学院招生名额
	//学院免推招生名额
	//学院考试内容
	
	//专业名称
	//专业代码_id
	//专业最新全球排名
	//专业最新全国排名
	//专业最近学院排名
	//专业社会热度
	//专业招生名额
	//专业免推招生名额
	//专业考试内容
	
	//研究方向
	//研究方向代码_id
	//研究方法全球排名
	//研究方法全国排名
	//研究方向社会热度		
	//研究方向招生名额
	//研究方向考试内容
	//指导老师
	//跨专业	
	//备注
	
	
	by liulube@126.com

*/
date_default_timezone_set('PRC');

class db_area {
	//地域	
	//地域代码_id
	
}

//class db_

class db_school_info {
	
	
	private $db_name = "/../dat/school_info.db";
	private $db_pw = "";
	private $tab_name = "school_info";
	private $db;
	private $tab_sql = <<< SCHOOL_DB_SQL
CREATE TABLE school_info
(ID INT PRIMARY KEY NOT NULL,
SCHOOL_NAME TEXT NOT NULL,
SCHOOL_ID INT NOT NULL,
SCHOOL_GLOBAL_RANK INT,
SCHOOL_NATIONAL_RANK INT,
SCHOOL_SOCIAL_RANK INT,
SCHOOL_AREA TEXT,
SCHOOL_AREA_ID INT,
DEPARTMENT_NAME TEXT,
DEPARTMENT_ID INT,
DEPARTMENT_GLOBAL_RANK INT,
DEPARTMENT_NATIONAL_RANK INT,
DEPARTMENT_SOCIAL_RANK INT,
DEPARTMENT_NUMBER INT,
DEPARTMENT_EXEMPTION_NUMBER INT,
SUBJECT_NAME TEXT,
SUBJECT_ID INT,
SUBJECT_GLOBAL_RANK INT,
SUBJECT_NATIONAL_RANK INT,
SUBJECT_DEPARTMENT_RANK INT,
SUBJECT_SOCIAL_RANK INT,
SUBJECT_TYPE TEXT,
SUBJECT_NUMBER INT,
SUBJECT_EXEMPTION_NUMBER INT,
SUBJECT_EMAM_SUBJECTS TEXT,
RESEARCH_NAME TEXT,
RESEARCH_ID INT,
RESEARCH_GLOBAL_RANK INT,
RESEARCH_NATIONAL_RANK INT,
RESEARCH_SUBJECT_RANK INT,
RESEARCH_SOCIAL_RANK INT,
RESEARCH_NUMBER INT,
RESEARCH_EXEMPTION_NUMBER INT,
RESEARCH_EMAM_SUBJECTS TEXT,
RESEARCH_TEARCHER TEXT,
RESEARCH_CROSS_SUBJECT INT,
REMARK TEXT);

SCHOOL_DB_SQL;
	
	public function __construct(){
		
		//初始化db
		$cl_sqlite = new SQLite3(dirname(__FILE__) . $this->db_name);
		if(!$cl_sqlite){
			echo "db init open failed";
			return false;
		}
		$this->db = $cl_sqlite;
		$this->init_db_table();
		//echo "Success";
		return true;
	}
	public function init_db_table(){
		//echo $this->db_name;
		if(file_exists(dirname(__FILE__) . $this->db_name)){
			return true;
		}
		$sql = $this->tab_sql;
		$ret = $this->db->exec($sql);
		if(!$ret){			
			echo $this->db->lastErrorMsg();
		} 
		return $ret;		
	}	
	
	// exec only once
	public function init_db_data(){
		$file_name = dirname(__FILE__) . "/../dat/school_info.list";
		//echo "$file_name";
		
		if(!file_exists($file_name)){
			echo "file not exists:$file_name";
			return false;
		} 
		
		
		
		$fp = fopen($file_name, "r+");
		
		while(!feof($fp)){
			$a_school = fgetcsv($fp, 999, "\t");
			if(count($a_school)){
				echo $a_school[1] . "\n";
			}
			//var_dump($a_school);
			$school_name = $a_school[1];
			$school_id = $a_school[1];
			$school_arae = $a_school[4];
			$school_arae_id = $a_school[5];
			
		}
		
		var_dump($a_school);
		
		fclose($fp);
		
	}
	
}



$cl_school_info = new db_school_info();
$cl_school_info->init_db_table();
//var_dump($cl_school_info);
$cl_school_info->init_db_data();
