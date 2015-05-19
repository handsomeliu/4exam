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
(ID_INT INTEGER PRIMARY KEY,
ID TEXT UNIQUE,
SCHOOL_NAME TEXT NOT NULL,
SCHOOL_ID INT NOT NULL,
SCHOOL_GLOBAL_RANK INT,
SCHOOL_NATIONAL_RANK INT,
SCHOOL_SOCIAL_RANK INT,
SCHOOL_AREA TEXT,
SCHOOL_AREA_ID INT,
SCHOOL_LEVEL TEXT,
SCHOOL_DLYX INT,
SCHOOL_ZZHX INT,
SCHOOL_BSD INT,
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
//TODO : 数据库 添加 平均录取分 去年录取分数 过去五年录取分数 录取比例

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
		//if(file_exists(dirname(__FILE__) . $this->db_name)){
		//	//echo "";
		//	return true;
		//}
		$sql = $this->tab_sql;
		//echo $sql;
		@ $ret = $this->db->exec($sql);
		//if(!$ret){			
		//	echo $this->db->lastErrorMsg();
		//} 
		return $ret;		
	}	
	
	// exec only once
	public function init_db_data(){
		$file_name = dirname(__FILE__) . "/../dat/subject.test";
		//echo "$file_name";
		
		if(!file_exists($file_name)){
			echo "file not exists:$file_name";
			return false;
		} 
		
		
		
		$fp = fopen($file_name, "r+");
		
		while(!feof($fp)){
			$a_research = fgetcsv($fp, 999, "\t");
			if(count($a_research) > 10){
				//echo $a_research[1] . "\n";
			
				$a_research['99'] = NULL;
				$school_name                           = "$a_research[0]";
				$school_id                             = "$a_research[1]";
				$school_global_rank                    = "$a_research[99]";
				$school_national_rank                  = "$a_research[99]";
				$school_social_rank                    = "$a_research[99]";
				$school_area                           = "$a_research[2]";
				$school_area_id                        = "$a_research[3]";
				$school_level                          = "$a_research[4]";
				$school_dlyx                           = "$a_research[5]";
				$school_zzhx                           = "$a_research[6]";
				$school_bsd                            = "$a_research[7]";
				$department_name                       = "$a_research[8]";
				$department_id                         = "$a_research[9]";
				$department_global_rank                = "$a_research[99]";
				$department_national_rank              = "$a_research[99]";
				$department_social_rank                = "$a_research[99]";
				$department_number                     = "$a_research[10]";
				$department_exemption_number           = "$a_research[11]";
				$subject_name                          = "$a_research[12]";
				$subject_id                            = "$a_research[13]";
				$subject_global_rank                   = "$a_research[99]";
				$subject_national_rank                 = "$a_research[99]";
				$subject_department_rank               = "$a_research[99]";
				$subject_social_rank                   = "$a_research[99]";
				$subject_type                          = "$a_research[14]";
				$subject_number                        = "$a_research[99]";
				$subject_exemption_number              = "$a_research[99]";
				$research_name                         = "$a_research[15]";
				$research_id                           = "$a_research[16]";
				$research_global_rank                  = "$a_research[99]";
				$research_national_rank                = "$a_research[99]";
				$research_subject_rank                 = "$a_research[99]";
				$research_social_rank                  = "$a_research[99]";
				$research_number                       = "$a_research[99]";
				$research_exemption_number             = "$a_research[99]";
				$research_emam_subjects                = "$a_research[17]";
				$research_tearcher                     = "$a_research[99]";
				$research_cross_subject                = "$a_research[99]";
				$remark                                = "$a_research[99]";
				
			
				$id = $school_id .  $department_id . $subject_id . $research_id;
				$sql = "INSERT INTO school_info
						(ID_INT,ID, SCHOOL_NAME, SCHOOL_ID, SCHOOL_GLOBAL_RANK ,SCHOOL_NATIONAL_RANK ,SCHOOL_SOCIAL_RANK ,SCHOOL_AREA ,SCHOOL_AREA_ID ,SCHOOL_LEVEL ,SCHOOL_DLYX ,SCHOOL_ZZHX ,SCHOOL_BSD ,DEPARTMENT_NAME ,DEPARTMENT_ID ,DEPARTMENT_GLOBAL_RANK ,DEPARTMENT_NATIONAL_RANK ,DEPARTMENT_SOCIAL_RANK ,DEPARTMENT_NUMBER ,DEPARTMENT_EXEMPTION_NUMBER ,SUBJECT_NAME ,SUBJECT_ID ,SUBJECT_GLOBAL_RANK ,SUBJECT_NATIONAL_RANK ,SUBJECT_DEPARTMENT_RANK ,SUBJECT_SOCIAL_RANK ,SUBJECT_TYPE ,SUBJECT_NUMBER ,SUBJECT_EXEMPTION_NUMBER ,RESEARCH_NAME ,RESEARCH_ID ,RESEARCH_GLOBAL_RANK ,RESEARCH_NATIONAL_RANK ,RESEARCH_SUBJECT_RANK ,RESEARCH_SOCIAL_RANK ,RESEARCH_NUMBER ,RESEARCH_EXEMPTION_NUMBER ,RESEARCH_EMAM_SUBJECTS ,RESEARCH_TEARCHER ,RESEARCH_CROSS_SUBJECT ,REMARK ) 
						VALUES
						(NULL , '$id', '$school_name','$school_id','$school_global_rank','$school_national_rank','$school_social_rank','$school_area','$school_area_id','$school_level','$school_dlyx','$school_zzhx','$school_bsd','$department_name','$department_id','$department_global_rank','$department_national_rank','$department_social_rank','$department_number','$department_exemption_number','$subject_name','$subject_id','$subject_global_rank','$subject_national_rank','$subject_department_rank','$subject_social_rank','$subject_type','$subject_number','$subject_exemption_number','$research_name','$research_id','$research_global_rank','$research_national_rank','$research_subject_rank','$research_social_rank','$research_number','$research_exemption_number','$research_emam_subjects','$research_tearcher','$research_cross_subject','$remark')";
				
				@ $flag = $this->db->exec($sql);
				//var_dump($flag);
			}
		}
		fclose($fp);
		
	}
	
	public function test_select(){
		
		$a_test = $this->db->query("SELECT * FROM school_info");
		if(false === $a_test){
			echo "NULL";
			return false;
		}
		//var_dump($a_test);
		$i = 1;
		while($row = $a_test->fetchArray()){
			//var_dump($row);
			$i++;
		}
		var_dump($i);
	}
	
}



$cl_school_info = new db_school_info();
$cl_school_info->init_db_table();
//var_dump($cl_school_info);

$cl_school_info->init_db_data();
$cl_school_info->test_select();
