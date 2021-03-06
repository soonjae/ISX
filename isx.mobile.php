<?php
/**
 * @class  integration_searchMobile
 * @author 카르마 (soonj@nate.com)
 * @brief  ISX module의 mobile class 
 *	
 **/

class isxMobile extends isx
{

	/**
	 * @brief 초기화
	 **/
	var $target_mid = array();
    /**
     * Skin
     * @var string skin name
     */
    var $skin = 'isxdefault';

    /**
     * Initialization
     *
     * @return void
     */
	function init()
	{
		$oModuleModel = &getModel('module');
		$this->isxconfig = $oModuleModel->getModuleConfig('isx');
		Context::set('module_config',$this->isxconfig);

	}

	function IS()
	{
		return $this->ISX();
	}

	function triggerDisplay()
	{
		$oModuleModel = &getModel('module');
		$isxconfig = $oModuleModel->getModuleConfig('isx');
		if($isxconfig->ac_use == 'N') return;
		if(Context::get('module') == 'admin') return;
		if($isxconfig->ac_use == 'Y')
		{
			if($isxconfig->ac_source == 'key')
                $target = getSiteUrl()."modules/isx/isx.key_query.php";
            else
                $target = getSiteUrl()."modules/isx/isx.document_query.php";

			Context::addCSSFile("./modules/isx/tpl/css/jquery.autocomplete.css", false);
			Context::addJsFile('./modules/isx/tpl/js/jquery.autocomplete.js',false,'',null,'');
			$scr = array();
            $scr[] = "<script type=\"text/javascript\">(function($){";
            if($isxconfig->get_use == 'Y')
            {
                $scr[] = "$('input[name=\"is_keyword\"]').parent().attr({\"method\":\"get\",\"no-error-return-url\":\"true\"});";
            }
            $scr[] = "$('input[name=\"is_keyword\"]').autocomplete( \"".$target."\", {  });";
            $scr[] = "})(jQuery);</script>";
            $script = implode(' ',$scr);
			Context::addHtmlFooter($script);
		}
	}
	
	/**
	 * @brief 통합 검색 출력
	 **/
	function ISX()
	{
		$oFile = &getClass('file');
		$oModuleModel = &getModel('module');

		// 권한 체크
		if(!$this->grant->access) return new Object(-1,'msg_not_permitted');
		$isxconfig = $this->isxconfig ;
		$config = $oModuleModel->getModuleConfig('integration_search');
		Context::set('config',$config);
		if(!$isxconfig->mskin) $isxconfig->mskin  = 'isxdefault';
        	$template_path = sprintf('%s/m.skins/%s', $this->module_path, $isxconfig->mskin);

		// Template path
        	$this->setTemplatePath($template_path);
        	$skin_vars = ($isxconfig->skin_vars) ? unserialize($isxconfig->skin_vars) : new stdClass;
        	Context::set('module_info', $skin_vars);

		$target = $config->target;
		if(!$target) $target = 'include';
		
		if(empty($config->target_module_srl))
            		$module_srl_list = array();
        	else
            		$module_srl_list = explode(',',$config->target_module_srl);

		// 검색어 변수 설정
		$is_keyword = Context::get('is_keyword');

		// 페이지 변수 설정
		$page = (int)Context::get('page');
		if(!$page) $page = 1;

		// 검색탭에 따른 검색
		$where = Context::get('where');

		// integration search model객체 생성
		if($is_keyword)
		{
			$oISx = &getModel('isx');
			$oIS = &getModel('integration_search');
			$oTrackbackModel = getAdminModel('trackback');
			Context::set('trackback_module_exist', true);
			if(!$oTrackbackModel)
			{
				Context::set('trackback_module_exist', false);
			}
			switch($where)
			{
				case 'document' :
					$search_target = Context::get('search_target');
					if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title_content';
					Context::set('search_target', $search_target);
					$output = $oIS->getDocuments($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("document", $page);
					break;
				case 'comment' :
					$output = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("comment", $page);
					break;
				case 'trackback' :
					$search_target = Context::get('search_target');
					if(!in_array($search_target, array('title','url','blog_name','excerpt'))) $search_target = 'title';
					Context::set('search_target', $search_target);
					$output = $oIS->getTrackbacks($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("trackback", $page);
					break;
				case 'multimedia' :
					$output = $oIS->getImages($target, $module_srl_list, $is_keyword, $page,20);
					Context::set('output', $output);
					$this->setTemplateFile("multimedia", $page);
					break;
				case 'file' :
					$output = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 20);
					Context::set('output', $output);
					$this->setTemplateFile("file", $page);
					break;
				case 'livexe' :
                    			$output = $oISx->getLivexeSearch($target, $module_srl_list, $is_keyword, $page, 20);
                    			Context::set('output', $output);
                    			$this->setTemplateFile("livexe", $page);
                    			break;
				default :
					$output['document'] = $oIS->getDocuments($target, $module_srl_list, 'title_content', $is_keyword, $page, 5);
					$output['comment'] = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 5);
					$output['trackback'] = $oIS->getTrackbacks($target, $module_srl_list, 'title', $is_keyword, $page, 5);
					$output['multimedia'] = $oIS->getImages($target, $module_srl_list, $is_keyword, $page, 5);
					$output['file'] = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 5);
					Context::set('search_result', $output);
					Context::set('search_target', 'title_content');
					$this->setTemplateFile("index", $page);
					break;
			}
			if($this->isxconfig->keyword_use == 'Y')
			{
				$oISx = &getModel('isx');
				$oISx->insertKeyword($is_keyword, $this->isxconfig);
			}
		}
		else
		{
			$this->setTemplateFile("no_keywords");
		}
	}

	function isxHighlight($is_keyword,$output)
	{
		if(!count($output)) return;
$out= new stdClass();
	    foreach($output as $key=>$data)
        {
			$track = $data->variables;
			$t=new stdClass();
			foreach ($track as $k=>$v)
			{
				$v = $this->isxKeywordReplace($is_keyword,$v);
				$t->{$k} = $v;
            }
			$data->variables = $t;
			$out->{$key} = $data;
        }
		return $out;
	}

	function isxKeywordReplace($key,$text)
	{
		$keywords = explode(" ", $key);
		foreach ($keywords as $val)
		{
			$text = preg_replace('/\<(li|d[tdl]|img|p|a|span|div|font)([^\>]*?)\>([^\<]*?)('.$val.')([^\>]*?)\>/is', '<$1$2>$3<span class="isx_highlight">$4</span>$5>', $text);
		}
		return $text;
	}

}
/* End of file isx.mobile.php */
/* Location: ./modules/isx/isx.mobile.php */
