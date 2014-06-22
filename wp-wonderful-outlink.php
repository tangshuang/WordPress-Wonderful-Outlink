<?php
/*
Plugin Name: Wonderful Links SEO
Plugin URI: http://dev.utubon.com/wonderful-links-seo/
Description: 为您提供一个通用的推广链接管理界面，并在文章中方便的使用它们，并实现SEO目的。
Version: 1.0
Author: 否子戈
Author URI: http://www.utubon.com/
*/

define('GO_PERFIX','go');

// 创建数据库表
function wonderful_links_seo_create_table(){
	global $wpdb;
	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wonderful_links_seo`(
		`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		`title` text NOT NULL,
		`link` longtext NOT NULL,
		`order` bigint(20) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`ID`),
		FULLTEXT KEY ( `link` )
	) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
	$wpdb->query($sql);
}
if(get_option('wonderful_links_seo_switch') != 'turned_on'){
	wonderful_links_seo_create_table();
	add_option('wonderful_links_seo_switch','turned_on');
}

// 获取链接(单一链接)
function wonderful_links_seo_get($id){
	global $wpdb;
	$url = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}wonderful_links_seo` WHERE `ID`='{$id}';");
	if(!$url)return false;
	$url->link = urldecode($url->link);
	return $url;
}

// 添加链接
function wonderful_links_seo_add($title,$url,$order){
	global $wpdb;
	// 添加链接到数据库
	$wpdb->insert(
		$wpdb->prefix.'wonderful_links_seo',
		array('title' => $title,'link' => urlencode($url),'order' => $order)
	);
	return $wpdb->insert_id;
}

// 更新链接
function wonderful_links_seo_update($id,$title,$url,$order){
	global $wpdb;
	$wpdb->update(
		$wpdb->prefix.'wonderful_links_seo',
		array('title' => $title,'link' => urlencode($url),'order' => $order),
		array('ID' => $id)
	);
}

// 删除链接
function wonderful_links_seo_delete($id){
	global $wpdb;
	$wpdb->query("DELETE FROM `{$wpdb->prefix}wonderful_links_seo` WHERE `ID`='{$id}';");
}

// 添加管理面板
add_action('admin_menu','wonderful_links_seo_setup');
function wonderful_links_seo_setup(){
	if(!empty($_POST) && $_POST['page'] == $_GET['page'] && $_POST['action'] == 'UpdateWonderfulLinksSEO'){
		check_admin_referer();
		$WonderfulLinksSEO = $_POST['WonderfulLinksSEO'];
		if(!empty($WonderfulLinksSEO))foreach ($WonderfulLinksSEO as $key => $link) {
			if($key == 'add' && $link['title'] != '') :
				$id = wonderful_links_seo_add($link['title'],$link['link'],$link['order']);
			elseif(is_numeric($key)) :
				if($link['title'] == ''){
					wonderful_links_seo_delete($key);
					continue;
				}else{
					wonderful_links_seo_update($key,$link['title'],$link['link'],$link['order']);					
				}
			endif;
		}
		if(isset($id) && is_numeric($id)){
			$uri = remove_query_arg(array('paged','time'));
			$uri = add_query_arg('show',$id,$uri);
		}else{
			$uri = add_query_arg('time',time());
		}
		wp_redirect($uri);
		exit;
	}
	add_submenu_page('plugins.php','完美外链 Wonderful Links SEO','WonderfulLinksSEO','edit_theme_options','wonderful_links_seo','wonderful_links_seo_admin');
}
function wonderful_links_seo_admin(){
	global $wpdb;
?>
<style>
#wonderful-links-seo-admin .table-colum{float: left;width: 20%;border-right:1px solid #CCC;}
#wonderful-links-seo-admin .table-colum-head{font-weight:bold;text-align: center;padding: 5px 0;}
#wonderful-links-seo-admin .table-colum input{width: 90%;margin:5px;}
#wonderful-links-seo-admin .id{width: 5%;}
#wonderful-links-seo-admin .id input{width: 40px;margin: 5px 0;}
#wonderful-links-seo-admin .order{width: 13%;border-right: 0;}
</style>
<div class="wrap" id="wonderful-links-seo-admin">
	<h2>SEO网址跳转 Wonderful Links SEO</h2>
	<br class="clear" />
    <div class="metabox-holder">
	    <form method="post">
			<div class="postbox">
				<h3>使用说明</h3>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
					1、在下方列表的最后一行添加新的推广链接，每次只能添加一个；<br />
					2、直接修改每一个对应的链接及其相关信息，提交即可；<br />
					3、如果要删除某一个链接，删除对应的标题即可删除这个跳转（不推荐，否则404，<span style="color:red">注意：删除是没有任何提醒的</span>）；<br />
					4、排序：数值越大排的越靠前，你可以利用它来实现分组排列；<br />
					5、使用：复制对应的代码作为链接插入到你需要使用的地方即可（需自己增加rel=nofollow）。也可以在文章中要使用该功能的地方使用短代码如 [go id=21] 或 [go id=21]自己规定锚文本[/go]（不需要自己增加rel=nofollow）。
				</div>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
					搜索链接：<input type="text" class="regular-text" name="SearchWonderfulLink" />
					<button type="submit" name="action" value="SearchWonderfulLinksSEO" class="button-primary">搜索</button>
				</div>
			</div>
			<div class="postbox">
			<?php if(isset($_GET['show']) || isset($_POST['SearchWonderfulLink']) ) : ?>
				<h3>添加或搜索成功</h3>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<div class="id table-colum table-colum-head">串号</div>
					<div class="title table-colum table-colum-head">标题</div>
					<div class="link table-colum table-colum-head">链接</div>
					<div class="code table-colum table-colum-head">跳转地址</div>
					<div class="code table-colum table-colum-head">短代码</div>
					<div class="order table-colum table-colum-head">排序</div>
					<div class="clear"></div>
				</div>
				<?php
					if(is_numeric($_GET['show'])){
						echo '<div id="message" class="updated fade"><p><strong>添加成功！</strong></p></div>';
						$links = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}wonderful_links_seo` WHERE `ID`={$_GET['show']};");
					}elseif($_POST['SearchWonderfulLink']){
						echo '<div id="message" class="updated fade"><p><strong>搜到结果！</strong></p></div>';
						$url = trim($_POST['SearchWonderfulLink']);
						$last_word = substr($url,-1);
						if($last_word == '/')$url = substr_replace($url,'',-1);
						$url = urlencode($url);
						$links = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}wonderful_links_seo` WHERE `link` LIKE '%{$url}%' ORDER BY `order` DESC;");
					}
					if(!empty($links)) : foreach($links as $key => $link) :
				?>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<div class="id table-colum"><input type="text" class="regular-text" value="<?php echo $link->ID; ?>" readonly="readonly" /></div>
					<div class="title table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[<?php echo $link->ID; ?>][title]" value="<?php echo $link->title; ?>" /></div>
					<div class="link table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[<?php echo $link->ID; ?>][link]" value="<?php echo urldecode($link->link); ?>" /></div>
					<div class="code table-colum"><input type="text" class="regular-text" value="<?php echo home_url('/'.GO_PERFIX.'/'.$link->ID.'/'); ?>" readonly="readonly" /></div>
					<div class="code table-colum"><input type="text" class="regular-text" value="[go id=<?php echo $link->ID; ?>]" readonly="readonly" /></div>
					<div class="order table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[<?php echo $link->ID; ?>][order]" value="<?php echo $link->order; ?>" /></div>					
					<div class="clear"></div>
				</div>
				<?php endforeach;	endif; ?>
			<?php else : ?>
				<h3>列表、添加、修改、删除</h3>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<div class="id table-colum table-colum-head">串号</div>
					<div class="title table-colum table-colum-head">标题</div>
					<div class="link table-colum table-colum-head">链接</div>
					<div class="code table-colum table-colum-head">跳转地址</div>
					<div class="code table-colum table-colum-head">短代码</div>
					<div class="order table-colum table-colum-head">排序</div>
					<div class="clear"></div>
				</div>
				<?php
				$length = 10;
				$start = (isset($_GET['paged']) && is_numeric($_GET['paged'])? ($_GET['paged']-1)*$length : 0);
				$links = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}wonderful_links_seo` ORDER BY `order` DESC LIMIT {$start},{$length};");
				if(!empty($links)) : foreach($links as $key => $link) :
				?>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<div class="id table-colum"><input type="text" class="regular-text" value="<?php echo $link->ID; ?>" readonly="readonly" /></div>
					<div class="title table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[<?php echo $link->ID; ?>][title]" value="<?php echo $link->title; ?>" /></div>
					<div class="link table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[<?php echo $link->ID; ?>][link]" value="<?php echo urldecode($link->link); ?>" /></div>
					<div class="code table-colum"><input type="text" class="regular-text" value="<?php echo home_url('/'.GO_PERFIX.'/'.$link->ID.'/'); ?>" readonly="readonly" /></div>
					<div class="code table-colum"><input type="text" class="regular-text" value="[go id=<?php echo $link->ID; ?>]" readonly="readonly" /></div>
					<div class="order table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[<?php echo $link->ID; ?>][order]" value="<?php echo $link->order; ?>" /></div>					
					<div class="clear"></div>
				</div>	
				<?php endforeach;endif; ?>
			<?php endif; ?>
				<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:0 10px;">
					<div class="id table-colum"><input type="text" class="regular-text" value="添加：" readonly="readonly" disabled="disabled" /></div>
					<div class="title table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[add][title]" value="" /></div>
					<div class="link table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[add][link]" value="" /></div>
					<div class="code table-colum"><input type="text" class="regular-text" value="自动生成" readonly="readonly" disabled="disabled" /></div>
					<div class="code table-colum"><input type="text" class="regular-text" value="自动生成" readonly="readonly" disabled="disabled" /></div>
					<div class="order table-colum"><input type="text" class="regular-text" name="WonderfulLinksSEO[add][order]" value="" /></div>					
					<div class="clear"></div>
				</div>
			</div>
			<p><?php
				$current_page = (isset($_GET['paged']) && is_numeric($_GET['paged'])) ? $_GET['paged'] : 1; // 当前页数
				$per_page_count = $length ? $length : 10; // 每页要显示的条数
				$total_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}wonderful_links_seo`;"); // 总条数
				$total_page = ($total_count%$per_page_count) ? (int)($total_count/$per_page_count) + 1 : (int)($total_count/$per_page_count); // 总页数
				echo paginate_links(array(
					'base' => add_query_arg('paged','%#%' ),
					'format' => '?paged=%#%',
					'show_all' => true,
					'total' => $total_page,
					'current' => $current_page,
					'prev_text' => '上一页',
					'next_text' => '下一页'
				));
			?></p>
			<p class="submit">
				<button type="submit" name="action" value="UpdateWonderfulLinksSEO" class="button-primary">提交</button>
				<a href="<?php echo remove_query_arg(array('show','time')); ?>" class="button-primary">返回列表</a>
			</p>
		    <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
		    <?php wp_nonce_field(); ?>
	    </form>
    </div>
</div>
<script>
jQuery(function($){
	$('#wonderful-links-seo-admin div.code input').click(function(){
		var $this = $(this),$value = $this.val();
		if($value != ''){
			$this.select();
			if(window.clipboardData){
				window.clipboardData.setData('text',$value);
				alert('已复制，请黏贴链接到你需要的地方');
			}
		}
	});
});
</script>
<?php
}

// 增加短代码
function add_shortcode_go($atts,$content = null){
	extract(shortcode_atts(array(
		'id' => ''
	), $atts));
	$link = wonderful_links_seo_get($id);
	$url = home_url('/'.GO_PERFIX.'/'.$id.'/');
	$title = $link->title;
	if(trim($content)){
		return "<a href='{$url}' title='{$title}' target='_blank' rel='nofollow external' class='external'>{$content}</a>";		
	}else{	
		return "<a href='{$url}' title='{$title}' target='_blank' rel='nofollow external' class='external'>{$title}</a>";		
	}
}
add_shortcode(GO_PERFIX,'add_shortcode_go');

// 开始跳转
$currentURI = explode('/',$_SERVER["REQUEST_URI"]);
if(defined('MULTISITE') && MULTISITE && is_multisite() && $currentURI[2] == GO_PERFIX && is_numeric($currentURI[3])):
	$wonderful_link_id = $currentURI[3];
	$wonderful_link = wonderful_links_seo_get($wonderful_link_id);
	if(isset($wonderful_link->link) && !empty($wonderful_link->link)):
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$wonderful_link->link}");
		exit;
	endif;
elseif($currentURI[1] == GO_PERFIX && is_numeric($currentURI[2])) :
	$wonderful_link_id = $currentURI[2];
	$wonderful_link = wonderful_links_seo_get($wonderful_link_id);
	if(isset($wonderful_link->link) && !empty($wonderful_link->link)):
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$wonderful_link->link}");
		exit;
	endif;
endif;