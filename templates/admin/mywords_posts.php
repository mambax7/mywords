<h1 class="cu-section-title mw_titles"><span style="background-position: -32px 0;">&nbsp;</span><?php _e('Posts Management','mywords'); ?></h1>
<form name="frmSearch" method="get" action="posts.php" style="margin: 0;">
    <div class="row">
        <div class="col-md-2 col-lg-2">
            <input type="text" name="keyw" value="<?php echo $keyw!='' ? $keyw : ''; ?>" class="form-control" placeholder="<?php _e('Search', 'mywords'); ?>">
        </div>
        <div class="col-md-2 col-lg-2">
            <div class="input-group">
                <span class="input-group-addon"><?php _e('Results:', 'mywords'); ?></span>
                <input type="text" size="5" name="limit" value="<?php echo $limit; ?>" class="form-control" />
                <span class="input-group-btn">
                    <button class="btn btn-info" type="submit"><i class="icon-search"></i></button>
                </span>
            </div>
        </div>
        <div class="col-md-8 col-lg-8">
            
            <ul class="nav nav-pills">
                <li><a href="posts.php?op=new"><?php _e('Add New','mywords'); ?></a></li>
                <li><a href="posts.php?limit=<?php echo $limit ?>"><?php _e('Show all','mywords'); ?> <strong>(<?php echo ($pub_count+$draft_count+$pending_count); ?>)</strong></a></li>
                <li><a href="posts.php?status=publish&amp;limit=<?php echo $limit ?>"><?php _e('Published', 'admin_mywords'); ?> <strong>(<?php echo $pub_count; ?>)</strong></a></li>
                <li><a href="posts.php?status=draft&amp;limit=<?php echo $limit ?>"><?php _e('Drafts', 'admin_mywords'); ?> <strong>(<?php echo $draft_count; ?>)</strong></a></li>
                <li><a href="posts.php?status=waiting&amp;limit=<?php echo $limit ?>"><?php _e('Pending of Review', 'admin_mywords'); ?> <strong>(<?php echo $pending_count; ?>)</strong></a></li>
            </ul>
            
        </div>
    </div>
</form>
<br />
<form name="modPosts" id="form-posts" method="post" action="posts.php">
<div class="cu-bulk-actions">
    <div class="row">
        <div class="col-md-4 col-lg-4">
            <select name="op" id="posts-op" class="form-control">
                <option value=""><?php _e('Bulk Actions','mywords'); ?></option>
                <option value="delete"><?php _e('Delete Posts','mywords'); ?></option>
                <option value="status-waiting"><?php _e('Set status as Pending review','mywords'); ?></option>
                <option value="status-draft"><?php _e('Set status as Draft','mywords'); ?></option>
                <option value="status-published"><?php _e('Set status as published','mywords'); ?></option>
            </select>
            <button type="button" onclick="submit();" class="btn btn-default"><?php _e('Apply', 'mywords'); ?></button>
        </div>
        <div class="col-md-8 col-lg-8">
            <?php echo isset($nav) ? $nav->render(false) : ''; ?>
        </div>
    </div>

</div>
<table border="0" cellspacing="1" cellpadding="0" class="table table-bordered">
	<thead>
  <tr class="head" align="center">
  	<th align="center" width="30"><input type="checkbox" name="checkall" id="checkall" value="1" onclick='$("#form-posts").toggleCheckboxes(":not(#checkall)");' /></th>
    <th align="left" width="30%"><?php _e('Post','mywords'); ?></th>
    <th><?php _e('Author','mywords'); ?></th>
    <th align="left"><?php _e('Categories','mywords'); ?></th>
    <th align="left"><?php _e('Tags','mywords'); ?></th>
    <th><img src="../images/commi.png" alt="" /></th>
    <th><img src="../images/reads.png" alt="" /></th>
	<th><?php _e('Date','mywords'); ?></th>
  </tr>
	</thead>
    <tfoot>
    <tr class="head" align="center">
        <th align="center" width="30"><input type="checkbox" name="checkall2" id="checkall2" value="1" onclick='$("#form-posts").toggleCheckboxes(":not(#checkall2)");' /></th>
        <th align="left" width="30%"><?php _e('Post','mywords'); ?></th>
        <th><?php _e('Author','mywords'); ?></th>
        <th align="left"><?php _e('Categories','mywords'); ?></th>
        <th align="left"><?php _e('Tags','mywords'); ?></th>
        <th><img src="../images/commi.png" alt="" /></th>
        <th><img src="../images/reads.png" alt="" /></th>
        <th><?php _e('Date','mywords'); ?></th>
    </tr>
    </tfoot>
	<tbody>
  <?php if(empty($posts)): ?>
  <tr class="even">
  	<td colspan="8" align="center" class="error"><?php _e('No posts where found','mywords'); ?></td>
  </tr>
  <?php endif; ?>
  <?php foreach($posts as $post): ?>
  <tr class="<?php echo tpl_cycle('even,odd'); ?>" valign="top">
  	<td align="center" valign="top"><input type="checkbox" name="posts[]" id="post-<?php echo $post['id']; ?>" value="<?php echo $post['id']; ?>" /></td>
    <td>
    	<strong>
    	    <a href="posts.php?op=edit&amp;id=<?php echo $post['id']; ?>"><?php echo $post['title']; ?></a>
            <?php switch($post['status']){
                case 'draft':
                    echo "<span class=\"draft\">- ".__('Draft','mywords')."</span> ";
                    break;
                case 'scheduled':
                    echo "<span class=\"sheduled\">- ".__('Scheduled','mywords')."</span> ";
                    break;
                case 'waiting':
                    echo "<span class=\"pending\">- ".__('Pending','mywords')."</span> ";
                    break;
            } ?>
        </strong>
    	<span class="mw_options">
    		<a href="posts.php?op=edit&amp;id=<?php echo $post['id']; ?>"><?php _e('Edit','mywords'); ?></a> |
    		<a href="javascript:;" onclick="return post_del_confirm('<?php echo $post['title']; ?>', <?php echo $post['id']; ?>);"><?php _e('Delete','mywords'); ?></a> |
    		<?php if($post['status']!='publish'): ?>
    			<a href="<?php echo MW_URL.'?p='.$post['id']; ?>"><?php _e('Preview','mywords'); ?></a>
    		<?php else: ?>
    			<a href="<?php echo $post['link']; ?>"><?php _e('View','mywords'); ?></a>
    		<?php endif; ?>
    	</span>
    </td>
    <td align="center"><a href="posts.php?author=<?php echo $post['uid'] ?>"><?php echo $post['uname'] ?></a></td>
    <td class="mw_postcats"><?php echo $post['categories']; ?></td>
    <td class="mw_postcats">
    <?php 
    $count = 1;
    $ct = count($post['tags']);
    foreach ($post['tags'] as $tag): ?>
    <?php echo $tag['tag']; ?><?php echo $count<$ct ? ',' : ''; ?>
    <?php $count++; endforeach; ?>
    </td>
    <td align="center">
		<?php echo $post['comments']; ?>
	</td>
    <td align="center"><?php echo $post['reads']; ?></td>
    <td align="center"><?php echo $post['date']; ?></td>
  </tr>
  <?php endforeach; ?>
	</tbody>
</table>
<?php echo $xoopsSecurity->getTokenHTML(); ?>
<input type="hidden" name="page" value="<?php echo $page; ?>" />
<input type="hidden" name="keyw" value="<?php echo $keyw; ?>" />
<input type="hidden" name="limit" value="<?php echo $limit; ?>" />
</form>
