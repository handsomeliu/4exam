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
			'name_id' => $cl_school->get_name_id(),
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
				$cl_school->set_name_id($school_id);
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
		//echo $url;
		
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
			$this->parse_subject_info($e_subject->outertext);			
			//echo $this->cl_school->get_name() . "\n";
			//echo $e_subject->outertext . "\n";
		}
		return true;			
	}
	
	//分析专业描述的列表 
	//输入每行的数据
	//输出专业 描述 结构体
	private function parse_subject_info($html){
		$cl_subject = new subject();
		
		$cl_subject->set_cl_school($this->cl_school);
		//var_dump($cl_subject);
		
		$cl_subject->parse_html($html);
		
		
		return true;
	}
	
	
	//分析学校的跟页面中 专业列表有多少列
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


class school {
	
	private $root = "http://yz.chsi.com.cn";
	private $html = "";
	
	private $name;//学校名称
	private $name_id;
	private $url;
	private $area;//学校地区
	private $area_id;
	private $level;//学校级别
	private $dlyx;//独立研究生院校
	private $zzhx;//自主招生
	private $bsd;//博士点
	
	
	public function get_name	(){return $this->name;}
	public function get_name_id	(){return $this->name_id;}
	public function get_url		(){return $this->url;}
	public function get_area	(){return $this->area;}
	public function get_area_id	(){return $this->area_id;}
	public function get_level	(){return $this->level;}
	public function get_dlyx	(){return $this->dlyx;}
	public function get_zzhx	(){return $this->zzhx;}
	public function get_bsd		(){return $this->bsd;}
	
	
	public function set_name	($value){$this->name	= $value;return true;}
	public function set_name_id	($value){$this->name_id	= $value;return true;}
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
			$this->name_id = substr($res_name[0], 1, 5);;			
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
			
			//var_dump($name_id, $name, $url, $area, $area_id, $dlyx, $zzhx, $bsd, $level);
			return true;
			
		}
	}
	
	
}

class subject {
	
	private $html;
	
	private $cl_school;
	private $department_name;
	private $department_id;
	private $subject_name;
	private $subject_id;
	private $research_name;
	private $research_id;
	private $tearcher;
	private $number;
	private $examption_number;
	private $exam_subjects;
	private $cross_subject;
	private $remark;
	
	public function get_cl_school		(){return $this->cl_school;}
	public function get_department_name	(){return $this->department_name;}	
	public function get_department_id	(){return $this->department_id;}
	public function get_subject_name		(){return $this->subject_name;}
	public function get_subject_id		(){return $this->subject_id;}
	public function get_research_name	(){return $this->research_name;}
	public function get_research_id		(){return $this->research_id;}
	public function get_tearcher			(){return $this->tearcher;}
	public function get_number			(){return $this->number;}
	public function get_examption_number	(){return $this->examption_number;}
	public function get_exam_subjects	(){return $this->exam_subjects;}
	public function get_cross_subject	(){return $this->cross_subject;}
	public function get_remark			(){return $this->remark;}

	
	public function set_cl_school($value){$this->cl_school = $value; return true;}
	
	public function parse_html($html){
		$this->html = $html;
		//echo $html;
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


//$cl_schoolslist = new schoolspider();
//$cl_schoolslist->spider_exec();
//$cl_schoolslist->spider_exec();


?>
