<?php
	class Meetings_MeetingroomController extends Shine_BaseAction
	{

		public function init()
		{
			parent::init();

			error_reporting(E_ALL);
			Zend_Loader::loadClass('MeetingPublish');
			Zend_Loader::loadClass('PublishUtils');
			Zend_Loader::loadClass('MissionUtils');
			Zend_Loader::loadClass('PublishTools');
			Zend_Loader::loadClass('TerminalSetup');
			Zend_Loader::loadClass('TerminalUtils');
			Zend_Loader::loadClass('MonitorUtils');
			Zend_Loader::loadClass('ModelPublish');
			//$m = "系统正在建设中！";
			//$e = iconv('gb2312','utf-8',$m);
			//echo $e;
			//exit;
		}
		
		function testgridAction(){
			$db =  Zend_Registry::get('meetings_db');
			$user = Zend_Auth::getInstance()->getStorage()->read();
			
			$params = $this->_getAllParams();
			$id = $params['id'];
			$room = array('Room_Id'=>0);
			$terminalids = array();
			if($id>0){
				$sql = "select * from meeting_room where Room_Id='$id'";
				$res = $db->query($sql);
				$row = $res->fetch();
				$room =$row;
				$floorsql = "select Building_Id from meeting_floor where Floor_Id='$row[Floor_Id]'";
				$floorres = $db->query($floorsql);
				$floorrow = $floorres->fetch();
				$room['Building_Id'] = $floorrow['Building_Id'];
				$sql = "select * from meeting_template where Template_Id='$room[Template_Id]'";
				$res = $db->query($sql);
				$row = $res->fetch();
				$tpljson = Zend_Json::decode($row['Template_Json']);
				$resolution = $tpljson['resolution']['width']."X".$tpljson['resolution']['height'];
				 $room['Template_Name']= $row['Template_Name']."(".$resolution.")";
				
				$sql = "select Terminal_ID from room_terminal where Room_ID='$id'";
				$res = $db->query($sql);
				$row = $res->fetchAll();
				foreach($row as $item){
					$terminalids[] = $item['Terminal_ID'];	
				}

			}
			
			
			if($user->id == 1){
				$this->view->userStructureId = -1;
			}else{
				$this->view->userStructureId = $user->Structure_ID;	
			}
			$this->view->room = Zend_Json::encode($room);
			$this->view->terminalids = Zend_Json::encode($terminalids);
		}
		function indexAction()
		{
			$db =  Zend_Registry::get('meetings_db');
			$params = $this->_getAllParams();
			$id = @$params['id'];
			$room = array('Room_Id'=>0);
			$terminalids = array();
			if($id>0){
				$sql = "select * from meeting_room where Room_Id='$id'";
				$res = $db->query($sql);
				$row = $res->fetch();
				$room =$row;
				$floorsql = "select Building_Id from meeting_floor where Floor_Id='$row[Floor_Id]'";
				$floorres = $db->query($floorsql);
				$floorrow = $floorres->fetch();
				$room['Building_Id'] = $floorrow['Building_Id'];
				$sql = "select * from meeting_template where Template_Id='$room[Template_Id]'";
				$res = $db->query($sql);
				$row = $res->fetch();
				$tpljson = Zend_Json::decode($row['Template_Json']);
				$resolution = $tpljson['resolution']['width']."X".$tpljson['resolution']['height'];
				 $room['Template_Name']= $row['Template_Name']."(".$resolution.")";
				
				$sql = "select Terminal_ID from room_terminal where Room_ID='$id'";
				$res = $db->query($sql);
				$row = $res->fetchAll();
				foreach($row as $item){
					$terminalids[] = $item['Terminal_ID'];	
				}

			}
			$this->view->room = Zend_Json::encode($room);
			$this->view->terminalids = Zend_Json::encode($terminalids);
		}

		/**********
			2014/2/22
			显示树形表格数据
		*********/
		function showtreegridAction(){
			$db = Zend_Registry::get('db');	
			$meetinsDB =  Zend_Registry::get('meetings_db');
			
			$tms = TerminalUtils::getMeetingTerminals();
			$params = $this->_getAllParams();
			$pid = isset($params['node'])?$params['node']:0;
			$roomId = isset($params['room'])?$params['room']:-1;
			$screen = isset($params['screen'])?$params['screen']:-1;
			$SeletedIds = Zend_Json::decode($params['ids']);
			if($roomId == -1){
				echo "[]";
				exit;
			}
			$meetSql = "select Terminal_ID from room_terminal  where Room_ID !='$roomId'";
			$meetRes = $meetinsDB->query($meetSql);
			$meetRow = $meetRes->fetchAll();
			

			$terminaldata = array();
			foreach($tms as $a){
				foreach($meetRow as $meet){
					if($meet['Terminal_ID'] == $a['id']){
						continue 2;
					}
				}
				if($a['ScreenDirect'] == $screen){
					$terminaldata[] = $a;	
				}
			}
			$sql = "select GroupName ,GroupID,Level,ParentID from terminalgroup ";
			$res = $db->query($sql);
			$row = $res->fetchAll();
			

			$groupsql = "select GroupName ,GroupID,Level,ParentID from terminalgroup where ParentID='$pid'";
			$groupres = $db->query($groupsql);
			$grouprow = $groupres->fetchAll();
			
			$data = array();
			foreach($grouprow as $item){
				$m = false;
				$n = false;
				foreach($row as $g){
					if($item['GroupID'] == $g['ParentID']){
						$m = true;
					}	
				}
				foreach($terminaldata as $o){
					if($o['GPID'] == $item['GroupID']){
						$n = true;
					}
				}
				
				$data[] = array(
					'id'=>$item['GroupID'],
					'name'=>($item['GroupName']),
					'nodeType'=>"async",
					'iconCls'=>'task-folder',
					'expanded'=>($m or $n),//如果没有终端也没有子文件夹，就不要展开显示了。
					'IP'=>'-',
					'resolution'=>'-',
					'leaf'=>false//,
					//"checked"=>false
				);
			}
			foreach($terminaldata as $e){
				if($e['GPID'] == $pid){
					$check = false;
					foreach($SeletedIds as $ID){
						if($e['id'] == $ID){
							$check = true;
						}
					}
					$data[] = array(
						'id'=>$e['id'],
						'name'=>$e['tmname'],
						'nodeType'=>"async",
						'iconCls'=>'task', 
						'IP'=>$e['ip'],
						'resolution'=>$e['resolutionText'],
						'leaf'=>true,
						"checked"=>$check
					);
				}
			}
			echo json_encode($data);
			exit;

		}

		/***********
			2013/12/19
			会议安排网页
		***********/
		function webroomAction(){
			
		
		}
		
		/***********
			2013/12/18
			显示终端列表
		************/
		function showterminalsAction(){

			  $db =  Zend_Registry::get('db');
			  $meetingdb =  Zend_Registry::get('meetings_db');
			  $param = $this->_getAllParams();
			  $tmpterminalids = @$param['tmpterminalids'];//临时选中的终端id
			  $tmpdeletetmids = @$param['tmpdeletetmids'];//临时取消的终端id
			  $roomid = $param['roomid'];
			  $roomsql = "SELECT Terminal_ID ,Room_ID FROM `room_terminal` where Room_ID!='$roomid' ";
			  $roomres = $meetingdb->query($roomsql);
			  $roomrow = $roomres->fetchAll();
			  $room_terminal_id = array();
			  foreach($roomrow as $item){
				$room_terminal_id[] = $item['Terminal_ID'];
			  }
		
			  $start = @$param['start'];
			  $limit = @$param['limit'];
			  $show  = @$param['show'];
			  $subtmidslist = @$param['tmids'];	//如果有值，说明是刚进入修改页面

			  $mis_tm = array();
			  $mis_tm_id_array = array();

			  /*根据分组ID获取终端ID*/
			  $tm_id_list = '';
			  $GP_ids = @$param['gids'];
			  $scn	  = @$param['subsearch'];
			  $search_value  = @$param['value'];
			  $add_tms = @$param['addtms'];
			  $search_tm_ids = '';//通过高级搜索或者按字段查询获取的终端id
			 
			  if($GP_ids or $add_tms){//按分组查询

				  $gpid_sql = "select * from terminal_terminalgroup where GroupID IN ($GP_ids) ";
				  $gpid_res = $db->query($gpid_sql);
				  $gpid_row = $gpid_res->fetchAll();
				  $tm_id_array = array();

				  foreach($gpid_row as $gp) {
					$tm_id_array[] = $gp["TerminalID"];
				  
				  }

				  if($add_tms){//新建播出单刚进入显示终端页面的时候，要显示当前用户的所有的终端

					$add_sql = "select * from terminal_terminalgroup";
					$add_res = $db->query($add_sql);
					$add_row = $add_res->fetchAll();
					foreach($add_row as $v){
						$tm_id_array[] = $v['TerminalID'];
					}
				  
				  }

				  $tm_id_list = join(',',$tm_id_array);
			 }
		
			  $tm_id_list = join(',',array_unique(array_merge(explode(',',$tm_id_list),explode(',',$tmpterminalids))));

			  if($subtmidslist){//刚进入修改页面的时候没有搜索到任何的记录，只有上次保存的记录，如果在执行排序的时候就需要将上次保存的终端id传回来作为终端查询的条件
			  	$tm_id_list = $subtmidslist;
			  
			  }
			 
			  $tmp_ids_array = array();
			  $tmp_delete_tm_array = explode(',',$tmpdeletetmids);
			  if($tm_id_list || count($mis_tm_id_array)){//下面的代码作用：选择其中一些记录，然后继续搜索的时候，还可以显示上次搜索的记录
	
				 
				$mis_tm_id_array = array_diff(array_merge($mis_tm_id_array,explode(',',$tmpterminalids)),$tmp_delete_tm_array);

				$tmp_select_tm = array_unique(array_merge($mis_tm_id_array,explode(',',$tm_id_list),explode(',',$tmpterminalids)));
				
				 foreach($tmp_select_tm as $v){//去除空白字符
					if($v){
						$tmp_ids_array[] = $v;
					
					}
				 
				 }
				 $tmp_ids_array = array_diff($tmp_ids_array,$room_terminal_id);
				 $tm_id_list = join(',',$tmp_ids_array); 
			  }

				
			  $row = TerminalUtils::getTerminalInfo($tm_id_list,$param,$search_tm_ids);
			  //var_dump($row);

			  $tm_row_id = array();
			  
			  
			  $bktms = $row[0];
			  $tmp = array();
			  $sel_tm_ids = array_diff($mis_tm_id_array,$tmp_delete_tm_array);

			  foreach($bktms as $v){
				if(in_array($v['id'],$sel_tm_ids)){
					$tmp[] = array_merge($v,array('sel'=>1));
				
				}else{
					$tmp[] = array_merge($v,array('sel'=>0));
				
				}
			  
			  }

			  $num = $row[1];
			  $arr = array('totalProperty' => $num, 'root' =>$tmp);
			  echo Zend_Json::encode($arr);
			  exit;
		
		}

		/************
			2013/12/17
			删除楼层信息
		**********/
		function deletefloorAction(){
			$db =  Zend_Registry::get('meetings_db');
			$params = $this->_getAllParams();
			$id = $params['id'];
			$sql = "select count(*) as num  FROM `meeting_room` where Floor_Id='$id'";
			$res = $db->query($sql);
			$row = $res->fetch();
			
			if($row['num']>0){
				echo 0;
				exit;
			}

			if($id){
				$sql = "delete from meeting_floor where Floor_Id in($id)";
				$db->query($sql);
			}
			echo 1;
			exit;
		}


		/***********
			2013/12/17
			添加楼层信息
		***********/
		function addfloorAction(){
			$db =  Zend_Registry::get('meetings_db');
			$params = $this->_getAllParams();
			$Building_Id = $params['Building_Id'];
			$Floor_Id = $params['Floor_Id'];
			$Floor_Name = $params['Floor_Name'];

			$rowset = array(
				'Building_Id'=>$Building_Id,
				'Floor_Name'=>$Floor_Name
			);
			if($Floor_Id == 0){
				$sql = "select count(*) as num from meeting_floor where Floor_Name='$Floor_Name' and Building_Id='$Building_Id'";
				
			}else{
				$sql =  "select count(*) as num from meeting_floor where Floor_Name='$Floor_Name' and Floor_Id!='$Floor_Id'  and Building_Id='$Building_Id'";
			}
			$res = $db->query($sql);
			$row = $res->fetch();
			if($row['num']>0){
				$data = array('code'=>0);
				echo json_encode($data);
				exit;
			}
			
			if($Floor_Id == 0){
				$db->insert("meeting_floor",$rowset);
			}else{
				$where = $db->quoteInto('Floor_Id = ?', $Floor_Id);
				$rows_affected = $db->update("meeting_floor", $rowset, $where);

			}
			
			$data = array('code'=>1);
			echo json_encode($data);
			exit;
		}


		/**********
			2013/12/17
			删除大楼信息
		**********/
		function deletebuildingAction(){
			$db =  Zend_Registry::get('meetings_db');
			$params = $this->_getAllParams();
			$id = $params['Building_Id'];
			$buildsql = "select Floor_Id from meeting_floor where Building_Id ='$id'";
			$buildres = $db->query($buildsql);
			$buildrow = $buildres->fetchAll();

			$num = 0;
			foreach($buildrow as $item){
				$sql = "select * FROM `meeting_room` where Floor_Id='$item[Floor_Id]'";
				$res = $db->query($sql);
				$row = $res->fetchAll();
				foreach($row as $rt){
					$RoomID = $rt['Room_Id'];
					if($rt['Room_Id']){
						$missionsql = "select count(*) as num from meeting_mission where Room_Id='$RoomID'";
						$missionres = $db->query($missionsql);
						$missionrow = $missionres->fetch();
						if($missionrow['num']>0){
							$num = $missionrow['num'];
						}
					}
				}
			}
			if($num>0){
				echo 0;
				exit;
			}
			$sql = "delete from meeting_building where Building_Id='$id'";
			$db->query($sql);
			$sql = "delete from meeting_floor where Building_Id='$id'";
			$db->query($sql);
			echo 1;
			exit;
		
		}
		
		/************
			2013/12/17
			添加大楼信息
		***********/
		function addbuildingAction(){
			$db =  Zend_Registry::get('meetings_db');
			$params = $this->_getAllParams();
			$name = $params['Building_Name'];
			$id = $params['Building_Id'];

			$rowset = array(
				'Building_Name'=>$name
			);

			if($id == 0){
				$sql = "select count(*) as num from meeting_building where Building_Name='$name'";
			}else{
				$sql = "select count(*) as num from meeting_building where Building_Name='$name' and Building_Id!='$Building_Id'";
			}

			$res = $db->query($sql);
			$row = $res->fetch();
			if($row['num']>0){
				$data = array("code"=>0);
				echo json_encode($data);
				exit;
			}

			if($id == 0){
				$db->insert("meeting_building",$rowset);
				$NewID = $db->lastInsertId();
				$floor = "第一层";
				$floor = ($floor);
				$floorset = array(
					'Building_Id'=>	$NewID,
					'Floor_Name'=>$floor
				);
				$db->insert("meeting_floor",$floorset);
			}else{
				$where = $db->quoteInto('Building_Id = ?',$id);

				// 更新表数据,返回更新的行数
				$rows_affected = $db->update("meeting_building", $rowset, $where);

			}
			$data = array("code"=>1);
			echo json_encode($data);
			exit;
		}

		/*********
			2013/12/17
			获取楼层信息
		*******/
		function showfloorAction(){
			$params = $this->_getAllParams();
			$Building_Id = isset($params['Building_Id'])?$params['Building_Id']:0;
			$db =  Zend_Registry::get('meetings_db');
			$sql = "select * from meeting_floor where Building_Id='$Building_Id' order by Floor_Order, Floor_Id  asc";
			$res = $db->query($sql);
			$row = $res->fetchAll();
			echo json_encode($row);
			exit;
		}

		/********
			2014/3/17
			楼层排序
		********/
		function floororderAction(){
			$params = $this->_getAllParams();
			$data = Zend_Json::decode($params['data']);
			$db =  Zend_Registry::get('meetings_db');
			foreach($data as $item){
				$sql = "update meeting_floor set Floor_Order='$item[order]' where Floor_Id='$item[id]'";
				$db->query($sql);
			}
			echo 1;
			exit;

		
		}

		/**********
			2013/12/17
			获取大楼信息
		**********/
		function showbuildingAction(){
			$db =  Zend_Registry::get('meetings_db');
			$sql = "select * from meeting_building order by Building_Order asc";
			$res = $db->query($sql);
			$row = $res->fetchAll();
			echo json_encode($row);
			exit;
		
		}

		/**********
			2014/3/17
			排序大楼
		*********/
		function buildingorderAction(){
			$params = $this->_getAllParams();
			$data = Zend_Json::decode($params['data']);
			$db =  Zend_Registry::get('meetings_db');
			foreach($data as $item){
				$sql = "update meeting_building set Building_Order='$item[order]' where Building_Id='$item[id]'";
				$db->query($sql);
			}
			echo 1;
			exit;	
		
		}

		/**********
			2013/10/22
			获取会议室时间段
		***********/
		function getmeetingtimeAction(){
			$db =  Zend_Registry::get('meetings_db');
			$sql = "select * from meeting_config where Config_Type='meetingtime'";
			$res = $db->query($sql);
			$row = $res->fetch();
			echo $row['Config_Data'];
			exit;
		
		
		}

		/**********
			2013/10/22
			设置会议室时间段
		***********/
		function setmeetingtimeAction(){
			$db =  Zend_Registry::get('meetings_db');
			$params = $this->_getAllParams();
			$data = $params['data'];
			$sql = "update meeting_config set Config_Data='$data' where Config_Type='meetingtime'";
			$db->query($sql);
			echo 1;
			exit;
		
		
		}


		/*******
			2013/5/8
			显示会议室信息
		******/
		function showroomlistAction(){
			$params = $this->_getAllParams();

			$db =  Zend_Registry::get('meetings_db');
			$buildsql = "SELECT * FROM `meeting_building` ";
			$buildres = $db->query($buildsql);
			$buildrow = $buildres->fetchAll();
			$buildings = array();
			foreach($buildrow as $item){
				$buildings[$item['Building_Id']] = $item['Building_Name'];
			}
			
			$floorsql = "SELECT * FROM `meeting_floor` ";
			$floorres = $db->query($floorsql);
			$floorrow = $floorres->fetchAll();
			$floors = array();
			foreach($floorrow as $floordata){
				$floors[$floordata['Floor_Id']] = array("floor"=>$floordata['Floor_Name'],'build'=>$floordata['Building_Id']);
			}
			
			$roomuserdata = Zend_Json::decode($params['roomids']);
		
			$sql = "select * from  meeting_room";
			$res = $db->query($sql);
			$row = $res->fetchAll();
			
			$tplsql = "select * from meeting_template";
			$tplres = $db->query($tplsql);
			$tplrow = $tplres->fetchAll();
			$tpldata = array();
			$screendata = array();
			foreach($tplrow as $tplitem){
				$tpljson = Zend_Json::decode($tplitem['Template_Json']);
				$resolution = $tpljson['resolution']['width']."X".$tpljson['resolution']['height'];
				$screendata[$tplitem['Template_Id']] =  $resolution;
				$tpldata[$tplitem['Template_Id']] = $tplitem['Template_Name']."(".$resolution.")";
			}
			try{
				$terminals = MeetingPublish::GetTerminals();
			}catch(Exception $e){
				$terminals = array();
			}
			
			$d = array();
			foreach($row as $key=>$val){
				if(!in_array($val['Room_Id'],$roomuserdata)){
					continue;
				}
				$row[$key]['Terminal'] = $terminals[$val['Room_Id']];
				$row[$key]['Template_Name'] = $tpldata[$val['Template_Id']];
				$row[$key]['Template_Screen'] = $screendata[$val['Template_Id']];
				$row[$key]['Floor_Name'] = @$floors[$val['Floor_Id']]['floor'];
				$row[$key]['Building_Name'] = @$buildings[$floors[$val['Floor_Id']]['build']];
				$d[] = $row[$key];
			}
	
			echo Zend_Json::encode($d);
			exit;
		}


		/*******
			2013/5/8
			显示会议室信息
		******/
		function showroomAction(){
			$params = $this->_getAllParams();

			$db =  Zend_Registry::get('meetings_db');
			$buildsql = "SELECT * FROM `meeting_building` ";
			$buildres = $db->query($buildsql);
			$buildrow = $buildres->fetchAll();
			$buildings = array();
			foreach($buildrow as $item){
				$buildings[$item['Building_Id']] = $item['Building_Name'];
			}
			
			$floorsql = "SELECT * FROM `meeting_floor` ";
			$floorres = $db->query($floorsql);
			$floorrow = $floorres->fetchAll();
			$floors = array();
			foreach($floorrow as $floordata){
				$floors[$floordata['Floor_Id']] = array("floor"=>$floordata['Floor_Name'],'build'=>$floordata['Building_Id']);
			}
			
			$roomuserdata = array();
			if(@$_SESSION["meetingUser"] and @$_SESSION["meetingPassword"]){
				$roomuser = "select Meeting_Room_IDS  from meeting_user where User_Name ='$_SESSION[meetingUser]' and User_Password='$_SESSION[meetingPassword]'";
				$roomres = $db->query($roomuser);
				$roomrow = $roomres->fetch();
				$roomuserdata = Zend_Json::decode($roomrow['Meeting_Room_IDS']);
			}

			$user = Zend_Auth::getInstance()->getStorage()->read();
			
			if($user){
				$userid = $user->id;
				$roomuserdata = array();	
			}else{
				$userid = null;
			}
			$sql = "select * from  meeting_room";
			$res = $db->query($sql);
			$row = $res->fetchAll();
			
			$tplsql = "select * from meeting_template";
			$tplres = $db->query($tplsql);
			$tplrow = $tplres->fetchAll();
			$tpldata = array();
			$screendata = array();
			foreach($tplrow as $tplitem){
				$tpljson = Zend_Json::decode($tplitem['Template_Json']);
				$resolution = $tpljson['resolution']['width']."X".$tpljson['resolution']['height'];
				$screendata[$tplitem['Template_Id']] =  $resolution;
				$tpldata[$tplitem['Template_Id']] = $tplitem['Template_Name']."(".$resolution.")";
			}
			try{
				$terminals = MeetingPublish::GetTerminals();
			}catch(Exception $e){
				$terminals = array();
			}
			
			$d = array();
			foreach($row as $key=>$val){
				if(!in_array($val['Room_Id'],$roomuserdata) and $params['showall'] == 0 and $params['MeetingAction'] == 0 and !$userid){
					continue;
				}
				$row[$key]['Terminal'] = Zend_Json::encode(@$terminals[$val['Room_Id']]);
				$row[$key]['Template_Name'] = $tpldata[$val['Template_Id']];
				$row[$key]['Template_Screen'] = $screendata[$val['Template_Id']];
				$row[$key]['Floor_Name'] = @$floors[$val['Floor_Id']]['floor'];
				$row[$key]['Building_Name'] = @$buildings[$floors[$val['Floor_Id']]['build']];
				$d[] = $row[$key];
			}
	
			echo Zend_Json::encode($d);
			exit;
		}
		
		/*******
			2014/3/17
			排序会议室
		********/
		function meetingroomorderAction(){
			$params = $this->_getAllParams();
			$data = Zend_Json::decode($params['data']);
			$db =  Zend_Registry::get('meetings_db');
			foreach($data as $item){
				$sql = "update meeting_room set  Room_Order='$item[order]' where Room_Id='$item[id]'";
				$db->query($sql);
			}
			echo 1;
		
		}

		/*******
			2013/5/8
			显示会议室信息
		******/
		function showmeetingroomAction(){

			$params = $this->_getAllParams();
			$Floor_Id = isset($params['Floor_Id'])?$params['Floor_Id']:0;
			$db =  Zend_Registry::get('meetings_db');
			$sql = "select * from  meeting_room where Floor_Id ='$Floor_Id' order by  Room_Order   asc";
			$res = $db->query($sql);
			$row = $res->fetchAll();
			echo Zend_Json::encode($row);
			exit;
		}

		/******
			2013/5/8
			创建会议室记录
		*******/
		function createroomAction(){
			$db =  Zend_Registry::get('meetings_db');
			$user = Zend_Auth::getInstance()->getStorage()->read();
			
			$params = $this->_getAllParams();
			$id = $params['id'];
			$room = array('Room_Id'=>0);
			$terminalids = array();
			if($id>0){
				$sql = "select * from meeting_room where Room_Id='$id'";
				$res = $db->query($sql);
				$row = $res->fetch();
				$room =$row;
				$floorsql = "select Building_Id from meeting_floor where Floor_Id='$row[Floor_Id]'";
				$floorres = $db->query($floorsql);
				$floorrow = $floorres->fetch();
				$room['Building_Id'] = $floorrow['Building_Id'];
				$sql = "select * from meeting_template where Template_Id='$room[Template_Id]'";
				$res = $db->query($sql);
				$row = $res->fetch();
				$tpljson = Zend_Json::decode($row['Template_Json']);
				$resolution = $tpljson['resolution']['width']."X".$tpljson['resolution']['height'];
				 $room['Template_Name']= $row['Template_Name']."(".$resolution.")";
				
				$sql = "select Terminal_ID from room_terminal where Room_ID='$id'";
				$res = $db->query($sql);
				$row = $res->fetchAll();
				foreach($row as $item){
					$terminalids[] = $item['Terminal_ID'];	
				}

			}
			
			
			if($user->id == 1){
				$this->view->userStructureId = -1;
			}else{
				$this->view->userStructureId = $user->Structure_ID;	
			}
			$this->view->room = Zend_Json::encode($room);
			$this->view->terminalids = Zend_Json::encode($terminalids);

			
		}

		/******
			2013/5/9
			保存会议室信息
		******/
		function submitroomAction(){
			$params = $this->_getAllParams();
			$templateId = $params['templateid'];
			$terminalIds = $params['terminalids'];
			$roomname = $params['roomname'];
			$roomid = $params['roomid'];
			$Floor_Id = $params['Floor_Id'];
			$Room_Screen = $params['RoomScreen'];
			
			$roommsg = "";
			$db =  Zend_Registry::get('meetings_db');
			
			if($roomid > 0){
				$roommsg = "修改会议室【".$roomname."】";
				$sql = "select count(*) as num from meeting_room where Room_Name='$roomname' and Room_Id!='$roomid'";
				$res = $db->query($sql);
				$row = $res->fetch();
				if($row['num']>0){
					echo "{success: 0, code: 0}";
					exit;
				}

				$set = array (
					'Room_Name'=>$roomname,
					'Template_Id'=>$templateId,
					'Floor_Id'=>$Floor_Id,
					'Room_Screen'=>$Room_Screen
				);
				$table = 'meeting_template';
				$where = $db->quoteInto('Room_Id = ?', $roomid);
				$ret = $db->update("meeting_room", $set, $where);

				$sql = "delete from room_terminal where Room_ID='$roomid'";
				$res = $db->query($sql);

				$terminalIdList = explode(',',$terminalIds);
				$terminalIdList = array_unique($terminalIdList);
				foreach($terminalIdList as $item){
					$data = array(
						'Room_ID'=>$roomid,
						'Terminal_ID'=>$item
					);	
					$ret = $db->insert('room_terminal', $data);
				}
			}else{
				$roommsg = "添加会议室【".$roomname."】";
				$sql = "select count(*) as num from meeting_room where Room_Name='$roomname'";
				$res = $db->query($sql);
				$row = $res->fetch();
				if($row['num']>0){
					echo "{success: 0, code: 0}";
					exit;
				}
				$row = array(
					'Room_Name'=>$roomname,
					'Template_Id'=>$templateId,
					'Floor_Id'=>$Floor_Id,
					'Room_Screen'=>$Room_Screen
				);
				$ret = $db->insert('meeting_room', $row);
				$roomId = $db->lastInsertId();

				$terminalIdList = explode(',',$terminalIds);
				$terminalIdList = array_unique($terminalIdList);
				foreach($terminalIdList as $item){
					$data = array(
						'Room_ID'=>$roomId,
						'Terminal_ID'=>$item
					);	
					$ret = $db->insert('room_terminal', $data);
				}
			
			}
			
			$logset = array(
				'm' => '会议模块会议室管理', 
				'act' =>$roommsg
			);			
			Shine_Logger::getInstance()->log($logset);
			echo "{success: 1, code: 0}";
			exit;	
		}

		/******
			2013/5/10
			删除会议室信息
		*****/
		function deleteroomAction(){
			$params = $this->_getAllParams();
			$ids = $params['ids'];
			$db =  Zend_Registry::get('meetings_db');
			$sql = "select count(*) as num from meeting_mission where Room_Id in($ids)";
			$res = $db->query($sql);
			$row = $res->fetch();

			$usersql = "select Meeting_Room_IDS from meeting_user";
			$userres = $db->query($usersql);
			$userrow = $userres->fetchAll();
			$userdata = array();
			foreach($userrow as $item){
				$userdata = @array_merge($userdata,Zend_Json::decode($item['Meeting_Room_IDS']));
			}
			$userdata = @array_unique($userdata);
			if($row['num']>0 or count(array_intersect(explode(',',$ids),$userdata))){
				echo 1;
				exit;
			}

			$roomsql = "select * from meeting_room where Room_Id in($ids) ";
			$roomres = $db->query($roomsql);
			$roomrow = $roomres->fetchAll();
			foreach($roomrow as $roomitem){
				$logset = array(
					'm' => '会议模块会议室管理', 
					'act' =>"删除会议室【".$roomitem['Room_Name']."】"
				);			
				Shine_Logger::getInstance()->log($logset);
			}

			$sql = "delete from meeting_room where Room_Id in($ids)";
			$res = $db->query($sql);

			$sql = "delete from room_terminal where Room_ID in($ids)";
			$res = $db->query($sql);
			echo 0;
			exit;	
		
		}


	}
?>
