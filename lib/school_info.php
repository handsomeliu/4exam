<?php

//
/*
	从考研官网 抓取 当年录取学校列表
	
	
	by liulube@126.com

*/


date_default_timezone_set('PRC');
include dirname(__File__) . "/../pub/simple_html_dom.php";

class schoolspider {
	
	private $root = "http://yz.chsi.com.cn";
	private $s_root_url = "http://yz.chsi.com.cn/zsml/queryAction.do";
	private $s_url_ss = "http://yz.chsi.com.cn/zsml/pages/getSs.jsp";
	
	private $a_schools_list = array();
	private $s_parameters = "";
	
	
	private $s_filter_page_name = "pageno";
	private $a_filter = array(
			//省市
			"ssdm" => 11,
			//招生单位
			"dwmc" => '',
			//门类类别
			"mldm" => '',
			//学科类别
			"yjxkdm" => '',
			//专业名称
			"zymc" => '',
			
			"mlmc" => '',
			
			"pageno" => 1,
		
		);
	
	public function get_schoolslist($a_filter){	return $this->a_schools_list; } 
	public function set_filter($a_filter){ $this->a_filter = $a_filter; }
	private function get_filter(){ return $this->a_filter; }
	
	//**********************************************
	//获取学校列表
	//传入需要过滤的参数	
	//**********************************************
	public function spider_exec(){	
		//获取省市列表
		$a_ss = $this->get_list_ss();
		foreach($a_ss as $ss){
			$ss_id = $ss['dm'];
			//var_dump($ss_id);
			$this->a_filter["ssdm"] = $ss_id;
		
	
			//构造过滤条件获取根页面url
			$s_url = $this->get_url_root();
			//var_dump($s_url);
		
			//获取根页面上的子页面ID				
			$n_page = $this->get_schools_page_info($s_url);
			//$n_page = 1;
			while (false === $n_page){
				$n_page = $this->get_schools_page_info($s_url);
			}
			
			
			//循环抓取指定ID的数据，将数据录入掉数据库
			$s_url_child = $this->get_url_child();
			//var_dump($s_url_child);
			for($i = 1; $i <= $n_page; $i++){
				$s_url_crawl = $s_url_child . "$this->s_filter_page_name=$i";
				echo date("Y-m-d H-i-s") . ":spider start crawl:" . $s_url_child . "$this->s_filter_page_name=$i" . "\n";
				$flag = $this->get_schoolslist_by_url($s_url_crawl);
				while(false === $flag){
					echo date("Y-m-d H-i-s") . ":spider failed crawl:" . $s_url_child . "$this->s_filter_page_name=$i" . "\n";
					$flag = $this->get_schoolslist_by_url($s_url_crawl);
				}
			}
			//$this->get_schoolslist_by_url($s_url);
			
		}
		
	} 
	
	//构造root url
	private function get_url_root(){
		$a_filter = $this->a_filter;
		$s_parameters = "";
		foreach ($a_filter as $name => $values){
			$s_parameters = $s_parameters . "$name" .  "="  . "$values" . "&";
			
		}
		$this->s_parameters = $this->s_root_url . "?" .$s_parameters;
		return $this->s_parameters;
	}
	
	//构造child url without page id
	private function get_url_child(){
		$a_filter = $this->a_filter;
		$s_parameters = "";
		$s_url_child = "";
		foreach ($a_filter as $name => $values){
			//排除页面构造
				if("pageno" != $name){					
					$s_parameters = $s_parameters . "$name" .  "="  . "$values" . "&";				
				}				
		}
		
		$s_url_child = $this->s_root_url . "?" .$s_parameters;
		return $s_url_child;
	}
	
	//获取当前页面上都多少个子页面
	private function get_schools_page_info($url){					
		/* simplexml_load_file **************************
		$xml = simplexml_load_file("$xmlpatch");
	
		//var_dump($xml);
		$result = $xml->xpath("to");

		var_dump($result);		
		*************************************************/
		
		
		// simple_html_dom					
		//$html = new simple_html_dom();
		$ch2 = curl_init();
		echo "Crawl Root : $url \n";
		curl_setopt($ch2, CURLOPT_URL, $url);
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);	
		curl_setopt($ch2, CURLOPT_HEADER, 0);
		curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
		
		$html_content = curl_exec($ch2);
		//// 失败重试
		//$n_try = 1;
		if (curl_getinfo($ch2, CURLINFO_HTTP_CODE) !== 200 || "" == trim($html_content)) {
		//	$ret = curl_exec($ch2);
		//	$n_try++;
		//	echo "Crawl Root again $n_try \n";
		//	if($n_try > 5){
			return false;
		}
		//}
		$html = new simple_html_dom();
		$html->load("$html_content");
		
		//function find() return a array
		$e_page_info = $html->find('li[id=page_total]');
		if(0 == count($e_page_info)){
			return false;
		}
		//var_dump(count($e_page_info));			
		$s_page_info = $e_page_info[0]->plaintext;
		$a_tmp = explode("/" , $s_page_info);
		if(2 == count($a_tmp)){
			$n_page = $a_tmp[1];
			return $n_page;
		} else {
			return false;
		}		
		/*****************************************************
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		
		if( @ $doc->load( $xmlpatch ) ) {
			$root = $doc->documentElement;
			$elm = $root->getElementsByTagName('page_total');
		}
		var_dump($elm);
		******************************************************/			

	}	

	//根据page id 获取指定页面的学校信息
	private function get_schoolslist_by_url($url){
		
		//$url = $this->root_url;		
		//$url = "http://yz.chsi.com.cn/zsml/queryAction.do?ssdm=11&dwmc=&mldm=&mlmc=&yjxkdm=&zymc=&pageno=1";
		//echo date("H-i-s") . "\t";			
		$ch2 = curl_init();
		curl_setopt($ch2, CURLOPT_URL, $url);
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);	
		curl_setopt($ch2, CURLOPT_HEADER, 0);
		curl_setopt($ch2, CURLOPT_TIMEOUT, 20);
		
		$html = curl_exec($ch2);
		//// 失败重试
		//$n_try = 1;
		if (curl_getinfo($ch2, CURLINFO_HTTP_CODE) !== 200 || "" == trim($html)) {
		//	$ret = curl_exec($ch2);
		//	$n_try++;
		//	echo "Crawl url again $n_try \n";
		//	if($n_try > 3){
			return false;
		//	}
		}
		//print_r($html);
		curl_close($ch2);
		//echo date("H-i-s") . "\t";		
		$cl_html = new simple_html_dom();
		$cl_html->load($html);
		
		$a_schools = $cl_html->find('div[id=sch_list] tr');
		//echo count($a_schools);
		//echo date("H-i-s") . "\n";
		foreach($a_schools  as $e_school){
			//echo $e_school->innertext  . "\n";
			$this->parse_school_info($e_school->outertext);			
		}
		//echo date("H-i-s") . "\t";
		return true;
	}
	
	//根据截取的 schools html 分析提取学校信息返回指定结构
	private function parse_school_info($school_html){
		//echo $school_html . "\n";
		$cl_school = new school();
		$flag = $cl_school->parse_html($school_html);
		if(false == $flag){
			return false;
		}
		
		$a_school_info = array(
			'name' => $cl_school->get_name(),//学校名称
			'id' => $cl_school->get_id(),
			'url' => $cl_school->get_url(),
			'area' => $cl_school->get_area(),//学校地区
			'area_id' => $cl_school->get_area_id(),
			'level' => $cl_school->get_level(),//学校级别
			'dlyx' => $cl_school->get_dlyx(),//独立研究生院校
			'zzhx' => $cl_school->get_zzhx(),//自主招生
			'bsd' => $cl_school->get_bsd(),//博士点
		);
		//echo $a_school_info['name'] . "\n";
		//var_dump($a_school_info);
		$s_school_info = implode("\t", $a_school_info);
		echo "School_info:\t" . $s_school_info . "\n";
	}
	
	private function get_list_ss(){
		$content = file($this->s_url_ss);
		//var_dump($content);
		
		$s_ss = $content[0];
		$a_ss = json_decode($content[0], true);
		//var_dump($a_ss);
		return $a_ss;
	}
		
	
}

class subjectsipder {
	private $s_school_info_file_name =  "./../dat/school_info.list";
	private $cl_school;
	
	public function spider_exec(){
		$file_name = $this->s_school_info_file_name;
		//echo "$file_name";
		
		if(!file_exists($file_name)){
			echo "file not exists:$file_name";
			return false;
		} 
		
		
		
		$fp = fopen($file_name, "r+");
		
		while(!feof($fp)){
			$a_school = fgetcsv($fp, 999, "\t");
			if(count($a_school)){
				//echo $a_school[1] . "\n";
				$school_name = $a_school[1];
				$school_id = $a_school[2];
				$school_arae = $a_school[4];
				$school_arae_id = $a_school[5];
				$school_url = $a_school[3];
				$cl_school = new school();
				$cl_school->set_name($school_name);
				$cl_school->set_id($school_id);
				$cl_school->set_url($school_url);
				$cl_school->set_area($school_arae);
				$cl_school->set_area_id($school_arae_id);
				$cl_school->set_level($a_school[6]);
				$cl_school->set_dlyx($a_school[7]);
			    $cl_school->set_zzhx($a_school[8]);
				$cl_school->set_bsd($a_school[9]);
				
				$this->cl_school = $cl_school;
			}
			//var_dump($a_school);			
			
			//$this->get_subjects_by_url($school_url);
		}
		$this->get_subjects_by_url($school_url);
		//var_dump($a_school);
		
		fclose($fp);
		
	}
	
	//根据抓取的 学校专业列表 url 循环抓取专业相关的信息
	public function get_subjects_by_url($url){
		
		//查询列表页数
		$n_page = $this->get_subjects_page_info($url);
			//失败重试
			while(false === $n_page){			
				$n_page = $this->get_subjects_page_info($url);
			}
			//var_dump($n_page);
		//循环抓取专业列表
		for($i = 1; $i <= $n_page; $i++){
			$flag = $this->get_subjects_by_pageno($url, $i);
			//失败重试
			while(false === $n_page){			
				$flag = $this->get_subjects_by_pageno($url, $i);
			}
		}
		return true;
	}
	
	
	//根据学校专业的列表url 构造带页面的页面 抓取页面内容
	private function get_subjects_by_pageno($url, $n_page){
		
		$url = $url . "&pageno=$n_page";
		echo "Crawl url : \t" . $url . "\n";
		
		$ch2 = curl_init();
		curl_setopt($ch2, CURLOPT_URL, $url);
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);	
		curl_setopt($ch2, CURLOPT_HEADER, 0);
		curl_setopt($ch2, CURLOPT_TIMEOUT, 20);
		
		$html = curl_exec($ch2);

		if (curl_getinfo($ch2, CURLINFO_HTTP_CODE) !== 200 || "" == trim($html)) {
			return false;
		}
		curl_close($ch2);
		$cl_html = new simple_html_dom();
		$cl_html->load($html);
		$a_subjects = $cl_html->find('div[id=sch_list] tr');
		foreach($a_subjects  as $e_subject){
			//解析行日志
			$this->parse_subject_info($e_subject->outertext);			
			//echo $this->cl_school->get_name() . "\n";
			//echo $e_subject->outertext . "\n";
		}
		return true;			
	}
	
	//分析专业描述的列表 -- 解析行日志
	//输入 每行 的数据
	//输出专业 描述 结构体
	private function parse_subject_info($html){
		
		
		$cl_parse = new html_parse_yz();
		//
		//$cl_parse->set_cl_school($this->cl_school);
		
		// 分析抓取数据的每一个学校
		$cl_school = $this->cl_school;
		$cl_department = $cl_parse->parse_html($html, "department");
		if(!$cl_department){
			return false;
		}
		//var_dump($cl_department);
		$cl_subject = $cl_parse->parse_html($html, "subject");
		$cl_research = $cl_parse->parse_html($html, "research");
		
	echo $cl_school->get_name(). "\t"
	. $cl_school->get_id() . "\t"
	. $cl_department->get_name() . "\t"
	. $cl_department->get_id() . "\t"
	. $cl_subject->get_name() . "\t"
	. $cl_subject->get_id() . "\t"
	. $cl_research->get_name() . "\t"
	. $cl_research->get_id() . "\n";
		
		return true;
	}
	
	
	//分析学校的跟页面中 专业列表 有多少 子页面
	private function get_subjects_page_info($url){
		$ch2 = curl_init();
		//echo "Crawl Root : $url \n";
		curl_setopt($ch2, CURLOPT_URL, $url);
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);	
		curl_setopt($ch2, CURLOPT_HEADER, 0);
		curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
		
		$html_content = curl_exec($ch2);

		if (curl_getinfo($ch2, CURLINFO_HTTP_CODE) !== 200 || "" == trim($html_content)) {

			return false;
		}
		$html = new simple_html_dom();
		$html->load("$html_content");
		$e_page_info = $html->find('li[id=page_total]');
		if(0 == count($e_page_info)){
			return false;
		}
		$s_page_info = $e_page_info[0]->plaintext;
		$a_tmp = explode("/" , $s_page_info);
		if(2 == count($a_tmp)){
			$n_page = $a_tmp[1];
			return $n_page;
		} else {
			return false;
		}		
	}

}

class html_parse {
	
	private $html;
	
	public function get_html(){return $this->html;}
	
	public function set_html($value){$this->html = $value;return true;}
	
	public function parse_html(){}
		
}

class html_parse_yz extends html_parse {
	
	public function parse_html($html, $flag){
		$this->html = $html;
		switch ($flag){
			case "school":
				return $this->parse_html_school();
				break;
			case "department":
				$cl_department = $this->parse_html_department();
				return $cl_department;
				break;
			case "subject":
				return $this->parse_html_subject();
				break;
			case "research":
				return $this->parse_html_research();
				break;
			default :
				return false;
				break;
		}
	}
	public function parse_html_school(){
		//return class school object $cl_school
		
	}
	public function parse_html_department(){
		//return class department object $cl_department
		//echo deparment
		$html = $this->html;
		$cl_html = new simple_html_dom();
		$cl_html->load($html);
		$a_html = $cl_html->find('td');
		//$a_url = $cl_html->find('td a a');
		if(count($a_html)){		
			$cl_department = new department();
			$str_department = $a_html[0]->innertext;
			//id
			preg_match('(\([^\(\)]*\))',$str_department,$a_department_id);
			$n_department_id = substr($a_department_id[0], 1, 3);
			//name
			$a_tmp = explode(")", $str_department);
			$s_department_name = array_pop($a_tmp);
			//set object attribute			
			$cl_department->set_name($s_department_name);
			$cl_department->set_id($n_department_id);
			//echo $s_department_name. "\n";
		} else {
			return false;
		}
		return $cl_department;	
	}
	public function parse_html_subject(){
		//return class subject object $cl_subject
		//echo "subject";
		$html = $this->html;
		$cl_html = new simple_html_dom();
		$cl_html->load($html);
		$a_html = $cl_html->find('td');
		//$a_url = $cl_html->find('td a a');
		if(count($a_html)){		
			$cl_subject = new subject();
			$str_subject = $a_html[1]->innertext;
			//id
			preg_match('(\([^\(\)]*\))',$str_subject,$a_subject_id);
			$n_subject_id = substr($a_subject_id[0], 1, 5);
			//name
			$a_tmp = explode(")", $str_subject);
			$n_subject = count($a_tmp);
			$s_subject_name = array_pop($a_tmp);
			//type
			if($n_subject == 3){
				$n_type = 1;//专业学位
			} else {
				$n_type = 0;//学术型
			}
			//set object attribute
			$cl_subject->set_name($s_subject_name);
			$cl_subject->set_id($n_subject_id);
			$cl_subject->set_type($n_type);
			//echo $s_subject_name. "\n";
		} else {
			return false;
		}
		return $cl_subject;
	}
	public function parse_html_research(){
		//return class research object $cl_research		
		//echo "research";
		$html = $this->html;
		$cl_html = new simple_html_dom();
		$cl_html->load($html);
		$a_html = $cl_html->find('td');
		//$a_url = $cl_html->find('td a a');
		if(count($a_html)){		
			$cl_research = new research();
			$str_research = $a_html[2]->innertext;
			//id
			preg_match('(\([^\(\)]*\))',$str_research,$a_research_id);
			$n_research_id = substr($a_research_id[0], 1, 2);
			//name
			$a_tmp = explode(")", $str_research);
			$s_research_name = array_pop($a_tmp);
			//set object attribute			
			$cl_research->set_name($s_research_name);
			$cl_research->set_id($n_research_id);
			//echo $s_research_name . "\n";
		} else {
			return false;
		}
		return $cl_research;
	}
}


//学校
class school {
	
	private $root = "http://yz.chsi.com.cn";
	private $html = "";
	
	private $name;//学校名称
	private $id;
	private $url;
	private $area;//学校地区
	private $area_id;
	private $level;//学校级别
	private $dlyx;//独立研究生院校
	private $zzhx;//自主招生
	private $bsd;//博士点
	
	
	public function get_name	(){return $this->name;}
	public function get_id		(){return $this->id;}
	public function get_url		(){return $this->url;}
	public function get_area	(){return $this->area;}
	public function get_area_id	(){return $this->area_id;}
	public function get_level	(){return $this->level;}
	public function get_dlyx	(){return $this->dlyx;}
	public function get_zzhx	(){return $this->zzhx;}
	public function get_bsd		(){return $this->bsd;}
	
	
	public function set_name	($value){$this->name	= $value;return true;}
	public function set_id	($value){$this->id	= $value;return true;}
	public function set_url		($value){$this->url		= $value;return true;}
	public function set_area	($value){$this->area	= $value;return true;}
	public function set_area_id	($value){$this->area_id	= $value;return true;}
	public function set_level	($value){$this->level	= $value;return true;}
	public function set_dlyx	($value){$this->dlyx	= $value;return true;}
	public function set_zzhx	($value){$this->zzhx	= $value;return true;}
	public function set_bsd		($value){$this->bsd		= $value;return true;}
	
	
	private function set_html($html){ $this->html = $html;}
	 
	public function parse_html($html){
		$this->set_html($html);
		
		$school_html = $this->html;
		
		$cl_html = new simple_html_dom();
		$cl_html->load($school_html);
		
		$a_school_html = $cl_html->find('td');
		$a_school_url = $cl_html->find('td a a');
		//$a_url = $cl_html->find('td a a[href]');
		//var_dump($a_url[0]->href);
		
		//var_dump(count($a_school_html));
		
		if(6 == count($a_school_html)){
			//echo $a_school_url[0]->innertext . "\n";
			$str_name = $a_school_url[0]->innertext;
			//学校名称			
			$a_name = explode(")", $str_name);
			$this->name = $a_name[1];
			if(isset( $a_name[2])){
				$this->name = $this->name . ")";
			}
			if(null == $this->name){
				return false;
			}
			preg_match('(\([^\(\)]*\))',$str_name,$res_name);
			$this->id = substr($res_name[0], 1, 5);;			
			$this->url = $this->root . $a_school_url[0]->href;
						
			//学校地区
			$str_area = $a_school_html[1]->innertext;
			$this->area_id = substr($str_area, 1, 2);
			$a_area = explode(")", $str_area);
			$this->area = $a_area[1];
			
			if(strstr($a_school_html[2]->innertext, "985")){
				$this->level = 985;
			} elseif(strstr($a_school_html[2]->innertext, "211")) {
				$this->level = 211;
			} else {
				$this->level = 0;
				
			}
			
			$this->dlyx = trim($a_school_html[3]->innertext) == '&nbsp;' ? 0 : 1;
			$this->zzhx = trim($a_school_html[4]->innertext) == '&nbsp;' ? 0 : 1;
			$this->bsd = trim($a_school_html[5]->innertext) == '&nbsp;' ? 0 : 1;
			
			//var_dump($id, $name, $url, $area, $area_id, $dlyx, $zzhx, $bsd, $level);
			return true;
			
		}
	}
	
	
}

//学院
class department{
	private $cl_school;
	
	private $name;
	private $id;
	private $global_rank;
	private $national_rank;
	private $social_rank;
	private $number;
	private $examption_number;
		
	public function get_cl_school		(){return $this->cl_school;}
	public function get_name			(){return $this->name;}
	public function get_id				(){return $this->id;}
	public function get_global_rank		(){return $this->global_rank;}
	public function get_national_rank	(){return $this->national_rank;}
	public function get_social_rank		(){return $this->social_rank;}
	public function get_number			(){return $this->number;}
	public function get_examption_number(){return $this->examption_number;}
	
	public function set_cl_school		($value){$this->cl_school 		= $value; return true;}
	public function set_name			($value){$this->name			= $value; return true;}
	public function set_id				($value){$this->id				= $value; return true;}
	public function set_global_rank		($value){$this->global_rank		= $value; return true;}
	public function set_national_rank	($value){$this->national_rank	= $value; return true;}
	public function set_social_rank		($value){$this->social_rank		= $value; return true;}
	public function set_number			($value){$this->number			= $value; return true;}
	public function set_examption_number($value){$this->examption_number= $value; return true;}
	
	public function html(){
		echo "html";
		return;
	}
}

//专业
class subject {
	
	private $html;
	
	private $cl_school;
	private $cl_department;
	
	private $name;
	private $id;
	private $global_rank;
	private $national_rank;
	private $social_rank;
	private $department_rank;
	private $type;
	private $number;
	private $examption_number;

	public function get_html			(){return $this->html;}
    
	public function get_cl_school		(){return $this->cl_school;}
	public function get_cl_department	(){return $this->cl_department;}
	
	public function get_name	(){return $this->subject_name;}
	public function get_id		(){return $this->subject_id;}
	public function get_global_rank		(){return $this->global_rank;}
	public function get_national_rank	(){return $this->national_rank;}
	public function get_social_rank		(){return $this->social_rank;}
	public function get_department_rank	(){return $this->department_rank;}
	public function get_type			(){return $this->type;}
	public function get_number			(){return $this->number;}
    public function get_examption_number(){return $this->examption_number;}
	
	public function set_cl_school		($value){$this->cl_school 		= $value; return true;}
	public function set_cl_department	($value){$this->cl_department	= $value; return true;}
	
	public function set_name	($value){$this->subject_name	= $value; return true;}
	public function set_id		($value){$this->subject_id		= $value; return true;}
	public function set_global_rank		($value){$this->global_rank		= $value; return true;}
	public function set_national_rank	($value){$this->national_rank	= $value; return true;}
	public function set_social_rank		($value){$this->social_rank		= $value; return true;}
	public function set_department_rank	($value){$this->department_rank	= $value; return true;}
	public function set_type			($value){$this->type			= $value; return true;}
	public function set_number			($value){$this->number			= $value; return true;}
	public function set_examption_number($value){$this->examption_number= $value; return true;}
	
	public function parse_html($html){
		$this->html = $html;
		echo $html;
		$subject_html = $this->html;
		
		$cl_html = new simple_html_dom();
		$cl_html->load($subject_html);
		
		$a_school_html = $cl_html->find('td');
		$a_school_url = $cl_html->find('td a a');
		if(count($a_school_html)){
			echo $this->cl_school->get_name() . "\t" . $a_school_html[0]->innertext . "\n";
		}		
	}	
}

//方向
class research {
	private $html;
	
	private $cl_school;
	private $cl_department;
	private $cl_subject;
	
	private $name;
	private $id;
	private $global_rank;
	private $national_rank;
	private $social_rank;
	private $subject_rank;
	private $number;
	private $examption_number;
	private $exam_subjects;
	private $tearcher;
	private $cross_subject;
	private $remark;
	
	public function get_html			(){return $this->html;}
	 
	public function get_cl_school		(){return $this->cl_school;}
	public function get_cl_department	(){return $this->cl_department;}
	public function get_cl_subject		(){return $this->cl_subject;}
     
	public function get_name			(){return $this->name;}
	public function get_id				(){return $this->id;}
	public function get_global_rank		(){return $this->global_rank;}
	public function get_national_rank	(){return $this->national_rank;}
	public function get_social_rank		(){return $this->social_rank;}
	public function get_subject_rank	(){return $this->subject_rank;}
	public function get_number			(){return $this->number;}
	public function get_examption_number(){return $this->examption_number;}
	public function get_exam_subjects	(){return $this->exam_subjects;}
	public function get_tearcher		(){return $this->tearcher;}
	public function get_cross_subject	(){return $this->cross_subject;}
	public function get_remark			(){return $this->remark;}

	public function set_cl_school		($value){$this->cl_school 		= $value; return true;}
	public function set_cl_department	($value){$this->cl_department 	= $value; return true;}
	public function set_cl_subject		($value){$this->cl_subject 		= $value; return true;}

	public function set_name			($value){$this->name			= $value; return true;}
	public function set_id				($value){$this->id			= $value; return true;}
	public function set_global_rank		($value){$this->global_rank		= $value; return true;}
	public function set_national_rank	($value){$this->national_rank	= $value; return true;}
	public function set_social_rank		($value){$this->social_rank		= $value; return true;}
	public function set_subject_rank	($value){$this->subject_rank	= $value; return true;}
	public function set_number			($value){$this->number			= $value; return true;}
	public function set_examption_number($value){$this->examption_number= $value; return true;}
	public function set_exam_subjects	($value){$this->exam_subjects	= $value; return true;}
	public function set_tearcher		($value){$this->tearcher		= $value; return true;}
	public function set_cross_subject	($value){$this->cross_subject	= $value; return true;}
	public function set_remark			($value){$this->remark			= $value; return true;}
	
}

//$cl_schoolslist = new schoolspider();
//$cl_schoolslist->spider_exec();
//$cl_schoolslist->spider_exec();


?>
