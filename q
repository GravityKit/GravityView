[1mdiff --git a/includes/class-gravityview-change-entry-creator.php b/includes/class-gravityview-change-entry-creator.php[m
[1mindex f3854518..4d262bb6 100644[m
[1m--- a/includes/class-gravityview-change-entry-creator.php[m
[1m+++ b/includes/class-gravityview-change-entry-creator.php[m
[36m@@ -77,8 +77,9 @@[m [mclass GravityView_Change_Entry_Creator {[m
         $post_var = wp_parse_args([m
             wp_unslash( $_POST ),[m
             array([m
[31m-                'q' => '',[m
[32m+[m[32m                'q'        => '',[m
                 'gv_nonce' => '',[m
[32m+[m[32m                'on_load'  => '',[m
             )[m
         );[m
 [m
[36m@@ -100,6 +101,10 @@[m [mclass GravityView_Change_Entry_Creator {[m
             );[m
         }[m
 [m
[32m+[m[32m        if( 1 == $post_var['on_load'] ) {[m
[32m+[m[32m            $user_args[ 'number' ] = 100;[m
[32m+[m[32m        }[m
[32m+[m
         $users = GVCommon::get_users( 'change_entry_creator', $user_args );[m
 [m
         echo wp_send_json( $users, 200 );[m
