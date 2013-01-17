<?php
error_reporting(E_ALL ^ E_NOTICE);
require 'fb-sdk/facebook.php';

class Group_Leaders{

    var $points;
    var $facebook;
    var $appId;
    var $secret;
    var $leaders = array();
    var $group_id;

    /**
     * Constructor
     */
    function __construct(){
        /**
         * Set points
         */
        $this -> points = array(
            'post' => 1, //in future may be:)
            'o_comment' => 1, //Give points to the owner of the post for every comment other user make on his/her post
            'o_like' => 1.5,
            'u_like' => 0.1, //Give points to user for liking the post,
            'u_comment' => 0.5
            );

        /**
         * Facebook Settings
         */
        $this -> appId = '330088920437121';
        $this -> secret = 'e90b58818623ff4ea5952f34ba4e2296';

        /**
         * Initialize Facebook Object
         */
        $this -> facebook = new Facebook(array(
            'appId' => $this -> appId,
            'secret' => $this -> secret ));

        /**
         * Set Group ID
         */
        $this -> group_id = 220266924662120;

    }

    function get_leaders(){

        try{

            $fql = "SELECT actor_id,  message, likes, comments FROM stream  WHERE source_id = {$this -> group_id} LIMIT 100";


            $group_data = $this -> facebook -> api(array(
                'method' => 'fql.query',
                'query' => $fql));


            //_d($group_data);
            foreach((array)$group_data as $key => $val){
               //Get the id of the post owner
               //Do not give points to the post owner's likes and comments
               $post_owner = $val['actor_id'];

               //Merge the likes['sample'] and likes['friends'] array
               $likes = array_merge($val['likes']['sample'], $val['likes']['friends']);

               //remove post_owners like
               unset($likes[$post_owner]);

               //Give points to owner for user likes
               $this -> set_points($post_owner, count($likes)*$this->points['o_like'], 'likes_points');

               foreach($likes as $like){
                $this -> set_points($like, $this -> points['u_like'], 'likes_points');
               }

                $comments_count = 0;
                foreach($val['comments']['comment_list'] as $comments){
                    if($comments['fromid']!=$post_owner){
                        $this -> set_points($comments['fromid'], $this -> points['u_comment'], 'comments_points');
                        $comments_count++;
                    }
                }
                $this -> set_points($post_owner, $comments_count*$this -> points['o_comment'], 'comments_points');
            }
            arsort($this -> leaders);
            $this -> leaders = array_slice($this -> leaders, 0, 10, true);
            $top_10 = array_keys($this -> leaders);
            $top_10 = implode(',', $top_10);

             $fql = "SELECT id, name, pic_square, url FROM profile  WHERE id IN($top_10)";


            $users_data = $this -> facebook -> api(array(
                'method' => 'fql.query',
                'query' => $fql));

            foreach($users_data as $user){
                $this -> leaders[$user['id']]['name'] = $user['name'];
                $this -> leaders[$user['id']]['pic_square'] = $user['pic_square'];
                $this -> leaders[$user['id']]['url'] = $user['url'];
            }
            return $this -> leaders;

        }catch(FacebookApiException $e){
            _d($e);

        }
    }

    function set_points($id, $points, $of_what){

        if(!array_key_exists($id, $this -> leaders)){
            $this -> leaders[$id]['total_points'] = 0;
        }

        $this -> leaders[$id]['total_points'] += $points;

    }

}

/**
 * Friendly DEBUG function
 * @param  [type] $what [description]
 * @return [type]       [description]
 */
function _d($what){
    echo '<pre>';
    print_r($what);
    echo '</pre>';
}

$gl = new Group_Leaders();
?>
<!DOCTYPE>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Leaderboard</title>
    <style type="text/css">
    /* http://meyerweb.com/eric/tools/css/reset/
   v2.0 | 20110126
   License: none (public domain)
    */

        html, body, div, span, applet, object, iframe,
        h1, h2, h3, h4, h5, h6, p, blockquote, pre,
        a, abbr, acronym, address, big, cite, code,
        del, dfn, em, img, ins, kbd, q, s, samp,
        small, strike, strong, sub, sup, tt, var,
        b, u, i, center,
        dl, dt, dd, ol, ul, li,
        fieldset, form, label, legend,
        table, caption, tbody, tfoot, thead, tr, th, td,
        article, aside, canvas, details, embed,
        figure, figcaption, footer, header, hgroup,
        menu, nav, output, ruby, section, summary,
        time, mark, audio, video {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            font: inherit;
            vertical-align: baseline;
        }
        /* HTML5 display-role reset for older browsers */
        article, aside, details, figcaption, figure,
        footer, header, hgroup, menu, nav, section {
            display: block;
        }
        body {
            line-height: 1;

        }
        ol, ul {
            list-style: none;
        }
        blockquote, q {
            quotes: none;
        }
        blockquote:before, blockquote:after,
        q:before, q:after {
            content: '';
            content: none;
        }
        table {
            border-collapse: collapse;
            border-spacing: 0;
        }
        body{
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        #leader-board{
            list-style: none;
            margin: 30px auto;
            width: 300px;
            padding: 4px;
            background: #E9EAEE;
            border: 2px solid #999;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
            box-shadow: 1px 2px 6px rgba(0, 0, 0, 0.5);
            -moz-box-shadow: 1px 2px 6px rgba(0,0,0, 0.5);
            -webkit-box-shadow: 1px 2px 6px rgba(0, 0, 0, 0.5);

        }
        .clear{
            clear: both;
        }
        #leader-board li{
            padding: 10px;

        }
        #leader-board img{
            float: left;
            margin-right: 8px;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
             border: 2px solid white;

        }
        .name h3{
            font-size: 16px;
            margin-bottom: 4px;
        }

    </style>
</head>
    <body>

        <ol id="leader-board">
        <?php foreach((array)$gl->get_leaders() as $key => $val){
            ?>
            <li>
                <img src="<?php echo $val['pic_square']?>" alt="">
                <div class="meta">
                    <div class="name">
                        <h3><?php echo $val['name']?></h3>
                    </div>
                    <div class="points">
                        <span>Points:</span> <?php echo $val['total_points']?>
                    </div>
                </div>
                <div class="clear"></div>
            </li>

            <?php
        }
        ?>
        </ol>
    </body>
</html>