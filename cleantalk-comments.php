<?
add_action('admin_menu', 'ct_add_comments_menu');

function ct_add_comments_menu()
{
	add_comments_page( __("Check for spam", 'cleantalk'), __("Check for spam", 'cleantalk'), 'read', 'ct_check_spam', 'ct_show_checkspam_page');
}

function ct_show_checkspam_page()
{
	?>
	<div class="wrap">
		<h2><? _e("Check for spam", 'cleantalk'); ?></h2><br />
		<?
		$args_unchecked = array(
			'meta_query' => array(
				'relation' => 'AND',
				Array(
					'key' => 'ct_checked',
					'value' => '1',
					'compare' => 'NOT EXISTS'
				),
				Array(
					'key' => 'ct_hash',
					'value' => '1',
					'compare' => 'NOT EXISTS'
				)
			),
			'count'=>true
		);
		$cnt_unchecked=get_comments($args_unchecked);
		if($cnt_unchecked>0)
		{
		?>
			<button class="button" id="ct_check_spam_button"><? _e("Find spam comments", 'cleantalk'); ?></button><br />
		<?
		}
		?>
<?
		//print '<button class="button" id="ct_insert_comments">Insert comments</button><br />';
?>

		<div id="ct_working_message" style="display:none">
			<? _e("Please wait a while. CleanTalk checking all approved and pending comments via blacklist database at cleantalk.org. You will have option to delete found spam comments after plugin finish.", 'cleantalk'); ?>
		</div>
		<div id="ct_done_message" style="display:none">
			<? _e("Done. All comments tested via blacklists database, please see result bellow.", 'cleantalk'); ?>
		</div>
		<h3 id="ct_checking_status"></h3>
		<?
			$args_spam = array(
				'meta_query' => array(
					Array(
						'key' => 'ct_marked_as_spam',
						'compare' => 'EXISTS'
					)
				),
				'count'=>true
			);
			$cnt_spam=get_comments($args_spam);
			
			
			$page=1;
			if(isset($_GET['spam_page']))
			{
				$page=intval($_GET['spam_page']);
			}
			$args_spam = array(
				'meta_query' => array(
					Array(
						'key' => 'ct_marked_as_spam',
						'value' => '1',
						'compare' => 'NUMERIC'
					)
				),
				'number'=>30,
				'offset'=>($page-1)*30
			);
			
			$c_spam=get_comments($args_spam);
			if($cnt_spam>0)
			{
		?>
		<table class="widefat fixed comments">
			<thead>
				<th scope="col" id="cb" class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
					<input id="cb-select-all-1" type="checkbox"/>
				</th>
				<th scope="col" id="author" class="manage-column column-response sortable desc"><? print _e('Author');?></th>
				<th scope="col" id="comment" class="manage-column column-comment"><? print _x( 'Comment', 'column name' );;?></th>
				<th scope="col" id="response" class="manage-column column-response sortable desc"><? print _x( 'In Response To', 'column name' );?></th>
			</thead>
			<tbody id="the-comment-list" data-wp-lists="list:comment">
				<?
					for($i=0;$i<sizeof($c_spam);$i++)
					{
						?>
						<tr id="comment-<? print $c_spam[$i]->comment_ID; ?>" class="comment even thread-even depth-1 approved">
						<th scope="row" class="check-column">
							<label class="screen-reader-text" for="cb-select-<? print $c_spam[$i]->comment_ID; ?>">Select comment</label>
							<input id="cb-select-<? print $c_spam[$i]->comment_ID; ?>" type="checkbox" name="del_comments[]" value="<? print $c_spam[$i]->comment_ID; ?>"/>
						</th>
						<td class="author column-author">
						<strong>
							<?php echo get_avatar( $c_spam[$i]->comment_author_email , 32); ?>
							 <? print $c_spam[$i]->comment_author; ?>
							</strong>
							<br/>
							<a href="mailto:<? print $c_spam[$i]->comment_author_email; ?>"><? print $c_spam[$i]->comment_author_email; ?></a>
							<br/>
							<a href="edit-comments.php?s=<? print $c_spam[$i]->comment_author_IP ; ?>&mode=detail"><? print $c_spam[$i]->comment_author_IP ; ?></a>
						</td>
						<td class="comment column-comment">
							<div class="submitted-on">
								<? printf( __( 'Submitted on <a href="%1$s">%2$s at %3$s</a>' ), get_comment_link($c_spam[$i]->comment_ID),
									/* translators: comment date format. See http://php.net/date */
									get_comment_date( __( 'Y/m/d' ),$c_spam[$i]->comment_ID ),
									get_comment_date( get_option( 'time_format' ),$c_spam[$i]->comment_ID )
									); 
								?>
									
							</div>
							<p>
							<? print $c_spam[$i]->comment_content; ?>
							</p>
						</td>
						<td class="response column-response">
							<div class="response-links">
								<span class="post-com-count-wrapper">
									<a href="http://ct_wp/wp-admin/post.php?post=<? print $c_spam[$i]->comment_post_ID; ?>&action=edit"><? print get_the_title($c_spam[$i]->comment_post_ID); ?></a>
									<br/>
									<a href="http://ct_wp/wp-admin/edit-comments.php?p=<? print $c_spam[$i]->comment_post_ID; ?>" class="post-com-count">
										<span class="comment-count"><?
											$p_cnt=wp_count_comments();
											print $p_cnt->total_comments;
										?></span>
									</a>
								</span>
								<a href="<? print get_permalink($c_spam[$i]->comment_post_ID); ?>"><? print _e('View Post');?></a>
							</div>
						</td>
						</tr>
						<?
					}
				?>
				<tr class="comment even thread-even depth-1 approved">
					<td colspan="4"> 
						<?
							$args_spam = array(
								'meta_query' => array(
									Array(
										'key' => 'ct_marked_as_spam',
										'value' => '1',
										'compare' => 'NUMERIC'
									)
									
								),
								'count'=>true
							);
							$cnt_spam=get_comments($args_spam);
							$pages=ceil(intval($cnt_spam)/30);
							for($i=1;$i<=$pages;$i++)
							{
								if($i==$page)
								{
									print "<a href='edit-comments.php?page=ct_check_spam&spam_page=$i'><b>$i</b></a> ";
								}
								else
								{
									print "<a href='edit-comments.php?page=ct_check_spam&spam_page=$i'>$i</a> ";
								}								
							}
						?>
					</td>
				</tr>
			</tbody>
		</table>
		<button class="button" id="ct_delete_all"><? _e('Delete all content.'); ?></button> 
		<button class="button" id="ct_delete_checked"><? _e('Delete selected', 'cleantalk'); ?></button>
		<?
		}
		?>
	</div>
	<?
}

add_action('admin_print_footer_scripts','ct_add_checkspam_button');
function ct_add_checkspam_button()
{
    $screen = get_current_screen();
    $ajax_nonce = wp_create_nonce( "ct_secret_nonce" );
    ?>
    <script>
    	var ajax_nonce='<?php echo $ajax_nonce; ?>';
    	var spambutton_text='<? _e("Find spam comments", 'cleantalk'); ?>';
    </script>
    <?
    if( $screen->id == 'edit-comments' ){
        ?>
            <script src="<? print plugins_url( 'cleantalk-comments-editscreen.js', __FILE__ ); ?>"></script>
        <?php
    }
    if($screen->id == 'comments_page_ct_check_spam')
    {
    	?>
            <script src="<? print plugins_url( 'cleantalk-comments-checkspam.js', __FILE__ ); ?>"></script>
        <?php
    }
}


add_action( 'wp_ajax_ajax_check_comments', 'ct_ajax_check_comments' );

function ct_ajax_check_comments()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	
	$ct_options = ct_get_options();
	
	$args_unchecked = array(
		'meta_query' => array(
			'relation' => 'AND',
			Array(
				'key' => 'ct_checked',
				'value' => '1',
				'compare' => 'NOT EXISTS'
			),
			Array(
				'key' => 'ct_hash',
				'value' => '1',
				'compare' => 'NOT EXISTS'
			)
		),
		'number'=>999
	);
	
	$u=get_comments($args_unchecked);
	if(sizeof($u)>0)
	{
		//print_r($unchecked);
		$data=Array();
		for($i=0;$i<sizeof($u);$i++)
		{
			$data[]=$u[$i]->comment_author_IP;
			$data[]=$u[$i]->comment_author_email;
		}
		$data=implode(',',$data);
		
		$request="data=$data";
		
		$opts = array(
		    'http'=>array(
		        'method'=>"POST",
		        'content'=>$request,
		    )
		);
		
		$context = stream_context_create($opts);
		
		$result = @file_get_contents("https://api.cleantalk.org/?method_name=spam_check&auth_key=".$ct_options['apikey'], 0, $context);
		$result=json_decode($result);
		if(isset($result->error_message))
		{
			print $result->error_message;
		}
		else
		{
			for($i=0;$i<sizeof($u);$i++)
			{
				add_comment_meta($u[$i]->comment_ID,'ct_checked',date("Y-m-d H:m:s"),true);
				$uip=$u[$i]->comment_author_IP;
				if(empty($uip))continue;
				$uim=$u[$i]->comment_author_email;
				if(empty($uim))continue;
				if($result->data->$uip->appears==1||$result->data->$uim->appears==1)
				{
					add_comment_meta($u[$i]->comment_ID,'ct_marked_as_spam','1',true);
				}
			}
			print 1;
		}
	}
	else
	{
		print 0;
	}

	die;
}

add_action( 'wp_ajax_ajax_info_comments', 'ct_ajax_info_comments' );
function ct_ajax_info_comments()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	$cnt=get_comments(Array('count'=>true));
	
	$args_spam = array(
		'meta_query' => array(
			Array(
				'key' => 'ct_marked_as_spam',
				'value' => '1',
				'compare' => 'NUMERIC'
			)
		),
		'count'=>true,
	);
	
	$cnt_spam=get_comments($args_spam);
	
	$args_checked1=array(
		'meta_query' => array(
			Array(
				'key' => 'ct_hash',
				'compare' => 'EXISTS'
			)
		),
		'count'=>true
	);
	$args_checked2=array(
		'meta_query' => array(
			Array(
				'key' => 'ct_checked',
				'compare' => 'EXISTS'
			)
		),
		'count'=>true
	);
	
	$cnt_checked1=get_comments($args_checked1);
	$cnt_checked2=get_comments($args_checked2);
	$cnt_checked=$cnt_checked1+$cnt_checked2;
	
	printf (__("Total comments %s. Checked %s, found spam comments: %s.", 'cleantalk'), $cnt, $cnt_checked, $cnt_spam);
	die();
}

add_action( 'wp_ajax_ajax_insert_comments', 'ct_ajax_insert_comments' );
function ct_ajax_insert_comments()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	$time = current_time('mysql');
	
	for($i=0;$i<500;$i++)
	{
		$rnd=mt_rand(1,100);
		if($rnd<20)
		{
			$email="stop_email@example.com";
		}
		else
		{
			$email="stop_email_$rnd@example.com";
		}
		$data = array(
			'comment_post_ID' => 1,
			'comment_author' => "author_$rnd",
			'comment_author_email' => $email,
			'comment_author_url' => 'http://',
			'comment_content' => "comment content ".mt_rand(1,10000)." ".mt_rand(1,10000)." ".mt_rand(1,10000),
			'comment_type' => '',
			'comment_parent' => 0,
			'user_id' => 1,
			'comment_author_IP' => '127.0.0.1',
			'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
			'comment_date' => $time,
			'comment_approved' => 1,
		);
		
		wp_insert_comment($data);
	}
	print "ok";
	die();
}

add_action( 'wp_ajax_ajax_delete_checked', 'ct_ajax_delete_checked' );
function ct_ajax_delete_checked()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	foreach($_POST['ids'] as $key=>$value)
	{
		wp_delete_comment($value, true);
	}
	die();
}

add_action( 'wp_ajax_ajax_delete_all', 'ct_ajax_delete_all' );
function ct_ajax_delete_all()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	$args_spam = array(
		'meta_query' => array(
			Array(
				'key' => 'ct_marked_as_spam',
				'value' => '1',
				'compare' => 'NUMERIC'
			)
		)
	);	
	$c_spam=get_comments($args_spam);
	for($i=0;$i<sizeof($c_spam);$i++)
	{
		wp_delete_comment($c_spam[$i]->comment_ID, true);
	}
	die();
}